<?php

session_start();

require_once __DIR__ . '/../src/auth_functions.php';
require_once __DIR__ . '/../src/database.php';

$is_logged_in = false;
$user_role = '';

refreshSessionUser();
updateExpiredAuctions();

if (isLoggedIn()) {

    if (isset($_SESSION['user_id'])) {
        $is_logged_in = true;
        $user_name = htmlspecialchars($_SESSION['user_name'] ?? '');
        $user_role = htmlspecialchars($_SESSION['user_role'] ?? 'user');
    }

}

if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'banned') {
        header('Location: banned.php');
        exit();
    }
}

// Require login to view user profiles
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}


$current_user_id = $_SESSION['user_id'] ?? null;

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    header('Location: index.php');
    exit();
}

$user = getUserById($user_id);
if (!$user) {
    header('Location: index.php');
    exit();
}

// Si el usuario está banneado y no es admin, redirigir
if ($user['userrole'] === 'banned' && $user_role !== 'admin') {
    header('Location: index.php');
    exit();
}

$rates = getUserRates($user_id);

// Obtener libros disponibles del usuario
$user_books = getBooksByUserId($user_id);
$available_books = array_filter($user_books, function($book) {
    return $book['status'] === true;
});

$rate_message = '';
$rate_error = '';
$report_message = '';
$report_error = '';
$ban_message = '';

// Procesar rate, report y ban solo en POST, luego redirigir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    if (isset($_POST['rate_user'])) {
        $stars = intval($_POST['stars'] ?? 0);
        $description = trim($_POST['rate_description'] ?? '');
        if ($stars < 1 || $stars > 5 || $description === '') {
            $_SESSION['rate_error'] = 'Debes ingresar estrellas (1-5) y una descripción.';
        } else {
            $result = createUserRate($current_user_id, $user_id, $stars, $description);
            $_SESSION['rate_message'] = $result ? '¡Reseña enviada!' : 'Error al enviar la reseña.';
        }
        header("Location: pickeduser.php?id=" . $user_id);
        exit();
    }
    if (isset($_POST['report_user'])) {
        $motive = $_POST['motive'] ?? '';
        $description = trim($_POST['report_description'] ?? '');
        if ($motive === '' || $description === '') {
            $_SESSION['report_error'] = 'Debes seleccionar un motivo y escribir una descripción.';
        } else {
            $result = createUserReport($current_user_id, $user_id, $motive, $description);
            $_SESSION['report_message'] = $result ? '¡Reporte enviado!' : 'Error al enviar el reporte.';
        }
        header("Location: pickeduser.php?id=" . $user_id);
        exit();
    }
    if (isset($_POST['ban_user'])) {
        if ($user_role === 'admin' && $current_user_id != $user_id) {
            $result = banUser($user_id);
            $_SESSION['ban_message'] = $result['message'];
        }
        header("Location: pickeduser.php?id=" . $user_id);
        exit();
    }
    if (isset($_POST['unban_user'])) {
        if ($user_role === 'admin' && $current_user_id != $user_id) {
            $result = unbanUser($user_id);
            $_SESSION['ban_message'] = $result['message'];
        }
        header("Location: pickeduser.php?id=" . $user_id);
        exit();
    }
}

