<?php

session_start();

require_once __DIR__ . '/../src/auth_functions.php';
require_once __DIR__ . '/../src/database.php';

$is_logged_in = false;
$user_role = '';

refreshSessionUser();

if (isLoggedIn()) {

    if (isset($_SESSION['user_id'])) {
        $is_logged_in = true;
        $user_name = htmlspecialchars($_SESSION['user_name'] ?? '');
        $user_role = htmlspecialchars($_SESSION['user_role'] ?? 'user');
    }

}

if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header('Location: adminpanel.php');
        exit();
    } elseif (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'banned') {
        header('Location: banned.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $ownerid = $_SESSION['user_id'];
    $name = $_POST['name'] ?? '';
    $author = $_POST['author'] ?? '';
    $genre = $_POST['genero'] ?? '';
    $editorial = $_POST['editorial'] ?? '';
    $description = $_POST['description'] ?? '';
    $qstatus = $_POST['status'] ?? 0;
    $bookpic = $_POST['imagen'] ?? '';
    $typeof = $_POST['trx'] ?? '';
    $status = 1; // Disponible al ser recién publicado
    $price = isset($_POST['monto']) && $_POST['monto'] !== '' ? $_POST['monto'] : null;

    $limdate = isset($_POST['fecha']) && $_POST['fecha'] !== '' ? $_POST['fecha'] : null;

    if (empty($name) || empty($author) || empty($genre) || empty($editorial) || empty($description) || empty($bookpic) || empty($typeof) || $qstatus === '') {
        $error = 'Completa todos los campos obligatorios.';
    } elseif ($typeof === "Subasta" && !$limdate) {
        $error = 'Debes ingresar una fecha límite para la subasta.';
    } else {
        $result = createBook($ownerid, $name, $author, $genre, $editorial, $description, $qstatus, $bookpic, $typeof, $status, $price, $limdate);
        if ($result['success']) {
            $_SESSION['newbook_message'] = $result['message'];
            header('Location: newbook.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// --- Y agrega esto donde inicializa $message ---
$message = '';
if (isset($_SESSION['newbook_message'])) {
    $message = $_SESSION['newbook_message'];
    unset($_SESSION['newbook_message']);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar libro</title>
    <link rel="icon" href="img/icon.png">
    <link rel="stylesheet" href="style.css">
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

<body>

    <nav>

        <a href="index.php" class="image-logo">
            <div class="content">LEEYA</div>
        </a>

        <?php if ($is_logged_in):

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

        <?php elseif (!$is_logged_in): ?>

            <a href="login.php">
                <div class="content">INICIAR SESIÓN</div>
            </a>

        <?php endif; ?>

        <?php if ($is_logged_in): ?>

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

            <style>
                .book-form {
                    width: 90%
                }

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


        <?php endif; ?>

    </nav>

    <style>
        main {
            max-width: 1440px;
            min-width: 200px;
            width: 95%;
            height: auto;
            display: flex;
            flex-direction: column;
            margin: 2.8rem auto 0 auto;
            padding: 1rem 0 0 0;
            justify-content: center;
            align-items: center;
        }
    </style>

    <main>

        <style>
            .form-group {
                width: 100%;
                display: flex;
                flex-direction: column;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: start;
                text-overflow: ellipsis;
                height: auto;
                max-height: 120px;
                box-sizing: border-box;

                label {
                    text-align: start;
                    align-self: flex-start;
                    color: #303030;
                    margin: 0 0 0 10px;
                    text-overflow: ellipsis;
                    overflow: auto;
                }

                input {
                    width: 96%;
                    height: 35px;
                    border: 1px solid rgba(99, 99, 99, 0.71);
                    border-radius: 10px;
                    background-color: #ffffffbb;
                    backdrop-filter: blur(12px);
                    padding-right: 40px;
                    box-sizing: border-box;
                    padding: 0 2rem 0 1rem;
                    font-family: 'HovesDemiBold';
                    color: #333333;
                    margin-bottom: clamp(5px, 2vh, 10px);
                }

                select {
                    background-color: transparent;
                    border: 1px solid #333333;
                    font-family: 'HovesDemiBold';
                    color: #333333;
                    padding: 0 1rem 0 1rem;
                    border: 1px solid rgba(99, 99, 99, 0.71);
                    background-color: #ffffffbb;
                    backdrop-filter: blur(12px);
                    height: 35px;
                    margin-bottom: clamp(5px, 2vh, 10px);
                    width: 96%;
                    border-radius: 10px;
                }
            }

            .form-whole {
                margin-bottom: clamp(2rem, 12vh, 2.8rem);
                width: 98%;
                display: flex;
                flex-wrap: wrap;
                border: 1px solid rgba(99, 99, 99, 0.37);
                background-color: #d8d8d888;
                backdrop-filter: blur(8px);
                padding: clamp(.8rem, 2vw, 2.2rem);
                gap: clamp(.8rem, 4vh, 1.5rem);
                border-radius: clamp(1rem, 1.5vw, 2rem);
                box-sizing: border-box;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                margin-top: clamp(2.8rem, 8vh, 3.2rem);
            }

            .bookinfo {
                background-color: yellow;
                flex: 1 1 350px;
                background-color: #d8d8d888;
                border: 1px solid rgba(99, 99, 99, 0.37);
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                align-items: center;
                max-width: 100%;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                padding: clamp(8px, 1.8vw, 25px);
                border-radius: clamp(15px, 1.8vw, 22px);
                color: #333333;

                h2 {
                    margin: clamp(.7rem, 5vh, 1.2rem) auto;
                    display: flex;
                    justify-content: center;
                    padding: 0;
                    width: 90%;
                    border-bottom: 1px solid rgba(99, 99, 99, 0.37);
                    color: #15152e;
                }
            }

            .bookpic {
                flex: 1 1 400px;
                max-width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                background-color: #d8d8d888;
                border: 1px solid rgba(99, 99, 99, 0.37);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                border-radius: clamp(15px, 1.8vw, 22px);
                box-sizing: border-box;
                justify-content: center;

                .preview-text {
                    padding: clamp(1rem, 1.8vw, 1.5rem);
                    width: 92%;
                    border-bottom: 1px solid rgba(99, 99, 99, 0.37);
                    display: flex;
                    justify-content: start;
                    flex-direction: column;
                    color: #333333;
                    align-items: center;
                    margin: 0;
                    box-sizing: border-box;

                    p {
                        width: 100%;
                        max-height: 22px;
                        height: 100%;
                        padding: clamp(8px, 1.8vw, 15px);
                        display: flex;
                        align-items: flex-start;
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    p:last-child {
                        justify-content: center;
                    }

                }

                .realpic {
                    width: 90%;
                    height: 320px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    box-sizing: border-box;
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    display: flex;
                    justify-content: center;
                    border-radius: clamp(15px, 1.8vw, 22px);
                    overflow: hidden;
                    margin-top: clamp(1rem, 10vh, 1.5rem);
                    margin-bottom: clamp(1rem, 10vh, 1.5rem);

                    img {
                        height: 100%;
                        width: auto;
                        max-width: 100%;
                        overflow: hidden;
                    }


                }

                .messages-back {
                    justify-items: flex-start;
                    width: 90%;
                    display: flex;
                    align-items: flex-start;
                    margin-bottom: clamp(1rem, 10vh, 1.5rem);
                }

            }

            .error-message {
                background: rgba(255, 238, 238, 0.64);
                color: #c53030af;
                backdrop-filter: blur(5px);
                box-sizing: border-box;
                border-radius: 8px;
                font-size: 0.9rem;
                border: 1px solid #fed7d7;
                margin: 0 auto;
                display: flex;
                align-items: flex-start;
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
                margin: 0 auto;
                display: flex;
                align-items: flex-start;
            }

            .form-buttons {
                width: 88%;
                display: flex;
                flex-wrap: wrap;
                gap: clamp(.4rem, 3vh, .8rem);
                margin: 0 auto;
                margin: clamp(1rem, 10vh, 1.5rem) auto;

                .auth-button {
                    display: flex;
                    align-items: center;
                    align-content: center;
                    justify-content: center;
                    flex: 1 1 150px;

                    max-width: 100%;
                    background-color: #08083069;
                    backdrop-filter: blur(5px);
                    padding: 1%;
                    border: none;
                    border: 1px solid rgba(99, 99, 99, 0.71);
                    border-radius: 10px;
                    color: #333333;
                    font-family: "HovesDemiBold";
                    font-size: clamp(.6rem, 3vh, .9rem);
                    cursor: pointer;
                }
            }
        </style>

        <div class="form-whole">

            <div class="bookinfo"> <!-- Caja izquierda -->

                <h2>Publica tu libro</h2>

                <form class="book-form" method="POST" action="">
                    <div class="form-group">
                        <label for="name">Título del libro</label>
                        <input type="text" id="name" name="name" placeholder="Ej: Cien años de soledad" required>
                    </div>

                    <div class="form-group">
                        <label for="author">Autor</label>
                        <input type="text" id="author" name="author" placeholder="Ej: Gabriel García Márquez" required>
                    </div>

                    <div class="form-group">
                        <label for="genre">Descripcion</label>
                        <input type="text" id="description" name="description" placeholder="Describe tu publicacion"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="editorial">Editorial</label>
                        <input type="text" id="editorial" name="editorial" placeholder="Ej: Panamericana" required>
                    </div>

                    <div class="form-group">
                        <label for="editorial">Imagen del libro</label>
                        <input type="text" id="imagen" name="imagen" placeholder="Ingresa el link de tu imagen"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="editorial">Genero</label>
                        <select id="genero" name="genero" required>
                            <option value="">Selecciona un genero</option>
                            <option value="Novela">Novela</option>
                            <option value="Ficción">Ficción</option>
                            <option value="Terror">Terror</option>
                            <option value="Misterio">Misterio</option>
                            <option value="Crimen">Crimen</option>
                            <option value="Literatura infantil">Literatura infantil</option>
                            <option value="Biografía">Biografía</option>
                            <option value="Historia">Historia</option>
                            <option value="Filosofía">Filosofía</option>
                            <option value="Psicología">Psicología</option>
                            <option value="Desarrollo personal">Desarrollo personal</option>
                            <option value="Espiritualidad">Espiritualidad</option>
                            <option value="Política">Política</option>
                            <option value="Economía">Economía</option>
                            <option value="Poesía">Poesía</option>
                            <option value="Teatro">Teatro</option>
                            <option value="Cuento">Cuento</option>
                            <option value="Divulgación científica">Divulgación científica</option>
                            <option value="Tecnología">Tecnología</option>
                            <option value="Novela gráfica">Novela gráfica</option>
                            <option value="Biografía">Biografía</option>
                            <option value="Ciencias básicas">Ciencias básicas</option>
                            <option value="Teoría musical">Teoría musical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Estado del libro</label>
                        <select id="status" name="status" required>
                            <option value="">Selecciona un estado</option>
                            <option value="0">0 - Muy deteriorado</option>
                            <option value="1">1 - Dañado</option>
                            <option value="2">2 - Regular</option>
                            <option value="3">3 - Bueno</option>
                            <option value="4">4 - Muy bueno</option>
                            <option value="5">5 - Como nuevo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="trx">Tipo de transaccion</label>
                        <select id="trx" name="trx" required>
                            <option value="">¿Que deseas realizar?</option>
                            <option value="Donacion">Donacion</option>
                            <option value="Venta">Venta</option>
                            <option value="Intercambio">Intercambio</option>
                            <option value="Subasta">Subasta</option>
                        </select>
                    </div>

                    <div class="form-group" id="monto-group" style="display: none;">
                        <label for="monto">Monto</label>
                        <input type="number" id="monto" name="monto" placeholder="Ingresa el monto del libro">
                    </div>

                    <div class="form-group" id="fecha-group" style="display: none;">
                        <label for="monto">Fecha limite</label>
                        <input type="date" id="fecha" name="fecha" placeholder="Fecha limite">
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="auth-button">GUARDAR</button>
                        <button type="reset" class="auth-button">LIMPIAR</button>
                    </div>
                </form>
            </div>

            <div class="bookpic"> <!-- Caja derecha -->

                <div class="preview-text"> <!-- Reservada para el texto de preview -->
                    <p></p>
                    <p></p>
                    <p></p>
                </div>

                <div class="realpic"> <!-- Reservada para la imagen del libro -->
                    <img src="" alt="Imagen del libro">
                </div>

                <div class="messages-back">
                    <?php if ($message): ?>
                        <div class="success-message"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="error-message"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

        <script>
            // --- Mostrar campo de monto según tipo de transacción ---
            const trxSelect = document.getElementById("trx");
            const montoGroup = document.getElementById("monto-group");
            const montoInput = document.getElementById("monto");
            const fechaGroup = document.getElementById("fecha-group");
            const fechaInput = document.getElementById("fecha");

            trxSelect.addEventListener("change", () => {
                const valor = trxSelect.value;
                if (valor === "Venta") {
                    montoGroup.style.display = "flex";
                    montoInput.required = true;
                    montoInput.placeholder = "Ingresa el monto";
                    fechaGroup.style.display = "none";
                    fechaInput.required = false;
                    fechaInput.value = "";
                } else if (valor === "Subasta") {
                    montoGroup.style.display = "flex";
                    montoInput.required = true;
                    montoInput.placeholder = "Ingresa el monto base";
                    fechaGroup.style.display = "flex";
                    fechaInput.required = true;
                } else {
                    montoGroup.style.display = "none";
                    montoInput.required = false;
                    montoInput.value = "";
                    fechaGroup.style.display = "none";
                    fechaInput.required = false;
                    fechaInput.value = "";
                }
            });

            // --- Actualizar vista previa en tiempo real ---
            const titleInput = document.getElementById("name");
            const authorInput = document.getElementById("author");
            const statusSelect = document.getElementById("status");
            const imageInput = document.getElementById("imagen");

            const previewTitle = document.querySelector(".preview-text p:nth-child(1)");
            const previewAuthor = document.querySelector(".preview-text p:nth-child(2)");
            const previewStatus = document.querySelector(".preview-text p:nth-child(3)");
            const previewImage = document.querySelector(".realpic img");

            // Función para convertir número en estrellas
            function getStars(num) {
                if (!num) return "—";
                num = parseInt(num);
                let stars = "";
                for (let i = 0; i < 5; i++) {
                    stars += i < num ? "⭐" : " ☆ ";
                }
                return stars;
            }

            // Función general para actualizar preview
            function updatePreview() {
                previewTitle.textContent = `Título: ${titleInput.value.trim() || "—"}`;
                previewAuthor.textContent = `Autor: ${authorInput.value.trim() || "—"}`;

                const stars = getStars(statusSelect.value);
                previewStatus.textContent = `Estado: ${stars}`;

                const link = imageInput.value.trim();
                previewImage.src = link
                    ? link
                    : "https://laud.udistrital.edu.co/sites/default/files/imagen-noticia/2022-09/laud-edificio-lectus-facultad-tecnologica-davidmoraconstructor%20%285%29.jpeg";
            }

            // Actualizar en cada cambio
            titleInput.addEventListener("input", updatePreview);
            authorInput.addEventListener("input", updatePreview);
            statusSelect.addEventListener("change", updatePreview);
            imageInput.addEventListener("input", updatePreview);

            // Inicializar al cargar la página
            updatePreview();
        </script>


    </main>

</body>

</html>