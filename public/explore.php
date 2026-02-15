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
} else {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/icon.png" type="image/png">
    <title>Encuentra tus libros</title>

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
    </style>

    <main>

        <?php
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $search_user = trim($_GET['search_user'] ?? '');
        $location = trim($_GET['location'] ?? '');
        $genre = trim($_GET['genre'] ?? '');
        $exclude_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

        // Obtener el rol del usuario actual (asumiendo que lo tienes en la sesión)
        $current_user_role = $is_logged_in ? ($_SESSION['user_role'] ?? 'user') : 'user';

        // Si se selecciona "Libros en mi localidad", usar la ubicación del usuario actual
        if ($location === 'mi_localidad' && $is_logged_in) {
            $location = $_SESSION['user_location'] ?? '';
        }

        // Libros filtrados
        $books = searchBooks($search, $type, $exclude_user_id, $current_user_role, $location, $genre);

        // Usuarios filtrados
        $users = [];
        if ($search_user !== '') {
            $users = searchUsers($search_user, $exclude_user_id);
        }
        ?>

        <style>
            .filtros {
                width: 92%;
                padding-top: clamp(3rem, 8vh, 4.2rem);
                padding-bottom: clamp(1.4rem, 6vh, 2.4rem);
                box-sizing: border-box;
            }

            .filtrolibros {
                width: 100%;
                box-sizing: border-box;
                margin-bottom: clamp(.8rem, 3vh, 1.4rem);

                form {
                    width: 100%;
                    display: flex;
                    flex-wrap: wrap;
                    flex-direction: row;
                    box-sizing: border-box;
                    gap: clamp(.6rem, 4vh, 1rem);

                    div:first-child {
                        display: flex;
                        flex: 1 1 280px;

                        input {
                            height: clamp(2rem, 8vh, 2.4rem);
                            width: 100%;
                        }
                    }

                    div:nth-child(2),
                    div:nth-child(3),
                    div:nth-child(4) {
                        display: flex;
                        flex: 1 1 200px;

                        select {
                            width: 100%;
                            font-size: clamp(.8rem, 2vh, 1.2rem);
                            font-family: 'HovesDemiBold';
                            color: #333333;
                            border: 1px solid rgba(99, 99, 99, 0.37);
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                            border-radius: clamp(.5rem, 8vh, 1.2rem);
                            padding: 0 2rem 0 1rem;
                            height: clamp(2rem, 8vh, 2.4rem);
                        }
                    }

                    div:last-child {
                        flex: 1 1 140px;
                        display: flex;

                        button {
                            width: 100%;
                            font-size: clamp(.8rem, 2vh, 1.2rem);
                            font-family: 'HovesDemiBold';
                            color: #333333;
                            border: 1px solid rgba(99, 99, 99, 0.37);
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                            border-radius: clamp(.5rem, 8vh, 1.2rem);
                            background-color: #08083069;
                            height: clamp(2rem, 8vh, 2.4rem);
                        }
                    }

                }

            }

            .filtrousuarios {
                width: 100%;
                box-sizing: border-box;
                gap: clamp(.6rem, 4vh, 1rem);

                form {
                    width: 100%;
                    display: flex;
                    flex-wrap: wrap;
                    flex-direction: row;
                    box-sizing: border-box;
                    gap: clamp(.6rem, 4vh, 1rem);

                    div:first-child {
                        display: flex;
                        flex: 1 1 450px;
                        width: 100%;

                        input {
                            height: clamp(2rem, 8vh, 2.4rem);
                            width: 100%;

                        }
                    }

                    div:last-child {
                        display: flex;
                        flex: 1 1 450px;
                        width: 100%;

                        button {
                            width: 100%;
                            font-size: clamp(.8rem, 2vh, 1.2rem);
                            font-family: 'HovesDemiBold';
                            color: #333333;
                            border: 1px solid rgba(99, 99, 99, 0.37);
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                            border-radius: clamp(.5rem, 8vh, 1.2rem);
                            background-color: #08083069;
                            height: clamp(2rem, 8vh, 2.4rem);
                        }
                    }
                }

            }

            .form-control {
                width: 100%;
                height: clamp(1.6rem, 8vh, 2.2rem);
                border: 1px solid rgba(99, 99, 99, 0.37);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                border-radius: clamp(.5rem, 8vh, 1.2rem);
                background-color: #d8d8d888;
                backdrop-filter: blur(5px);
                box-sizing: border-box;
                padding: 0 2rem 0 1rem;
                text-overflow: ellipsis;
                font-family: 'HovesDemiBold';
                color: #333333;
                font-size: clamp(.8rem, 2vh, 1.2rem);
            }
        </style>

        <div class="filtros">

            <div class="filtrolibros">
                <form method="get">
                    <div>
                        <input class="form-control" type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Buscar libro por título, autor o género...">
                    </div>
                    <div>
                        <select name="type" class="form-control">
                            <option value="">Tipo - Todos</option>
                            <option value="Donacion" <?= $type == 'Donacion' ? 'selected' : ''; ?>>Donación</option>
                            <option value="Venta" <?= $type == 'Venta' ? 'selected' : ''; ?>>Venta</option>
                            <option value="Intercambio" <?= $type == 'Intercambio' ? 'selected' : ''; ?>>Intercambio</option>
                            <option value="Subasta" <?= $type == 'Subasta' ? 'selected' : ''; ?>>Subasta</option>
                        </select>
                    </div>
                    <div>
                        <select name="location" class="form-control">
                            <option value="">Selecciona tu localidad</option>
                            <?php if ($is_logged_in): ?>
                            <option value="mi_localidad" <?= $location == ($_SESSION['user_location'] ?? '') && $location !== '' ? 'selected' : ''; ?>>Libros en mi localidad</option>
                            <?php endif; ?>
                            <option value="Usaquén" <?= $location == 'Usaquén' ? 'selected' : ''; ?>>Usaquén</option>
                            <option value="Chapinero" <?= $location == 'Chapinero' ? 'selected' : ''; ?>>Chapinero</option>
                            <option value="Santa Fe" <?= $location == 'Santa Fe' ? 'selected' : ''; ?>>Santa Fe</option>
                            <option value="San Cristóbal" <?= $location == 'San Cristóbal' ? 'selected' : ''; ?>>San Cristóbal</option>
                            <option value="Usme" <?= $location == 'Usme' ? 'selected' : ''; ?>>Usme</option>
                            <option value="Tunjuelito" <?= $location == 'Tunjuelito' ? 'selected' : ''; ?>>Tunjuelito</option>
                            <option value="Bosa" <?= $location == 'Bosa' ? 'selected' : ''; ?>>Bosa</option>
                            <option value="Kennedy" <?= $location == 'Kennedy' ? 'selected' : ''; ?>>Kennedy</option>
                            <option value="Fontibón" <?= $location == 'Fontibón' ? 'selected' : ''; ?>>Fontibón</option>
                            <option value="Engativá" <?= $location == 'Engativá' ? 'selected' : ''; ?>>Engativá</option>
                            <option value="Suba" <?= $location == 'Suba' ? 'selected' : ''; ?>>Suba</option>
                            <option value="Barrios Unidos" <?= $location == 'Barrios Unidos' ? 'selected' : ''; ?>>Barrios Unidos</option>
                            <option value="Teusaquillo" <?= $location == 'Teusaquillo' ? 'selected' : ''; ?>>Teusaquillo</option>
                            <option value="Los Mártires" <?= $location == 'Los Mártires' ? 'selected' : ''; ?>>Los Mártires</option>
                            <option value="Antonio Nariño" <?= $location == 'Antonio Nariño' ? 'selected' : ''; ?>>Antonio Nariño</option>
                            <option value="Puente Aranda" <?= $location == 'Puente Aranda' ? 'selected' : ''; ?>>Puente Aranda</option>
                            <option value="La Candelaria" <?= $location == 'La Candelaria' ? 'selected' : ''; ?>>La Candelaria</option>
                            <option value="Rafael Uribe Uribe" <?= $location == 'Rafael Uribe Uribe' ? 'selected' : ''; ?>>Rafael Uribe Uribe</option>
                            <option value="Ciudad Bolívar" <?= $location == 'Ciudad Bolívar' ? 'selected' : ''; ?>>Ciudad Bolívar</option>
                            <option value="Sumapaz" <?= $location == 'Sumapaz' ? 'selected' : ''; ?>>Sumapaz</option>
                            <option value="Fuera de Bogotá" <?= $location == 'Fuera de Bogotá' ? 'selected' : ''; ?>>Fuera de Bogotá</option>
                        </select>
                    </div>
                    <div>
                        <select name="genre" class="form-control">
                            <option value="">Selecciona un genero</option>
                            <option value="Novela" <?= $genre == 'Novela' ? 'selected' : ''; ?>>Novela</option>
                            <option value="Ficción" <?= $genre == 'Ficción' ? 'selected' : ''; ?>>Ficción</option>
                            <option value="Terror" <?= $genre == 'Terror' ? 'selected' : ''; ?>>Terror</option>
                            <option value="Misterio" <?= $genre == 'Misterio' ? 'selected' : ''; ?>>Misterio</option>
                            <option value="Crimen" <?= $genre == 'Crimen' ? 'selected' : ''; ?>>Crimen</option>
                            <option value="Literatura infantil" <?= $genre == 'Literatura infantil' ? 'selected' : ''; ?>>Literatura infantil</option>
                            <option value="Biografía" <?= $genre == 'Biografía' ? 'selected' : ''; ?>>Biografía</option>
                            <option value="Historia" <?= $genre == 'Historia' ? 'selected' : ''; ?>>Historia</option>
                            <option value="Filosofía" <?= $genre == 'Filosofía' ? 'selected' : ''; ?>>Filosofía</option>
                            <option value="Psicología" <?= $genre == 'Psicología' ? 'selected' : ''; ?>>Psicología</option>
                            <option value="Desarrollo personal" <?= $genre == 'Desarrollo personal' ? 'selected' : ''; ?>>Desarrollo personal</option>
                            <option value="Espiritualidad" <?= $genre == 'Espiritualidad' ? 'selected' : ''; ?>>Espiritualidad</option>
                            <option value="Política" <?= $genre == 'Política' ? 'selected' : ''; ?>>Política</option>
                            <option value="Economía" <?= $genre == 'Economía' ? 'selected' : ''; ?>>Economía</option>
                            <option value="Poesía" <?= $genre == 'Poesía' ? 'selected' : ''; ?>>Poesía</option>
                            <option value="Teatro" <?= $genre == 'Teatro' ? 'selected' : ''; ?>>Teatro</option>
                            <option value="Cuento" <?= $genre == 'Cuento' ? 'selected' : ''; ?>>Cuento</option>
                            <option value="Divulgación científica" <?= $genre == 'Divulgación científica' ? 'selected' : ''; ?>>Divulgación científica</option>
                            <option value="Tecnología" <?= $genre == 'Tecnología' ? 'selected' : ''; ?>>Tecnología</option>
                            <option value="Novela gráfica" <?= $genre == 'Novela gráfica' ? 'selected' : ''; ?>>Novela gráfica</option>
                            <option value="Ciencias básicas" <?= $genre == 'Ciencias básicas' ? 'selected' : ''; ?>>Ciencias básicas</option>
                            <option value="Teoría musical" <?= $genre == 'Teoría musical' ? 'selected' : ''; ?>>Teoría musical</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="form-control" style="background-color: #08083069; padding: 0; display: flex; align-items: center; justify-content: center; cursor: pointer;">BUSCAR LIBRO</button>
                    </div>
                </form>
            </div>

            <div class="filtrousuarios">
                <form method="get">
                    <div>
                        <input class="form-control" type="text" name="search_user"
                            value="<?= htmlspecialchars($search_user) ?>" placeholder="Buscar usuario por nombre...">
                    </div>
                    <div>
                        <button type="submit">BUSCAR USUARIO</button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            .resulusuarios {
                width: 100%;
                box-sizing: border-box;

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
                    flex: 1 1 380px;
                    background-color: #d8d8d888;
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    box-sizing: border-box;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    justify-items: center;
                    align-items: center;
                    border-radius: clamp(15px, 1.8vw, 22px);
                    align-items: center;
                    align-content: stretch;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    justify-items: center;
                    padding: clamp(15px, 1.8vw, 22px);

                    .bookbox {
                        width: 100%;
                        display: flex;
                        margin: 0 auto;
                        justify-content: center;
                        padding: 0;
                    }

                    .infolibro {
                        width: 100%;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: clamp(.2rem, 4vh, .4rem);

                        .TituloLibro {
                            color: #333333;
                            margin: 0;
                            padding: 0;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            max-height: 24px;
                            height: 100%;
                        }

                        p {
                            color: #333333;
                            margin: 0;
                            padding: 0;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }
                    }

                    .buttonscaja {
                        width: 100%;
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: center;
                        align-items: center;
                        margin-top: clamp(6px, 1.8vw, 12px);
                        gap: clamp(.2rem, 4vh, .4rem);
                        box-sizing: border-box;

                        .boton {
                            flex: 1 1 200px;
                            display: flex;
                            align-items: center;
                            align-content: center;
                            justify-content: center;
                            box-sizing: border-box;
                            height: clamp(2rem, 8vh, 2.4rem);

                            a {
                                display: flex;
                                width: 100%;
                                text-align: center;
                                color: #333333;
                                text-decoration: none;
                                box-sizing: border-box;
                                height: 100%;
                                align-items: center;
                                justify-content: center;
                                border: 1px solid rgba(99, 99, 99, 0.37);
                                backdrop-filter: blur(5px);
                                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                                border-radius: clamp(.5rem, 8vh, 1.2rem);
                                background-color: #08083069;

                            }

                            a:visited {
                                font-style: none;
                                color: #333333;
                                text-decoration: none;
                            }
                        }
                    }

                }
            }
        </style>

        <div class="resulusuarios">
            <?php if (!empty($users)): ?>
                <h2>Usuarios encontrados</h2>
                <div class="bookbox-container">
                    <?php foreach ($users as $user): ?>
                        <div class="fullbook">

                            <div class="bookbox"> <!-- SVG de usuario -->
                                <svg width="100px" height="100px" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" stroke="#333333">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier">
                                        <path
                                            d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z"
                                            stroke="#333333" stroke-width="2.136" stroke-linecap="round"
                                            stroke-linejoin="round"></path>
                                        <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="#333333"
                                            stroke-width="2.136" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </g>
                                </svg>
                            </div>

                            <div class="infolibro"> <!-- Datos del libro y botones -->
                                <h3 class="TituloLibro"><?= htmlspecialchars($user['name']) ?></h3>
                                <p><b>Ubicación:</b>
                                    <?= htmlspecialchars($user['location']) ?></p>
                            </div>

                            <div class="buttonscaja"> <!-- Botones del libro -->
                                <div class="boton">
                                    <a href="pickeduser.php?id=<?= $user['id'] ?>">VER PERFIL</a>
                                </div>
                                <div class="boton">
                                    <a href="https://outlook.office.com/mail/deeplink/compose?to=<?= urlencode($user['email']) ?>&subject=Consulta&body=Hola,%20estoy%20interesado%20en%20el%20libro"
                                        target="_blank">
                                        CONTACTAR
                                    </a>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>


        <style>
            .resullibros {
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
                    flex: 1 1 320px;
                    background-color: #d8d8d888;
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    box-sizing: border-box;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    justify-items: center;
                    align-items: center;
                    border-radius: clamp(15px, 1.8vw, 22px);
                    align-items: center;
                    align-content: center;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    justify-items: center;
                    padding: clamp(15px, 1.8vw, 22px);
                    max-width: 100%;

                    .bookbox {
                        width: 90%;
                        display: flex;
                        margin: 0 auto;
                        justify-content: center;
                        padding: 0;
                        height: 200px;
                        border-radius: clamp(15px, 1.8vw, 22px);
                        border: 1px solid rgba(99, 99, 99, 0.37);
                        overflow: hidden;
                        box-sizing: border-box;

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
                        align-items: center;
                        gap: clamp(.2rem, 4vh, .4rem);
                        overflow: hidden;
                        text-overflow: ellipsis;

                        .TituloLibro:first-child {
                            color: #333333;
                            margin: 0;
                            padding: 0;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            margin-top: clamp(.2rem, 2vw, .4rem);
                            border-radius: clamp(15px, 1.8vw, 22px);
                            border: 1px solid rgba(99, 99, 99, 0.37);
                            width: 88%;
                            background-color: red;
                            text-align: center;
                            background-color: #08083031;
                        }

                        .TituloLibro:nth-child(2) {
                            color: #333333;
                            margin: 0;
                            padding: 0;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            max-height: 24px;
                            height: 100%;
                            width: 92%;
                            justify-content: center;
                            margin-top: clamp(.2rem, 4vw, .5rem);
                            display: flex;
                        }

                        p {
                            color: #333333;
                            margin: 0;
                            padding: 0;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            max-height: 24px;
                            height: 100%;
                        }

                    }

                    .boton {
                        display: flex;
                        align-items: center;
                        align-content: center;
                        justify-content: center;
                        box-sizing: border-box;
                        height: clamp(2rem, 8vh, 2.4rem);
                        width: 85%;
                        margin-top: clamp(.6rem, 4vw, 1.2rem);

                        a {
                            display: flex;
                            width: 100%;
                            text-align: center;
                            color: #333333;
                            text-decoration: none;
                            box-sizing: border-box;
                            height: 100%;
                            align-items: center;
                            justify-content: center;
                            border: 1px solid rgba(99, 99, 99, 0.37);
                            backdrop-filter: blur(5px);
                            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                            border-radius: clamp(.5rem, 8vh, 1.2rem);
                            background-color: #08083069;
                        }

                        a:visited {
                            font-style: none;
                            color: #333333;
                            text-decoration: none;
                        }
                    }

                }

            }

            .mensajenulo {
                color: #333333;
            }
        </style>

        <div class="resullibros">
            <h2>Libros disponibles</h2>

            <div class="bookbox-container">
                <?php if (empty($books)): ?>
                    <h2 class="mensajenulo">No se encontraron libros disponibles.</h2>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>

                        <div class="fullbook">

                            <div class="bookbox">
                                <img src="<?= htmlspecialchars($book['bookpic']) ?>" alt="Libro publicado">
                            </div>

                            <div class="infolibro">

                                <h3 class="TituloLibro">
                                    <?= htmlspecialchars($book['typeof']) ?>
                                </h3>

                                <h3 class="TituloLibro"><?= htmlspecialchars($book['name']) ?></h3>

                                <p><b>Publicado por:</b>
                                    <?= htmlspecialchars($book['owner_name']) ?>
                                </p>

                                <p><b>Autor:</b>
                                    <?= htmlspecialchars($book['author']) ?></p>

                                <p><b>Género:</b>
                                    <?= htmlspecialchars($book['genre']) ?></p>

                                <?php if ($book['price'] !== null): ?>
                                    <p><b>$
                                            <?= htmlspecialchars($book['price']) ?>
                                        </b></p>
                                <?php elseif ($book['price'] == null): ?>
                                    <p><b>($) No aplica</b></p>
                                <?php endif; ?>
                            </div>

                            <div class="boton">
                                <?php if ($is_logged_in && $book['ownerid'] != $_SESSION['user_id']): ?>
                                    <a href="pickedbook.php?id=<?= $book['id'] ?>">ADQUIRIR</a>
                                <?php elseif (!$is_logged_in): ?>
                                    <a href="login.php">INICIA SESIÓN</a>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>

</body>

</html>