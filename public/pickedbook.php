<?php
session_start();
require_once __DIR__ . '/../src/auth_functions.php';
require_once __DIR__ . '/../src/database.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $_SESSION['user_id'] ?? null;
$user_role = '';

refreshSessionUser();
cancelInvalidExchangeProposals();

if (isLoggedIn()) {

    if (isset($_SESSION['user_id'])) {
        $is_logged_in = true;
        $user_name = htmlspecialchars($_SESSION['user_name'] ?? '');
        $user_role = htmlspecialchars($_SESSION['user_role'] ?? 'user');
    }

} else {
    header('Location: index.php');
    exit();
}

if (isset($_SESSION['user_id'])) {
    if (empty($_SESSION['user_role'])) {
        header('Location: index.php');
        exit();
    } elseif (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'banned') {
        header('Location: banned.php');
        exit();
    }
}

// Validar parámetro id
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($book_id <= 0) {
    header('Location: index.php');
    exit();
}

$book = getBookById($book_id);

if (!$book) {
    header('Location: index.php');
    exit();
}

// Si el libro no está disponible, redirige
if ($book['status'] !== true) {
    header('Location: index.php');
    exit();
}

// Si el usuario es el dueño, no mostrar botones de oferta/chat
$is_owner = ($is_logged_in && $current_user_id == $book['ownerid']);

// Mensajes POST/REDIRECT/GET - Consolidados
$proposal_message = '';
$proposal_error = '';
$edit_message = '';
$edit_error = '';
$admin_message = '';

if (isset($_SESSION['proposal_message'])) {
    $proposal_message = $_SESSION['proposal_message'];
    unset($_SESSION['proposal_message']);
}
if (isset($_SESSION['proposal_error'])) {
    $proposal_error = $_SESSION['proposal_error'];
    unset($_SESSION['proposal_error']);
}
if (isset($_SESSION['edit_message'])) {
    $edit_message = $_SESSION['edit_message'];
    unset($_SESSION['edit_message']);
}
if (isset($_SESSION['edit_error'])) {
    $edit_error = $_SESSION['edit_error'];
    unset($_SESSION['edit_error']);
}
if (isset($_SESSION['admin_message'])) {
    $admin_message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

// Procesar eliminación del libro por admin PRIMERO (máxima prioridad)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_delete_book']) && isAdmin() && !$is_owner) {
    $deleted = deleteBookByAdmin($book['id'], $current_user_id);
    if ($deleted) {
        $_SESSION['admin_message'] = "¡Libro eliminado por administrador!";
        header("Location: adminpanel.php");
        exit();
    } else {
        $_SESSION['admin_message'] = "Error al eliminar el libro.";
        header("Location: pickedbook.php?id=" . $book['id']);
        exit();
    }
}

// Procesar propuesta POST, luego redirigir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_owner && $is_logged_in && !isset($_POST['admin_delete_book']) && !isset($_POST['delete_book']) && !isset($_POST['edit_book'])) {
    if ($book['typeof'] === 'Donacion') {
        $proposal_id = createProposal($current_user_id, $book['id'], 'Donacion');
        $_SESSION['proposal_message'] = $proposal_id ? '¡Propuesta de donación registrada!' : 'Error al registrar la propuesta.';
    } elseif ($book['typeof'] === 'Venta') {
        $amount = floatval($_POST['amount'] ?? 0);
        if ($amount > 0) {
            $proposal_id = createProposal($current_user_id, $book['id'], 'Venta', $amount);
            $_SESSION['proposal_message'] = $proposal_id ? '¡Propuesta de compra registrada!' : 'Error al registrar la propuesta.';
        } else {
            $_SESSION['proposal_error'] = 'Debes ingresar un monto válido.';
        }
    } elseif ($book['typeof'] === 'Intercambio') {
        $offered_books = $_POST['offered_books'] ?? [];
        if (!empty($offered_books)) {
            $proposal_id = createExchangeProposal($current_user_id, $book['id'], $offered_books);
            $_SESSION['proposal_message'] = $proposal_id ? '¡Propuesta de intercambio registrada!' : 'Error al registrar la propuesta.';
        } else {
            $_SESSION['proposal_error'] = 'Debes seleccionar al menos un libro para intercambiar.';
        }
    } elseif ($book['typeof'] === 'Subasta') {
        $amount = floatval($_POST['amount'] ?? 0);
        $base = floatval($book['price']);
        if ($amount > $base) {
            $proposal_id = createAuctionProposal($current_user_id, $book['id'], $amount);
            $_SESSION['proposal_message'] = $proposal_id ? '¡Puja registrada y monto actualizado!' : 'Error al registrar la puja.';
        } else {
            $_SESSION['proposal_error'] = 'La puja debe ser mayor al monto base actual.';
        }
    }
    // Redirige para evitar duplicados al recargar
    header("Location: pickedbook.php?id=" . $book['id']);
    exit();
}

// Para intercambio: obtener libros del usuario
$user_books = [];
if ($book['typeof'] === 'Intercambio' && $is_logged_in) {
    $user_books = getBooksByUserId($current_user_id);
}

// Obtener información del propietario del libro
$book_owner = getUserById($book['ownerid']);

// Si el propietario está banneado y el usuario no es admin, redirigir
if ($book_owner && $book_owner['userrole'] === 'banned' && !isAdmin()) {
    header('Location: index.php');
    exit();
}

$nombredueno = $book_owner["name"];

$edit_mode = isset($_GET['edit']) && $is_owner;
$edit_message = '';
$edit_error = '';