// Mensajes POST/REDIRECT/GET
if (isset($_SESSION['rate_message'])) {
    $rate_message = $_SESSION['rate_message'];
    unset($_SESSION['rate_message']);
}
if (isset($_SESSION['rate_error'])) {
    $rate_error = $_SESSION['rate_error'];
    unset($_SESSION['rate_error']);
}
if (isset($_SESSION['report_message'])) {
    $report_message = $_SESSION['report_message'];
    unset($_SESSION['report_message']);
}
if (isset($_SESSION['report_error'])) {
    $report_error = $_SESSION['report_error'];
    unset($_SESSION['report_error']);
}
if (isset($_SESSION['ban_message'])) {
    $ban_message = $_SESSION['ban_message'];
    unset($_SESSION['ban_message']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuario</title>
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
            background: white;
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

        <a href="explore.php">
            <div class="content">EXPLORAR</div>
        </a>
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

<body>

    <style>
        main {
            max-width: 1440px;
            min-width: 200px;
            width: 96%;
            height: auto;
            display: flex;
            flex-direction: column;
            margin: 2.8rem auto 0 auto;
            padding: 2rem 0 0 0;
            justify-content: center;
            align-items: center;
        }

        .dataUser {
            width: 100%;
            display: flex;
            flex-direction: column;
            flex-wrap: nowrap;
            align-items: flex-start;
            justify-content: flex-start;
            margin: 0;
            box-sizing: border-box;
            padding: 2rem;
            border-radius: 10px;
            border: 1px solid rgba(99, 99, 99, 0.37);
            background-color: #d8d8d888;
            backdrop-filter: blur(80px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            color: #333333;
            gap: 0.8rem;
            margin-top: clamp(1.4rem, 6vh, 2.4rem);

            h1 {
                margin: 0 0 0.5rem 0;
                font-size: 18px;
                width: 100%;
            }

            p {
                display: block;
                margin: 0;
                padding: 0;
                width: 100%;
                font-size: 14px;
            }

            .since {
                margin-bottom: 0;
                font-family: 'HovesDemiBoldItalic';
                font-size: 13px;
            }

            a {
                color: #222222;
                text-decoration: none;
                font-size: 16px;
            }

        }

        .error-message {
            background: rgba(255, 238, 238, 0.64);
            color: #c53030af;
            backdrop-filter: blur(5px);
            padding: 0.2rem 1.5rem 0.2rem 1.5rem;
            box-sizing: border-box;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid #fed7d7;
            width: 90%;
            margin: clamp(.6rem, 3vh, 1.2rem) auto;
        }

        .success-message {
            background: rgba(200, 215, 255, 0.64);
            color: #0819b6af;
            backdrop-filter: blur(5px);
            padding: 0.2rem 1.5rem 0.2rem 1.5rem;
            box-sizing: border-box;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid #d3dbff;
            width: 90%;
            margin: clamp(.6rem, 3vh, 1.2rem) auto;
        }
    </style>

    <main>

        <div class="dataUser">
            <h1 class="welcome"><?= htmlspecialchars($user['name']) ?></h1>
            <p class="since">Usuario activo desde: <?= htmlspecialchars(explode(' ', $user['signdate'])[0]) ?></p>
            <p class="infotext">Correo electrónico de contacto: <?= htmlspecialchars($user['email']) ?></p>
            <p class="infotext">Ubicación: <?= htmlspecialchars($user['location']) ?></p>
            <?php if ($user['lildescription'] == ''): ?>
                <p class="infotextfinal">Aún no cuenta con una descripción</p>
            <?php else: ?>
                <p class="infotextfinal">Descripción: <?= htmlspecialchars($user['lildescription']) ?></p>
            <?php endif; ?>

            <a href="https://outlook.office.com/mail/deeplink/compose?to=<?= urlencode($user['email']) ?>&subject=Consulta&body=Hola,%20estoy%20interesado%20en%20el%20libro"
                target="_blank">
                Contactar
            </a>
        </div>

        <style>
            .resenas {
                width: 100%;
                background-color: #d8d8d888;
                border: 1px solid rgba(99, 99, 99, 0.37);
                backdrop-filter: blur(5px);
                border-radius: .4rem;
                margin: clamp(1.6rem, 4vh, 2rem) auto;

                h2 {
                    margin: clamp(1.6rem, 5vh, 2.6rem) auto;
                    padding: 0;
                    text-align: center;
                    color: #15152e;
                }

                .resenaslista {
                    width: 94%;
                    color: #333333;
                    box-sizing: border-box;
                    padding-inline: clamp(1.2rem, 4vh, 2.4rem);
                    margin: 0 auto clamp(.6rem, 3vh, 1.2rem) auto;
                    overflow: hidden;
                    border-bottom: 1px solid rgba(99, 99, 99, 0.37);
                }

                .resenaescribir {
                    width: 95%;
                    margin: clamp(.6rem, 4vh, 1.4rem) auto clamp(1.4rem, 5vh, 2.2rem) auto;
                    background-color: rgba(216, 216, 216, 0.3);
                    border: 1px solid rgba(99, 99, 99, 0.25);
                    border-radius: 0.8rem;
                    padding: clamp(1rem, 3vh, 1.8rem);
                    box-sizing: border-box;

                    form {
                        width: 100%;
                        display: flex;
                        flex-direction: column;
                        gap: clamp(0.8rem, 2vh, 1.2rem);

                        h3 {
                            margin: 0;
                            padding: 0;
                            text-align: center;
                            color: #333333;
                            font-size: clamp(1rem, 3vw, 1.3rem);
                        }

                        label {
                            color: #333333;
                            font-size: 0.95rem;
                            font-weight: bold;
                            display: block;
                            margin-bottom: 0.3rem;
                        }

                        select,
                        textarea {
                            width: 100%;
                            padding: 0.7rem 0.8rem;
                            border: 1px solid rgba(99, 99, 99, 0.37);
                            border-radius: 0.5rem;
                            background-color: #f5f5f5;
                            color: #333333;
                            font-family: 'HovesDemiBold';
                            font-size: 0.9rem;
                            box-sizing: border-box;
                            transition: all 0.3s ease;
                        }

                        select:focus,
                        textarea:focus {
                            outline: none;
                            background-color: #ffffff;
                            border-color: #0819b6;
                            box-shadow: 0 0 0 3px rgba(8, 25, 182, 0.1);
                        }

                        textarea {
                            resize: vertical;
                            min-height: 80px;
                        }

                        button {
                            align-self: flex-end;
                            width: 100%;
                            padding: clamp(0.6rem, 1.5vh, 0.9rem) 1.5rem;
                            font-size: clamp(.7rem, 6vh, .9rem);
                            font-family: 'HovesDemiBold';
                            color: #333333;
                            border: 1px solid rgba(99, 99, 99, 0.37);
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                            border-radius: clamp(.5rem, 8vh, 1.2rem);
                            background-color: #08083069;
                        }

                    }
                }
            }

            .report-container {
                width: 100%;
                background-color: #d8d8d888;
                border: 1px solid rgba(99, 99, 99, 0.37);
                backdrop-filter: blur(5px);
                border-radius: .4rem;
                margin: clamp(1.6rem, 4vh, 2rem) auto;
                padding: clamp(1rem, 3vh, 1.8rem);
                box-sizing: border-box;

                h2 {
                    margin: 0 0 clamp(1rem, 3vh, 1.6rem) 0;
                    padding: 0;
                    text-align: center;
                    color: #15152e;
                }

                form {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    gap: clamp(0.8rem, 2vh, 1.2rem);
                    background-color: rgba(216, 216, 216, 0.3);
                    border: 1px solid rgba(99, 99, 99, 0.25);
                    border-radius: 0.8rem;
                    padding: clamp(1rem, 3vh, 1.8rem);
                    box-sizing: border-box;

                    label {
                        color: #333333;
                        font-size: 0.95rem;
                        font-weight: bold;
                        display: block;
                        margin-bottom: 0.3rem;
                    }

                    select,
                    textarea {
                        width: 100%;
                        padding: 0.7rem 0.8rem;
                        border: 1px solid rgba(99, 99, 99, 0.37);
                        border-radius: 0.5rem;
                        background-color: #f5f5f5;
                        color: #333333;
                        font-family: 'HovesDemiBold';
                        font-size: 0.9rem;
                        box-sizing: border-box;
                        transition: all 0.3s ease;
                    }

                    select:focus,
                    textarea:focus {
                        outline: none;
                        background-color: #ffffff;
                        border-color: #0819b6;
                        box-shadow: 0 0 0 3px rgba(8, 25, 182, 0.1);
                    }

                    textarea {
                        resize: vertical;
                        min-height: 80px;
                    }

                    button {
                        align-self: flex-end;
                        width: 100%;
                        padding: clamp(0.6rem, 1.5vh, 0.9rem) 1.5rem;
                        font-size: clamp(.7rem, 6vh, .9rem);
                        font-family: 'HovesDemiBold';
                        color: #333333;
                        border: 1px solid rgba(99, 99, 99, 0.37);
                        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                        border-radius: clamp(.5rem, 8vh, 1.2rem);
                        background-color: #08083069;
                    }
                }
            }

            .admin-container {
                width: 100%;
                background-color: #d8d8d888;
                border: 1px solid rgba(99, 99, 99, 0.37);
                backdrop-filter: blur(5px);
                border-radius: .4rem;
                margin: clamp(1.6rem, 4vh, 2rem) auto;
                padding: clamp(1.5rem, 4vh, 2.2rem);
                box-sizing: border-box;

                h2 {
                    margin: 0 0 clamp(1rem, 3vh, 1.6rem) 0;
                    padding: 0;
                    text-align: center;
                    color: #15152e;
                }

                .admin-info {
                    background-color: rgba(216, 216, 216, 0.3);
                    border-radius: 0.8rem;
                    padding: clamp(1rem, 3vh, 1.8rem);
                    margin-bottom: clamp(1rem, 3vh, 1.6rem);
                    box-sizing: border-box;

                    p {
                        margin: 0 0 clamp(0.8rem, 2vh, 1.2rem) 0;
                        color: #333333;
                        font-size: 0.95rem;
                    }

                    form {
                        display: flex;
                        flex-direction: column;
                        gap: clamp(0.6rem, 1.5vh, 1rem);
                        border: none;

                        button {
                            width: 100%;
                            padding: clamp(0.6rem, 1.5vh, 0.9rem) 1.5rem;
                            font-size: clamp(.7rem, 6vh, .9rem);
                            font-family: 'HovesDemiBold';
                            color: #333333;
                            border: 1px solid rgba(99, 99, 99, 0.37);
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                            border-radius: clamp(.5rem, 8vh, 1.2rem);
                            background-color: #08083069;
                            cursor: pointer;
                        }
                    }
                }
            }

            @media (max-width: 750px) {

                .resenaescribir,
                .report-container form,
                .admin-container .admin-info {
                    width: 100%;
                }

                .resenaescribir form button,
                .report-container form button,
                .admin-container form button {
                    align-self: center;
                    width: 100%;
                }

            }
        </style>

        <div class="resenas">
            <h2>Reseñas del usuario</h2>
            <?php if ($rate_message): ?>
                <div class="success-message"><?= htmlspecialchars($rate_message) ?></div>
            <?php endif; ?>
            <?php if ($rate_error): ?>
                <div class="error-message"><?= htmlspecialchars($rate_error) ?></div>
            <?php endif; ?>
            <?php if (empty($rates)): ?>
                <div class="error-message">Este usuario aún no tiene reseñas.</div>
            <?php else: ?>
                <?php foreach ($rates as $r): ?>
                    <div class="resenaslista">
                        <b><?= htmlspecialchars($r['sender_name']) ?></b>
                        <span>
                            <?php for ($i = 0; $i < intval($r['rating']); $i++)
                                echo ' ★ '; ?>
                            <?php for ($i = intval($r['rating']); $i < 5; $i++)
                                echo ' ☆ '; ?>
                        </span>
                        <span> | <?= htmlspecialchars($r['ratedate']) ?></span>
                        <p><?= htmlspecialchars($r['commentary']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="report-container">
            <?php if ($user_role !== 'admin'): ?>
                <h2>Reportar usuario</h2>
                <?php if ($report_message): ?>
                    <div class="success-message"><?= htmlspecialchars($report_message) ?></div>
                <?php endif; ?>
                <?php if ($report_error): ?>
                    <div class="error-message"><?= htmlspecialchars($report_error) ?></div>
                <?php endif; ?>
                <?php if ($is_logged_in && $current_user_id != $user_id): ?>
                    <form method="post">
                        <div>
                            <label for="motive">Motivo:</label>
                            <select name="motive" id="motive" required>
                                <option value="">Selecciona</option>
                                <option>Estafa o fraude</option>
                                <option>Producto falso o réplica</option>
                                <option>Suplantación de identidad</option>
                                <option>Información o fotos falsas</option>
                                <option>Lenguaje ofensivo o acoso</option>
                                <option>Venta de productos prohibidos</option>
                                <option>Contenido inapropiado o ilegal</option>
                                <option>Solicitud de datos personales</option>
                                <option>Phishing o enlaces maliciosos</option>
                                <option>Incumplimiento en la entrega</option>
                            </select>
                        </div>
                        <div>
                            <label for="report_description">Descripción:</label>
                            <textarea name="report_description" id="report_description" rows="2" required></textarea>
                        </div>
                        <button type="submit" name="report_user" class="btn-cancel">Enviar reporte</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <div class="admin-container">
                    <h2>Gestión administrativa</h2>
                    <?php if ($ban_message): ?>
                        <div class="success-message"><?= htmlspecialchars($ban_message) ?></div>
                    <?php endif; ?>
                    <?php if ($current_user_id != $user_id): ?>
                        <div class="admin-info">
                            <p>Como administrador, puedes gestionar este usuario.</p>
                            <form method="post">
                                <?php if ($user['userrole'] === 'banned'): ?>
                                    <button type="submit" name="unban_user" class="btn-save">Desbannear usuario</button>
                                <?php else: ?>
                                    <button type="submit" name="ban_user" class="btn-cancel">Bannear usuario</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
            .catalogo-usuario {
                width: 100%;
                box-sizing: border-box;
                margin-top: clamp(1.4rem, 6vh, 2.4rem);
                margin-bottom: clamp(1.4rem, 6vh, 2.4rem);

                >h2 {
                    padding: clamp(.4rem, 4vh, .6rem) 0 clamp(1.4rem, 4vh, 2.2rem) 0;
                    margin: 0;
                    margin: 0 auto;
                    text-align: center;
                    color: #15152e;
                }

                .bookbox-container {
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    background-color: #d8d8d888;
                    backdrop-filter: blur(8px);
                    width: 100%;
                    padding: clamp(.8rem, 2vw, 2.2rem);
                    display: flex;
                    flex-wrap: wrap;
                    gap: clamp(.8rem, 4vh, 1.5rem);
                    border-radius: clamp(1rem, 1.5vw, 2rem);
                    box-sizing: border-box;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                }

                .fullbook {
                    flex: 1 1 280px;
                    background-color: #d8d8d888;
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    box-sizing: border-box;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    align-items: center;
                    border-radius: clamp(15px, 1.8vw, 22px);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    padding: clamp(10px, 1.8vw, 15px);
                    text-decoration: none;
                    color: #333333;
                    transition: all 0.3s ease;
                    cursor: pointer;

                    &:hover {
                        background-color: #c9c9c999;
                        transform: translateY(-4px);
                    }

                    .bookbox {
                        width: 100%;
                        height: 150px;
                        background-color: white;
                        border: 1px solid rgba(99, 99, 99, 0.2);
                        border-radius: 8px;
                        overflow: hidden;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-bottom: 10px;

                        img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                        }
                    }

                    .infolibro {
                        width: 100%;
                        display: flex;
                        flex-direction: column;
                        text-align: center;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        gap: 4px;

                        h3 {
                            margin: 0;
                            padding: 0;
                            font-size: clamp(.85rem, 2vh, 1rem);
                            overflow: hidden;
                            text-overflow: ellipsis;
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            -webkit-box-orient: vertical;
                        }

                        p {
                            margin: 0;
                            padding: 0;
                            font-size: clamp(.75rem, 1.5vh, .85rem);
                            overflow: hidden;
                            text-overflow: ellipsis;
                            color: #666666;
                        }

                        span {
                            font-size: clamp(.7rem, 1.2vh, .8rem);
                            color: #888888;
                        }
                    }
                }

                .mensaje-vacio {
                    color: #333333;
                    text-align: center;
                    font-size: clamp(1rem, 3vh, 1.2rem);
                }
            }
        </style>

        <div class="catalogo-usuario">
            <h2>Catálogo de <?= htmlspecialchars($user['name']) ?></h2>
            <div class="bookbox-container">
                <?php if (empty($available_books)): ?>
                    <p class="mensaje-vacio">Este usuario no ha registrado libros</p>
                <?php else: ?>
                    <?php foreach ($available_books as $book): ?>
                        <a href="pickedbook.php?id=<?= $book['id'] ?>" class="fullbook">
                            <div class="bookbox">
                                <img src="<?= htmlspecialchars($book['bookpic']) ?>" alt="<?= htmlspecialchars($book['name']) ?>">
                            </div>
                            <div class="infolibro">
                                <h3><?= htmlspecialchars($book['name']) ?></h3>
                                <p><strong><?= htmlspecialchars($book['author']) ?></strong></p>
                                <span><?= htmlspecialchars($book['typeof']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>