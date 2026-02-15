<?php

session_start();

require_once __DIR__ . '/../src/auth_functions.php';
require_once __DIR__ . '/../src/database.php';

$is_logged_in = false;
$user_role = '';

refreshSessionUser();
updateExpiredAuctions();
cancelOldProposals();

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

// Obtener estadísticas del balance
$balance = getUserBalance($_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi balance</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/icon.png" type="image/png">
</head>

<body>

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

        <style>
            .balance-title {
                width: 92%;
                margin-top: clamp(1.4rem, 3vw, 2.5rem);
                color: #15152e;
                text-align: left;
                font-size: clamp(1.8rem, 8vh, 2.5rem);
                border-bottom: 1px solid rgba(99, 99, 99, 0.37);
                padding-bottom: clamp(.8rem, 3vh, 1.2rem);
                margin-bottom: clamp(1.5rem, 6vh, 2rem);
            }

            .balance-grid {
                width: 95%;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: clamp(1rem, 4vh, 1.8rem);
                margin-bottom: clamp(2rem, 8vh, 3rem);
            }

            .balance-card {
                border-radius: clamp(8px, 1.8vw, 14px);
                display: flex;
                flex-direction: column;
                border: 1px solid rgba(99, 99, 99, 0.37);
                backdrop-filter: blur(8px);
                box-sizing: border-box;
                padding: clamp(1.2rem, 4vh, 2rem);
                align-items: center;
                justify-content: center;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                background-color: #64646425;
                min-height: 160px;
                text-align: center;
            }

            .balance-card h3 {
                color: #666666;
                font-size: clamp(0.85rem, 3vh, 0.95rem);
                margin: 0 0 clamp(0.8rem, 3vh, 1.2rem) 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-weight: 600;
            }

            .balance-card .value {
                color: #15152e;
                font-size: clamp(2rem, 6vh, 2.8rem);
                font-weight: bold;
                margin: 0;
            }

            .balance-card .subtitle {
                color: #999999;
                font-size: clamp(0.75rem, 2vh, 0.85rem);
                margin-top: clamp(0.4rem, 2vh, 0.6rem);
            }

            .balance-section {
                width: 95%;
                margin-bottom: clamp(2rem, 8vh, 3rem);
            }

            .section-title {
                color: #15152e;
                font-size: clamp(1.3rem, 5vh, 1.6rem);
                margin: 0 0 clamp(1.2rem, 4vh, 1.8rem) 0;
                border-bottom: 2px solid rgba(99, 99, 99, 0.37);
                padding-bottom: clamp(0.6rem, 2vh, 0.8rem);
            }

            .financial-grid {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: clamp(1rem, 4vh, 1.8rem);
            }

            .financial-card {
                border-radius: clamp(8px, 1.8vw, 14px);
                display: flex;
                flex-direction: column;
                border: 1px solid rgba(99, 99, 99, 0.37);
                backdrop-filter: blur(8px);
                box-sizing: border-box;
                padding: clamp(1.5rem, 4vh, 2.2rem);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                background-color: #64646425;
            }

            .financial-card.earned {
                border-left: 4px solid #4CAF50;
            }

            .financial-card.invested {
                border-left: 4px solid #2196F3;
            }

            .financial-card.net {
                border-left: 4px solid #FF9800;
            }

            .financial-label {
                color: #666666;
                font-size: clamp(0.9rem, 3vh, 1rem);
                margin: 0 0 clamp(0.6rem, 2vh, 1rem) 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-weight: 600;
            }

            .financial-amount {
                color: #15152e;
                font-size: clamp(1.8rem, 5vh, 2.4rem);
                font-weight: bold;
                margin: 0;
            }

            .financial-card.earned .financial-label {
                color: #4CAF50;
            }

            .financial-card.invested .financial-label {
                color: #2196F3;
            }

            .financial-card.net .financial-label {
                color: #FF9800;
            }

            .balance-summary {
                width: 100%;
                border-radius: clamp(8px, 1.8vw, 14px);
                border: 1px solid rgba(99, 99, 99, 0.37);
                backdrop-filter: blur(8px);
                box-sizing: border-box;
                padding: clamp(1.5rem, 4vh, 2.2rem);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                background-color: #64646425;
                display: flex;
                flex-direction: column;
                gap: clamp(1rem, 3vh, 1.5rem);
            }

            .summary-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: clamp(0.6rem, 2vh, 1rem) 0;
                border-bottom: 1px solid rgba(99, 99, 99, 0.2);
            }

            .summary-row:last-child {
                border-bottom: none;
            }

            .summary-label {
                color: #666666;
                font-size: clamp(0.9rem, 2vh, 1rem);
            }

            .summary-value {
                color: #15152e;
                font-size: clamp(0.95rem, 2.5vh, 1.1rem);
                font-weight: bold;
            }

            @media (max-width: 750px) {
                .balance-grid {
                    grid-template-columns: 1fr;
                }

                .financial-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <h1 class="balance-title">Mi Balance</h1>

        <!-- Estadísticas principales -->
        <div class="balance-grid">
            <div class="balance-card">
                <h3>Transacciones Totales</h3>
                <p class="value"><?= htmlspecialchars($balance['total_transactions']) ?></p>
                <p class="subtitle">Operaciones completadas</p>
            </div>

            <div class="balance-card">
                <h3>Libros Dados</h3>
                <p class="value"><?= htmlspecialchars($balance['books_given']) ?></p>
                <p class="subtitle">Libros vendidos/subastados</p>
            </div>

            <div class="balance-card">
                <h3>Libros Adquiridos</h3>
                <p class="value"><?= htmlspecialchars($balance['books_acquired']) ?></p>
                <p class="subtitle">Libros comprados/ganados</p>
            </div>
        </div>

        <!-- Sección Financiera -->
        <div class="balance-section">
            <h2 class="section-title">Análisis Financiero</h2>

            <div class="financial-grid">
                <div class="financial-card earned">
                    <p class="financial-label">Dinero Generado</p>
                    <p class="financial-amount">$<?= number_format($balance['money_earned'], 2, '.', ',') ?></p>
                    <p class="subtitle">De ventas y subastas</p>
                </div>

                <div class="financial-card invested">
                    <p class="financial-label">Dinero Invertido</p>
                    <p class="financial-amount">$<?= number_format($balance['money_invested'], 2, '.', ',') ?></p>
                    <p class="subtitle">En compras y subastas</p>
                </div>

                <div class="financial-card net">
                    <p class="financial-label">Balance Neto</p>
                    <p class="financial-amount"
                        style="color: <?= $balance['net_balance'] >= 0 ? '#4CAF50' : '#f44336' ?>">
                        <?= $balance['net_balance'] >= 0 ? '+' : '' ?>$<?= number_format($balance['net_balance'], 2, '.', ',') ?>
                    </p>
                    <p class="subtitle"><?= $balance['net_balance'] >= 0 ? 'Ganancia' : 'Pérdida' ?></p>
                </div>
            </div>
        </div>

        <!-- Resumen detallado -->
        <div class="balance-section">
            <h2 class="section-title">Resumen Detallado</h2>

            <div class="balance-summary">
                <div class="summary-row">
                    <span class="summary-label">Total de Transacciones</span>
                    <span class="summary-value"><?= htmlspecialchars($balance['total_transactions']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Libros que has dado</span>
                    <span class="summary-value"><?= htmlspecialchars($balance['books_given']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Libros que has adquirido</span>
                    <span class="summary-value"><?= htmlspecialchars($balance['books_acquired']) ?></span>
                </div>
                <div class="summary-row"
                    style="border-bottom: 2px solid rgba(99, 99, 99, 0.37); margin-top: clamp(0.5rem, 2vh, 0.8rem);">
                    <span class="summary-label"><strong>Total Movimiento Financiero</strong></span>
                    <span
                        class="summary-value"><strong>$<?= number_format($balance['money_earned'] + $balance['money_invested'], 2, '.', ',') ?></strong></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label"><strong>Beneficio / Pérdida</strong></span>
                    <span class="summary-value"
                        style="color: <?= $balance['net_balance'] >= 0 ? '#4CAF50' : '#f44336' ?>">
                        <strong><?= $balance['net_balance'] >= 0 ? '+' : '' ?>$<?= number_format($balance['net_balance'], 2, '.', ',') ?></strong>
                    </span>
                </div>
            </div>
        </div>

    </main>

</body>

</html>