// Procesar edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_owner && isset($_POST['edit_book'])) {
    $update_data = [
        'name' => $_POST['name'] ?? $book['name'],
        'author' => $_POST['author'] ?? $book['author'],
        'editorial' => $_POST['editorial'] ?? $book['editorial'],
        'genre' => $_POST['genre'] ?? $book['genre'],
        'description' => $_POST['description'] ?? $book['description'],
        'qstatus' => $_POST['qstatus'] ?? $book['qstatus'],
        'bookpic' => $_POST['bookpic'] ?? $book['bookpic'],
        'typeof' => $_POST['typeof'] ?? $book['typeof'],
    ];

    // Según tipo, agrega campos
    if ($_POST['typeof'] === 'Venta' || $_POST['typeof'] === 'Subasta') {
        $update_data['price'] = $_POST['price'] ?? $book['price'];
    } else {
        $update_data['price'] = null;
    }
    if ($_POST['typeof'] === 'Subasta') {
        $update_data['limdate'] = $_POST['limdate'] ?? $book['limdate'];
    } else {
        $update_data['limdate'] = null;
    }

    $update_data['fechalibro'] = $_POST['fechalibro'] ?? $book['fechalibro'];

    $result = updateBook($book['id'], $update_data);
    if ($result) {
        $_SESSION['edit_message'] = "¡Libro actualizado correctamente!";
    } else {
        $_SESSION['edit_error'] = "Error al actualizar el libro.";
    }
    // Redirige sin parámetro edit para salir del modo edición
    header("Location: pickedbook.php?id=" . $book['id']);
    exit();
}

// Procesar eliminación del libro por propietario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_owner && isset($_POST['delete_book'])) {
    $deleted = deleteBook($book['id']);
    if ($deleted) {
        $_SESSION['edit_message'] = "¡Libro eliminado correctamente!";
        header("Location: user.php");
        exit();
    } else {
        $_SESSION['edit_error'] = "Error al eliminar el libro.";
        header("Location: pickedbook.php?id=" . $book['id']);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del libro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/icon.png" type="image/png">

    <style>
        html {
            background: white;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'HovesDemiBold';
        }

        nav {
            position: fixed;
            max-width: 1440px;
            min-width: 200px;
            width: fit-content;
            height: auto;
            background-color: #08083069;
            backdrop-filter: blur(8px);
            display: inline-flex;
            justify-content: center;
            align-items: stretch;
            box-sizing: border-box;
            left: 0;
            right: 0;
            margin: auto;
            border: 1px solid rgba(99, 99, 99, 0.37);
            border-radius: 1rem;
            font-size: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            z-index: 5;
        }

        nav a {
            box-sizing: border-box;
            margin-inline: auto;
            inset-inline: 0;
            width: fit-content;
            padding: .2rem .5rem;
            margin: .3rem .3rem .3rem .3rem;
            border: 1px solid rgba(99, 99, 99, 0.6);
            backdrop-filter: blur(5px);
            background-color: #d8d8d888;
            border-radius: .6rem;
            color: #333333;
            text-decoration: none;
            min-width: 140px;
            overflow: hidden;
            max-width: 18%;
            max-height: 30px;

            .content {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                text-align: center;
            }

        }

        /* Cel */
        @media (max-width: 750px) {

            nav {
                position: static;
                display: flex;
                margin-top: 30px;
                flex-direction: column;
                font-size: 13px;
                border-radius: 5px;
                padding: 2px 0;
                width: 80%;
                align-items: center;

                a {
                    margin: .1rem;
                    padding: 2px 10px;
                    width: 98%;
                    height: 35px;
                    border-radius: 5px;
                    display: flex;
                    justify-content: center;
                    align-items: stretch;
                    max-width: 100%;
                    min-height: 30px;
                }

            }

        }
    </style>

</head>

<body>

    <nav>
        <a href="index.php" class="image-logo">
            <div class="content">LEEYA</div>
        </a>

        <?php if ($is_logged_in && $_SESSION['user_role'] === 'user'):

            $pending_counts = getPendingProposalsCount($_SESSION['user_id']);
            $total_pending = $pending_counts['sent'] + $pending_counts['received'];
            $badge_text = $total_pending > 9 ? '+9' : ($total_pending > 0 ? $total_pending : '');
            ?>

            <a href="explore.php">
                <div class="content">EXPLORAR</div>
            </a>

            <a href="newbook.php" class="plus">
                <div class="content">+</div>
            </a>

            <a class="circle1" href="myproposals.php">
                <svg class="esuve1" width="256px" height="256px" viewBox="0 0 24.00 24.00" fill="none"
                    xmlns="http://www.w3.org/2000/svg" stroke="" stroke-width="0.00024000000000000003">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#e6e6e6"
                        stroke-width="2.496">
                        <path
                            d="M11.713 7.14977C12.1271 7.13953 12.4545 6.79555 12.4443 6.38146C12.434 5.96738 12.0901 5.63999 11.676 5.65023L11.713 7.14977ZM6.30665 12.193H7.05665C7.05665 12.1874 7.05659 12.1818 7.05646 12.1761L6.30665 12.193ZM6.30665 14.51L6.34575 15.259C6.74423 15.2382 7.05665 14.909 7.05665 14.51H6.30665ZM6.30665 17.6L6.26755 18.349C6.28057 18.3497 6.29361 18.35 6.30665 18.35L6.30665 17.6ZM9.41983 18.35C9.83404 18.35 10.1698 18.0142 10.1698 17.6C10.1698 17.1858 9.83404 16.85 9.41983 16.85V18.35ZM10.9445 6.4C10.9445 6.81421 11.2803 7.15 11.6945 7.15C12.1087 7.15 12.4445 6.81421 12.4445 6.4H10.9445ZM12.4445 4C12.4445 3.58579 12.1087 3.25 11.6945 3.25C11.2803 3.25 10.9445 3.58579 10.9445 4H12.4445ZM11.713 5.65023C11.299 5.63999 10.955 5.96738 10.9447 6.38146C10.9345 6.79555 11.2619 7.13953 11.676 7.14977L11.713 5.65023ZM17.0824 12.193L16.3325 12.1761C16.3324 12.1818 16.3324 12.1874 16.3324 12.193H17.0824ZM17.0824 14.51H16.3324C16.3324 14.909 16.6448 15.2382 17.0433 15.259L17.0824 14.51ZM17.0824 17.6V18.35C17.0954 18.35 17.1084 18.3497 17.1215 18.349L17.0824 17.6ZM13.9692 16.85C13.555 16.85 13.2192 17.1858 13.2192 17.6C13.2192 18.0142 13.555 18.35 13.9692 18.35V16.85ZM10.1688 17.6027C10.1703 17.1885 9.83574 16.8515 9.42153 16.85C9.00732 16.8485 8.67034 17.1831 8.66886 17.5973L10.1688 17.6027ZM10.0848 19.3L10.6322 18.7873L10.6309 18.786L10.0848 19.3ZM13.3023 19.3L12.7561 18.786L12.7549 18.7873L13.3023 19.3ZM14.7182 17.5973C14.7167 17.1831 14.3797 16.8485 13.9655 16.85C13.5513 16.8515 13.2167 17.1885 13.2182 17.6027L14.7182 17.5973ZM9.41788 16.85C9.00366 16.85 8.66788 17.1858 8.66788 17.6C8.66788 18.0142 9.00366 18.35 9.41788 18.35V16.85ZM13.9692 18.35C14.3834 18.35 14.7192 18.0142 14.7192 17.6C14.7192 17.1858 14.3834 16.85 13.9692 16.85V18.35ZM11.676 5.65023C8.198 5.73622 5.47765 8.68931 5.55684 12.2099L7.05646 12.1761C6.99506 9.44664 9.09735 7.21444 11.713 7.14977L11.676 5.65023ZM5.55665 12.193V14.51H7.05665V12.193H5.55665ZM6.26755 13.761C5.0505 13.8246 4.125 14.8488 4.125 16.055H5.625C5.625 15.6136 5.95844 15.2792 6.34575 15.259L6.26755 13.761ZM4.125 16.055C4.125 17.2612 5.0505 18.2854 6.26755 18.349L6.34575 16.851C5.95843 16.8308 5.625 16.4964 5.625 16.055H4.125ZM6.30665 18.35H9.41983V16.85H6.30665V18.35ZM12.4445 6.4V4H10.9445V6.4H12.4445ZM11.676 7.14977C14.2917 7.21444 16.3939 9.44664 16.3325 12.1761L17.8322 12.2099C17.9114 8.68931 15.191 5.73622 11.713 5.65023L11.676 7.14977ZM16.3324 12.193V14.51H17.8324V12.193H16.3324ZM17.0433 15.259C17.4306 15.2792 17.764 15.6136 17.764 16.055H19.264C19.264 14.8488 18.3385 13.8246 17.1215 13.761L17.0433 15.259ZM17.764 16.055C17.764 16.4964 17.4306 16.8308 17.0433 16.851L17.1215 18.349C18.3385 18.2854 19.264 17.2612 19.264 16.055H17.764ZM17.0824 16.85H13.9692V18.35H17.0824V16.85ZM8.66886 17.5973C8.66592 18.4207 8.976 19.2162 9.53861 19.814L10.6309 18.786C10.335 18.4715 10.1673 18.0473 10.1688 17.6027L8.66886 17.5973ZM9.53739 19.8127C10.0977 20.4109 10.8758 20.7529 11.6935 20.7529V19.2529C11.2969 19.2529 10.9132 19.0873 10.6322 18.7873L9.53739 19.8127ZM11.6935 20.7529C12.5113 20.7529 13.2894 20.4109 13.8497 19.8127L12.7549 18.7873C12.4739 19.0873 12.0901 19.2529 11.6935 19.2529V20.7529ZM13.8484 19.814C14.4111 19.2162 14.7211 18.4207 14.7182 17.5973L13.2182 17.6027C13.2198 18.0473 13.0521 18.4715 12.7561 18.786L13.8484 19.814ZM9.41788 18.35H13.9692V16.85H9.41788V18.35Z"
                            fill="#333333"></path>
                    </g>
                    <g id="SVGRepo_iconCarrier">
                        <path
                            d="M11.713 7.14977C12.1271 7.13953 12.4545 6.79555 12.4443 6.38146C12.434 5.96738 12.0901 5.63999 11.676 5.65023L11.713 7.14977ZM6.30665 12.193H7.05665C7.05665 12.1874 7.05659 12.1818 7.05646 12.1761L6.30665 12.193ZM6.30665 14.51L6.34575 15.259C6.74423 15.2382 7.05665 14.909 7.05665 14.51H6.30665ZM6.30665 17.6L6.26755 18.349C6.28057 18.3497 6.29361 18.35 6.30665 18.35L6.30665 17.6ZM9.41983 18.35C9.83404 18.35 10.1698 18.0142 10.1698 17.6C10.1698 17.1858 9.83404 16.85 9.41983 16.85V18.35ZM10.9445 6.4C10.9445 6.81421 11.2803 7.15 11.6945 7.15C12.1087 7.15 12.4445 6.81421 12.4445 6.4H10.9445ZM12.4445 4C12.4445 3.58579 12.1087 3.25 11.6945 3.25C11.2803 3.25 10.9445 3.58579 10.9445 4H12.4445ZM11.713 5.65023C11.299 5.63999 10.955 5.96738 10.9447 6.38146C10.9345 6.79555 11.2619 7.13953 11.676 7.14977L11.713 5.65023ZM17.0824 12.193L16.3325 12.1761C16.3324 12.1818 16.3324 12.1874 16.3324 12.193H17.0824ZM17.0824 14.51H16.3324C16.3324 14.909 16.6448 15.2382 17.0433 15.259L17.0824 14.51ZM17.0824 17.6V18.35C17.0954 18.35 17.1084 18.3497 17.1215 18.349L17.0824 17.6ZM13.9692 16.85C13.555 16.85 13.2192 17.1858 13.2192 17.6C13.2192 18.0142 13.555 18.35 13.9692 18.35V16.85ZM10.1688 17.6027C10.1703 17.1885 9.83574 16.8515 9.42153 16.85C9.00732 16.8485 8.67034 17.1831 8.66886 17.5973L10.1688 17.6027ZM10.0848 19.3L10.6322 18.7873L10.6309 18.786L10.0848 19.3ZM13.3023 19.3L12.7561 18.786L12.7549 18.7873L13.3023 19.3ZM14.7182 17.5973C14.7167 17.1831 14.3797 16.8485 13.9655 16.85C13.5513 16.8515 13.2167 17.1885 13.2182 17.6027L14.7182 17.5973ZM9.41788 16.85C9.00366 16.85 8.66788 17.1858 8.66788 17.6C8.66788 18.0142 9.00366 18.35 9.41788 18.35V16.85ZM13.9692 18.35C14.3834 18.35 14.7192 18.0142 14.7192 17.6C14.7192 17.1858 14.3834 16.85 13.9692 16.85V18.35ZM11.676 5.65023C8.198 5.73622 5.47765 8.68931 5.55684 12.2099L7.05646 12.1761C6.99506 9.44664 9.09735 7.21444 11.713 7.14977L11.676 5.65023ZM5.55665 12.193V14.51H7.05665V12.193H5.55665ZM6.26755 13.761C5.0505 13.8246 4.125 14.8488 4.125 16.055H5.625C5.625 15.6136 5.95844 15.2792 6.34575 15.259L6.26755 13.761ZM4.125 16.055C4.125 17.2612 5.0505 18.2854 6.26755 18.349L6.34575 16.851C5.95843 16.8308 5.625 16.4964 5.625 16.055H4.125ZM6.30665 18.35H9.41983V16.85H6.30665V18.35ZM12.4445 6.4V4H10.9445V6.4H12.4445ZM11.676 7.14977C14.2917 7.21444 16.3939 9.44664 16.3325 12.1761L17.8322 12.2099C17.9114 8.68931 15.191 5.73622 11.713 5.65023L11.676 7.14977ZM16.3324 12.193V14.51H17.8324V12.193H16.3324ZM17.0433 15.259C17.4306 15.2792 17.764 15.6136 17.764 16.055H19.264C19.264 14.8488 18.3385 13.8246 17.1215 13.761L17.0433 15.259ZM17.764 16.055C17.764 16.4964 17.4306 16.8308 17.0433 16.851L17.1215 18.349C18.3385 18.2854 19.264 17.2612 19.264 16.055H17.764ZM17.0824 16.85H13.9692V18.35H17.0824V16.85ZM8.66886 17.5973C8.66592 18.4207 8.976 19.2162 9.53861 19.814L10.6309 18.786C10.335 18.4715 10.1673 18.0473 10.1688 17.6027L8.66886 17.5973ZM9.53739 19.8127C10.0977 20.4109 10.8758 20.7529 11.6935 20.7529V19.2529C11.2969 19.2529 10.9132 19.0873 10.6322 18.7873L9.53739 19.8127ZM11.6935 20.7529C12.5113 20.7529 13.2894 20.4109 13.8497 19.8127L12.7549 18.7873C12.4739 19.0873 12.0901 19.2529 11.6935 19.2529V20.7529ZM13.8484 19.814C14.4111 19.2162 14.7211 18.4207 14.7182 17.5973L13.2182 17.6027C13.2198 18.0473 13.0521 18.4715 12.7561 18.786L13.8484 19.814ZM9.41788 18.35H13.9692V16.85H9.41788V18.35Z"
                            fill="#333333"></path>
                    </g>
                </svg>
                <?php if ($badge_text): ?>
                    <span class="numnoti">
                        <p>
                            <?= $badge_text ?>
                        </p>
                    </span>
                <?php endif; ?>
            </a>

            <a class="circle2" href="user.php">
                <svg class="esuve2" width="256px" height="256px" viewBox="0 0 28.00 28.00" fill="none"
                    xmlns="http://www.w3.org/2000/svg" stroke="#333333" stroke-width="0.14">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#e6e6e6"
                        stroke-width="2.632">
                        <path clip-rule="evenodd"
                            d="M13.9991 2C10.6405 2 7.88924 4.6739 7.88924 8.00723C7.88924 10.1497 9.02582 12.0197 10.7297 13.0825C5.95609 14.5248 2.41965 19.0144 2.00617 24.0771C1.91662 25.1735 2.81571 26 3.81688 26H24.1831C25.1843 26 26.0834 25.1735 25.9938 24.0771C25.5803 19.014 22.0433 14.524 17.2691 13.0821C18.9726 12.0193 20.109 10.1494 20.109 8.00723C20.109 4.6739 17.3577 2 13.9991 2ZM9.74071 8.00723C9.74071 5.72598 11.6315 3.84838 13.9991 3.84838C16.3667 3.84838 18.2575 5.72598 18.2575 8.00723C18.2575 10.2885 16.3667 12.1661 13.9991 12.1661C11.6315 12.1661 9.74071 10.2885 9.74071 8.00723ZM4.95086 24.1516C4.36361 24.1516 3.89887 23.6462 4.01091 23.0697C4.94115 18.2837 9.09806 14.4476 14 14.4476C18.902 14.4476 23.0589 18.2837 23.9891 23.0697C24.1011 23.6462 23.6364 24.1516 23.0492 24.1516H4.95086Z"
                            fill="#333333" fill-rule="evenodd"></path>
                    </g>
                    <g id="SVGRepo_iconCarrier">
                        <path clip-rule="evenodd"
                            d="M13.9991 2C10.6405 2 7.88924 4.6739 7.88924 8.00723C7.88924 10.1497 9.02582 12.0197 10.7297 13.0825C5.95609 14.5248 2.41965 19.0144 2.00617 24.0771C1.91662 25.1735 2.81571 26 3.81688 26H24.1831C25.1843 26 26.0834 25.1735 25.9938 24.0771C25.5803 19.014 22.0433 14.524 17.2691 13.0821C18.9726 12.0193 20.109 10.1494 20.109 8.00723C20.109 4.6739 17.3577 2 13.9991 2ZM9.74071 8.00723C9.74071 5.72598 11.6315 3.84838 13.9991 3.84838C16.3667 3.84838 18.2575 5.72598 18.2575 8.00723C18.2575 10.2885 16.3667 12.1661 13.9991 12.1661C11.6315 12.1661 9.74071 10.2885 9.74071 8.00723ZM4.95086 24.1516C4.36361 24.1516 3.89887 23.6462 4.01091 23.0697C4.94115 18.2837 9.09806 14.4476 14 14.4476C18.902 14.4476 23.0589 18.2837 23.9891 23.0697C24.1011 23.6462 23.6364 24.1516 23.0492 24.1516H4.95086Z"
                            fill="#333333" fill-rule="evenodd"></path>
                    </g>
                </svg>
            </a>

        <?php elseif ($is_logged_in && $_SESSION['user_role'] === 'admin'): ?>

            <a href="userlist.php" class="image-logo">
                <div class="content">USUARIOS</div>
            </a>
            <a href="reports.php" class="image-logo">
                <div class="content">REPORTES</div>
            </a>
            <a href="logout.php" class="image-logo">
                <div class="content">CERRAR SESIÓN</div>
            </a>

        <?php endif; ?>
    </nav>

    <style>
        .circle1 {
            min-width: 25px;
            max-width: 30px;
            width: 100%;
            height: auto;
            padding: 0;
            display: flex;
            position: relative;
            align-items: center;
            justify-content: center;
            border: none;
            background-color: #d8d8d888;
            border: 1px solid rgba(99, 99, 99, 0.6);

            .esuve {
                height: 100%;
                width: auto;
                max-height: 100%;
            }

            .numnoti {
                position: absolute;
                margin: auto;
                padding: 3px 1px 0 0;
                color: #202020;
                font-size: clamp(.4rem, 1.2vh, .6rem);
            }
        }

        .circle2 {
            min-width: 25px;
            max-width: 30px;
            width: 100%;
            height: auto;
            padding: 0;
            display: flex;
            position: relative;
            align-items: center;
            justify-content: center;
            border: none;
            background-color: #d8d8d888;
            border: 1px solid rgba(99, 99, 99, 0.6);

            .esuve2 {
                height: 88%;
                width: auto;
                max-height: 100%;
            }
        }

        /* Cel */
        @media (max-width: 750px) {

            .circle1 {
                height: auto;
                min-width: 97%;
                position: relative;
                padding: 0;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(99, 99, 99, 0.37);
                background-color: #d8d8d881;
                border-radius: 5px;
            }

            .circle1 .esuve1 {
                max-width: 8%;
            }

            .circle2 {
                min-width: 97%;
                width: auto;
                position: relative;
                padding: 0;
                border: none;
                display: flex;
                background-color: transparent;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(99, 99, 99, 0.37);
                background-color: #d8d8d881;
                border-radius: 5px;

            }

            .circle2 .esuve2 {
                max-width: 8%;
                width: auto;
                margin: 0 auto;
            }
        }
    </style>

    </nav>

    <style>
        main {
            max-width: 1440px;
            min-width: 200px;
            width: 100%;
            height: auto;
            display: flex;
            flex-direction: column;
            margin: 2.8rem auto 0 auto;
            padding: clamp(3rem, 11.5vh, 5rem) 0 0 0;
            justify-content: center;
            align-items: center;
        }

        @media(max-width: 750px) {

            main {
                flex-direction: column;
                margin: 2rem auto 0 auto;
                width: 95%;
                height: auto;
                padding: 0;
            }
        }

        .bigcontainer {
            width: 94%;
            min-height: 500px;
            height: 100%;
            display: flex;
            flex-direction: row;
            justify-content: stretch;
            align-items: stretch;
            align-content: stretch;
            box-sizing: border-box;
            flex-wrap: wrap;
            justify-items: stretch;
            gap: clamp(1.2rem, 5vh, 3.5rem);
            padding: clamp(.6rem, 2vh, 1rem);
            margin: 0 auto clamp(2rem, 6vh, 3.8rem) auto;
            border: 1px solid rgba(99, 99, 99, 0.37);
            border-radius: clamp(10px, 4vh, 18px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            background-color: #64646425;
            backdrop-filter: blur(8px);
        }

        .left-controls {
            flex: 1 1 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-sizing: border-box;
            justify-items: center;
            align-items: center;
            align-content: center;
            backdrop-filter: blur(5px);
            overflow: hidden;
            border-radius: clamp(10px, 4vh, 18px);
            border: 1px solid rgba(99, 99, 99, 0.37);
            padding: clamp(1rem, 3vh, 3rem);
            background-color: #d8d8d888;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.10);
            min-height: auto;
        }

        .rightcontrols {
            flex: 1 1 340px;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(8px);
            box-sizing: border-box;
            margin: 0;
            border-radius: clamp(10px, 4vh, 18px);
            border: 1px solid rgba(99, 99, 99, 0.37);
            background-color: #64646425;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            min-height: auto;
            padding: clamp(.5rem, 2vh, 1rem);
            color: #333333;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .imagebook {
            width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: clamp(10px, 4vh, 18px);
            overflow: hidden;
            box-shadow: 0 2px 2px rgba(0, 0, 0, 0.08);
            max-height: 600px;
            flex: 1 1 120px;
            height: auto;

            img {
                height: auto;
                width: 100%;
                margin: 0 auto;
            }

        }

        .buttons-left {
            flex-shrink: 0;
            width: 100%;
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            justify-content: space-evenly;
            align-items: center;
            margin-top: clamp(1rem, 2vh, 1.5rem);
            gap: clamp(0.6rem, 1.5vh, 1rem);

            form:first-child {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;

                button {
                    width: 100%;
                    padding: clamp(0.5rem, 1.5vh, 0.8rem);
                    border-radius: clamp(5px, 2vh, 8px);
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    font-family: 'HovesDemiBold';
                    font-size: clamp(12px, 2vh, 14px);
                    color: #333333;
                    box-shadow: 0 4px 4px rgba(0, 0, 0, 0.05);
                    cursor: pointer;
                    background-color: #08083069;
                }
            }

            form:last-child {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;

                button {
                    width: 100%;
                    padding: clamp(0.5rem, 1.5vh, 0.8rem);
                    border-radius: clamp(5px, 2vh, 8px);
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    font-family: 'HovesDemiBold';
                    font-size: clamp(12px, 2vh, 14px);
                    color: #333333;
                    box-shadow: 0 4px 4px rgba(0, 0, 0, 0.05);
                    cursor: pointer;
                    background-color: #08083069;
                }
            }
    </style>

    <main>

        <div class="bigcontainer">

            <div class="left-controls">

                <div class="imagebook">
                    <img src="<?= htmlspecialchars($book['bookpic']) ?>" alt="Imagen del libro">
                </div>
                <div class="buttons-left">
                    <?php if ($is_owner && !$edit_mode): ?>
                        <form method="get">
                            <input type="hidden" name="id" value="<?= $book['id'] ?>">
                            <button type="submit" name="edit" value="1" class="functions">EDITAR</button>
                        </form>

                        <form method="post">
                            <input type="hidden" name="delete_book" value="1">
                            <button type="submit" class="functions btn-cancel"
                                onclick="return confirm('¿Seguro que deseas eliminar este libro?');">ELIMINAR</button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>

            <style>
                .mensajescaja {
                    width: 100%;
                    min-height: auto;
                    background-color: #d8d8d888;
                    border-bottom: 1px solid rgba(99, 99, 99, 0.37);
                    border-radius: clamp(10px, 4vh, 15px);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    backdrop-filter: blur(8px);
                    box-sizing: border-box;
                    padding: clamp(0.5rem, 2vh, 1.2rem);
                    flex-shrink: 0;
                }

                .datosedicion {
                    padding: clamp(.6rem, 6vh, 1.2rem) clamp(.6rem, 6vh, 1.2rem) clamp(.4rem, 2vh, .8rem) clamp(.6rem, 6vh, 1.2rem);
                    width: 100%;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: flex;
                    flex-direction: column;
                    box-sizing: border-box;
                    flex-shrink: 0;
                    color: #333333;

                    h2 {
                        overflow: hidden;
                        text-overflow: ellipsis;
                        margin: 0;
                        padding: 0;
                        padding-bottom: clamp(.4rem, 2vh, .8rem);
                    }

                    p {
                        overflow: hidden;
                        text-overflow: ellipsis;
                        margin: 0;
                        padding: 0;
                        padding-bottom: clamp(.3rem, 4vh, .6rem);
                    }
                }

                .formulario {
                    width: 95%;
                    display: flex;
                    flex-direction: column;
                    flex-wrap: nowrap;
                    justify-content: flex-start;
                    align-items: center;
                    box-sizing: border-box;
                    margin: 0 auto;
                    gap: 16px;
                    max-height: none;
                    overflow-y: auto;

                }

                .form-group {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    flex-wrap: nowrap;
                    align-items: center;
                    justify-content: flex-start;
                    flex-shrink: 0;

                    label {
                        text-align: start;
                        align-self: flex-start;
                        color: #303030;
                        margin: 0 0 5px 10px;
                    }
                }

                .form-control {
                    width: 96%;
                    min-height: 30px;
                    border: 1px solid rgba(99, 99, 99, 0.71);
                    border-radius: 10px;
                    background-color: #ffffffbb;
                    backdrop-filter: blur(12px);
                    box-sizing: border-box;
                    padding: 0 2rem 0 1rem;
                    font-family: 'HovesDemiBold';
                    color: #333333;
                    resize: vertical;
                }

                textarea.form-control {
                    min-height: 80px;
                    max-height: 150px;
                }

                select {
                    border: 1px solid rgba(99, 99, 99, 0.71);
                    font-family: 'HovesDemiBold';
                    color: #333333;
                    padding: 0 1rem 0 1rem;
                    width: 95%;
                    min-height: 35px;
                    box-sizing: border-box;
                    font-size: clamp(.8rem, 1.1vw, 1.4rem);
                    background-color: #ffffffbb;
                    backdrop-filter: blur(12px);
                    border-radius: clamp(10px, 1.5vw, 20px);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    height: 40px;
                }

                .editarboton {
                    border: 1px solid rgba(99, 99, 99, 0.71);
                    text-decoration: none;
                    width: 70%;
                    font-size: clamp(.8rem, 1vw, 1.4rem);
                    text-align: center;
                    border-radius: clamp(10px, 1.5vw, 20px);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    margin: clamp(.4rem, 2.6vh, 1.6rem) 0 clamp(1rem, 4vh, 3rem) 0;
                    padding: 0.5rem 1rem;
                    cursor: pointer;
                    flex-shrink: 0;
                    font-family: 'HovesDemiBold';
                    color: #333333;
                    background-color: #ffffffbb;
                    min-height: 35px;

                    a {
                        text-decoration: none;
                        color: #333333;
                        font-family: 'HovesDemiBold';
                    }

                    a::visited {
                        text-decoration: none;
                        color: #333333;
                        font-family: 'HovesDemiBold';
                    }
                }

                .ownernoedit {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    gap: clamp(0.6rem, 1.5vh, 1.2rem);
                    flex-shrink: 0;
                    font-family: 'HovesDemiBold';

                    h2 {
                        margin: 0 0 clamp(0.8rem, 2vh, 1.2rem) 0;
                        padding: 0;
                    }

                    p {
                        margin: clamp(0.3rem, 1vh, 0.6rem) 0;
                        padding: 0;
                        word-wrap: break-word;
                        overflow-wrap: break-word;
                    }
                }

                .buyerview {
                    width: 100%;
                    margin-bottom: clamp(.2rem, 1vh, .4rem);
                    display: flex;
                    flex-direction: column;
                    gap: clamp(0.8rem, 2vh, 1rem);
                    justify-content: center;
                    align-items: center;

                    >div {
                        display: flex;
                        flex-direction: column;
                        align-items: stretch;
                        align-content: stretch;
                        width: 90%;
                        gap: clamp(0.6rem, 1.5vh, 1rem);
                        overflow: hidden;
                    }

                    form {
                        display: flex;
                        flex-direction: column;
                        gap: clamp(0.6rem, 1.5vh, 1rem);
                        width: 90%;
                    }

                    input[type="number"],
                    input[type="text"] {
                        width: 100%;
                        min-height: 45px;
                        border: 1px solid rgba(99, 99, 99, 0.71);
                        border-radius: 10px;
                        background-color: #ffffffbb;
                        backdrop-filter: blur(12px);
                        box-sizing: border-box;
                        padding: 0 1rem;
                        font-family: 'HovesDemiBold';
                        color: #333333;
                        font-size: clamp(.8rem, 4vh, 1rem);
                    }

                    .formulariomol {
                        width: 100%;
                    }

                    button,
                    .functions {
                        width: 100%;
                        padding: 0.4rem 1rem;
                        min-height: 25px;
                        border: 1px solid rgba(99, 99, 99, 0.37);
                        border-radius: 8px;
                        background-color: #d8d8d888;
                        font-family: 'HovesDemiBold';
                        color: #333333;
                        text-decoration: none;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        box-sizing: border-box;
                        transition: all 0.2s ease;
                        font-size: clamp(.8rem, 4vh, 1rem);
                    }

                    .functions {
                        text-align: center;
                    }
                }

                .exchange-list {
                    display: flex;
                    flex-direction: column;
                    gap: clamp(0.4rem, 1vh, 0.8rem);
                    width: 100%;
                    margin: 0;
                    max-height: 45px;
                    overflow: auto;
                    justify-self: flex-start;
                }

                .exchange-item {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }

                .proposal-message,
                .proposal-error {
                    padding: clamp(0.6rem, 1.5vh, 1rem);
                    border-radius: 8px;
                    font-weight: bold;
                    text-align: center;
                    width: 100%;
                    box-sizing: border-box;
                }

                .proposal-message {
                    background-color: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }

                .proposal-error {
                    background-color: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
            </style>

            <div class="rightcontrols">
                <div class="mensajescaja">
                    <?php if ($edit_message): ?>
                        <div class="proposal-message"><?= htmlspecialchars($edit_message) ?></div>
                    <?php endif; ?>
                    <?php if ($edit_error): ?>
                        <div class="proposal-error"><?= htmlspecialchars($edit_error) ?></div>
                    <?php endif; ?>
                    <?php if ($proposal_message): ?>
                        <div class="proposal-message"><?= htmlspecialchars($proposal_message) ?></div>
                    <?php endif; ?>
                    <?php if ($proposal_error): ?>
                        <div class="proposal-error"><?= htmlspecialchars($proposal_error) ?></div>
                    <?php endif; ?>
                    <?php if ($admin_message): ?>
                        <div class="proposal-message"><?= htmlspecialchars($admin_message) ?></div>
                    <?php endif; ?>
                </div>

                <div class="datosedicion">
                    <?php if ($is_owner && $edit_mode): ?>
                        <form method="post" class="formulario">
                            <input type="hidden" name="edit_book" value="1">

                            <div class="form-group">
                                <label>Título:</label>
                                <input class="form-control" type="text" name="name"
                                    value="<?= htmlspecialchars($book['name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Autor:</label>
                                <input class="form-control" type="text" name="author"
                                    value="<?= htmlspecialchars($book['author']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Editorial:</label>
                                <input class="form-control" type="text" name="editorial"
                                    value="<?= htmlspecialchars($book['editorial']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Género:</label>
                                <input class="form-control" type="text" name="genre"
                                    value="<?= htmlspecialchars($book['genre']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Descripción:</label>
                                <textarea class="form-control" name="description"
                                    required><?= htmlspecialchars($book['description']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Estado (0-5):</label>
                                <input class="form-control" type="number" name="qstatus" min="0" max="5"
                                    value="<?= htmlspecialchars($book['qstatus']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Imagen (URL):</label>
                                <input class="form-control" type="text" name="bookpic"
                                    value="<?= htmlspecialchars($book['bookpic']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Tipo de operación:</label>
                                <select name="typeof" required>
                                    <option value="Donacion" <?= $book['typeof'] == 'Donacion' ? 'selected' : ''; ?>>
                                        Donación
                                    </option>
                                    <option value="Venta" <?= $book['typeof'] == 'Venta' ? 'selected' : ''; ?>>Venta
                                    </option>
                                    <option value="Intercambio" <?= $book['typeof'] == 'Intercambio' ? 'selected' : ''; ?>>
                                        Intercambio
                                    </option>
                                    <option value="Subasta" <?= $book['typeof'] == 'Subasta' ? 'selected' : ''; ?>>Subasta
                                    </option>
                                </select>
                            </div>


                            <div class="form-group">
                                <?php if ($book['typeof'] === 'Venta' || $book['typeof'] === 'Subasta'): ?>
                                    <label>Precio:</label>
                                    <input class="form-control" type="number" name="price" min="0" step="any"
                                        value="<?= htmlspecialchars($book['price']) ?>">
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <?php if ($book['typeof'] === 'Subasta'): ?>
                                    <label>Fecha límite de subasta:</label>
                                    <input type="date" name="limdate" value="<?= htmlspecialchars($book['limdate']) ?>">
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Fecha de publicación original:</label>
                                <input class="form-control" type="date" name="fechalibro"
                                    value="<?= htmlspecialchars($book['fechalibro']) ?>">
                            </div>

                            <button class="editarboton" type="submit" class="functions">GUARDAR</button>
                        </form>
                    </div>

                    <div class="ownernoedit">

                    <?php else: ?>
                        <h2><?= htmlspecialchars($book['name']) ?></h2>
                        <p><b>Autor:</b> <?= htmlspecialchars($book['author']) ?></p>
                        <p><b>Editorial:</b> <?= htmlspecialchars($book['editorial']) ?></p>
                        <p><b>Género:</b> <?= htmlspecialchars($book['genre']) ?></p>
                        <p><b>Descripción:</b> <?= htmlspecialchars($book['description']) ?></p>
                        <p><b>Estado:</b>
                            <?php
                            $stars = '';
                            for ($i = 0; $i < 5; $i++) {
                                $stars .= $i < intval($book['qstatus']) ? '⭐' : ' ☆ ';
                            }
                            echo $stars;
                            ?>
                        </p>
                        <p><b>Tipo:</b> <?= htmlspecialchars($book['typeof']) ?></p>
                        <?php if ($book['typeof'] === 'Subasta' && !empty($book['limdate'])): ?>
                            <p><b>Fecha límite de subasta:</b> <?= htmlspecialchars($book['limdate']) ?></p>
                        <?php endif; ?>
                        <?php if ($book['price'] !== null): ?>
                            <p><b>Precio:</b> $<?= htmlspecialchars($book['price']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($book['fechapubli'])): ?>
                            <p><b>Fecha de publicación en plataforma:</b> <?= htmlspecialchars($book['fechapubli']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($book['fechalibro'])): ?>
                            <p><b>Fecha de publicación original:</b> <?= htmlspecialchars($book['fechalibro']) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>


                <div class="buyerview">

                    <?php if (!$is_owner && $is_logged_in && $_SESSION['user_role'] !== 'admin'): ?>


                        <div>

                            <div>
                                <b>Publicación realizada por:</b>
                                <?= htmlspecialchars($book_owner['name']) ?>
                            </div>

                            <div>
                                <a href="https://outlook.office.com/mail/deeplink/compose?to=<?= urlencode($book_owner['email']) ?>&subject=Consulta%20sobre%20libro&body=Hola,%20estoy%20interesado%20en%20el%20libro"
                                    target="_blank" class="functions">
                                    Contactar vendedor
                                </a>
                            </div>

                            <div>
                                <a href="pickeduser.php?id=<?= $book['ownerid'] ?>" class="functions">
                                    Ver perfil del vendedor
                                </a>
                            </div>

                        </div>

                        <?php if ($book['typeof'] === 'Donacion'): ?>
                            <form method="post">
                                <button type="submit" class="functions">Solicitar donación</button>
                            </form>
                        <?php elseif ($book['typeof'] === 'Venta'): ?>
                            <form method="post">
                                <input type="number" name="amount" min="1" step="any" placeholder="Monto a ofrecer" required>
                                <button type="submit" class="functions">Ofertar</button>
                            </form>
                        <?php elseif ($book['typeof'] === 'Intercambio'): ?>
                            <?php if (empty($user_books)): ?>
                                <div class="proposal-error">No dispones de libros publicados para hacer un intercambio.</div>
                            <?php else: ?>
                                <form method="post">
                                    <label>Selecciona tus libros para intercambiar:</label>
                                    <div class="exchange-list">
                                        <?php foreach ($user_books as $ubook): ?>
                                            <div class="exchange-item">
                                                <input type="checkbox" id="book<?= $ubook['id'] ?>" name="offered_books[]"
                                                    value="<?= $ubook['id'] ?>">
                                                <label for="book<?= $ubook['id'] ?>">
                                                    <b><?= htmlspecialchars($ubook['name']) ?></b>
                                                    (<?= htmlspecialchars($ubook['author']) ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="submit" class="functions">Proponer intercambio</button>
                                </form>
                            <?php endif; ?>
                        <?php elseif ($book['typeof'] === 'Subasta'): ?>
                            <form method="post">
                                <input type="number" name="amount" min="<?= floatval($book['price']) + 1 ?>" step="any"
                                    placeholder="Monto a pujar" required>
                                <button type="submit" class="functions">Pujar</button>
                            </form>

                        <?php endif; ?>
                    <?php elseif (isAdmin() && !$is_owner): ?>
                        <div>
                            <form method="post" class="formulariomol">
                                <input type="hidden" name="admin_delete_book" value="1">
                                <button type="submit" class="functions"
                                    onclick="return confirm('¿Seguro que deseas eliminar esta publicación?');">Eliminar
                                    publicación</button>
                            </form>
                            <a href="pickeduser.php?id=<?= $book['ownerid'] ?>" class="functions">
                                Ver perfil del propietario
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

    </main>
</body>

</html>