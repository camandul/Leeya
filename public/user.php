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

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
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
            z-index: 4;
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

        <a href="explore.php">
            <div class="content">EXPLORAR</div>
        </a>

        <?php if ($is_logged_in):

            $pending_counts = getPendingProposalsCount($_SESSION['user_id']);
            $total_pending = $pending_counts['sent'] + $pending_counts['received'];
            $badge_text = $total_pending > 9 ? '+9' : ($total_pending > 0 ? $total_pending : '');
            ?>

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
                        <p><?= $badge_text ?></p>
                    </span>
                <?php endif; ?>
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


                    .numnoti {
                        position: absolute;
                        margin: auto;
                        padding: 3px 1px 0 0;
                        color: #202020;
                        font-size: clamp(.4rem, 1.2vh, .6rem);
                    }

                    .esuve {
                        height: 100%;
                        width: auto;
                        max-height: 100%;
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


    <main>

        <style>
            main {
                max-width: 1440px;
                min-width: 200px;
                width: 100%;
                height: auto;
                display: flex;
                flex-direction: column;
                margin: 2.8rem auto 0 auto;
                padding: 2rem 0 0 0;
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

            .profile-container {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                flex-direction: row;
                margin: 0 auto;
                justify-content: center;
                align-items: stretch;
                margin-top: 3.5rem;
                gap: 2.5%;
                padding: 0 2rem;
                box-sizing: border-box;
            }

            @media(max-width: 750px) {
                .profile-container {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    margin: 0 auto;
                    justify-content: center;
                    align-items: center;
                    margin-top: 1rem;
                    gap: 3.5%;
                    font-size: 10px;
                }

                h1 {
                    font-size: 15px;
                }
            }

            .dataUser {
                width: 50%;
                display: flex;
                flex-direction: column;
                flex-wrap: nowrap;
                align-items: flex-start;
                justify-content: flex-start;
                margin: 0;
                box-sizing: border-box;
                padding: 3rem;
                border-radius: 10px;
                border: 1px solid rgba(99, 99, 99, 0.37);
                background-color: #d8d8d888;
                backdrop-filter: blur(80px);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                overflow: hidden;
                color: #333333;
                gap: 1.2rem;

                h1 {
                    margin: 0 0 0.5rem 0;
                    font-size: 28px;
                    width: 100%;
                    text-align: left;
                    color: #15152e;
                }

                p {
                    display: block;
                    margin: 0;
                    padding: 0;
                    width: 100%;
                    text-align: left;
                    line-height: 1.4;
                    font-size: 14px;
                }

                .since {
                    margin-bottom: 0.5rem;
                    font-family: 'HovesDemiBoldItalic';
                    font-size: 13px;
                    color: #555555;
                }

            }

            .userChanges {
                width: 30%;
                display: flex;
                flex-direction: column;
                flex-wrap: nowrap;
                align-items: stretch;
                justify-content: center;
                gap: 0.8rem;
                border-radius: 10px;
                border: 1px solid rgba(99, 99, 99, 0.37);
                background-color: #08083069;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                padding: 2rem;
                box-sizing: border-box;

                a {
                    width: 100%;
                    text-align: center;
                    padding: 1rem;
                    border-radius: 10px;
                    background-color: #d8d8d888;
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    backdrop-filter: blur(80px);
                    text-decoration: none;
                    font-size: 14px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    color: #333333;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 45px;
                    box-sizing: border-box;
                }

                a:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
                    border-color: rgba(99, 99, 99, 0.6);
                }

            }

            @media(max-width: 750px) {
                .profile-container {
                    padding: 0 1rem;
                    gap: 1.5rem;
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
                        font-size: 13px;
                    }

                    .since {
                        margin-bottom: 0;
                        font-family: 'HovesDemiBoldItalic';
                        font-size: 12px;
                    }

                }

                .userChanges {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    flex-wrap: nowrap;
                    align-items: stretch;
                    justify-content: flex-start;
                    border-radius: 10px;
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    background-color: #08083069;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    margin: 0;
                    box-sizing: border-box;
                    padding: 1.5rem;
                    gap: 0.6rem;

                    a {
                        width: 100%;
                        text-align: center;
                        padding: 0.85rem;
                        border-radius: 10px;
                        background-color: #d8d8d888;
                        border: 1px solid rgba(99, 99, 99, 0.37);
                        backdrop-filter: blur(80px);
                        text-decoration: none;
                        font-size: 13px;
                        font-weight: 600;
                        transition: all 0.3s ease;
                        color: #333333;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 40px;
                        box-sizing: border-box;
                    }

                    a:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                    }

                }
            }

            a:visited {
                text-decoration: none;
                color: #333333;
            }

            .infotextfinal {
                max-height: 60px;
                height: auto;
                text-overflow: ellipsis;
                overflow: auto;
                box-sizing: border-box;
                padding-top: 0;
                width: 100%;
                font-size: 14px;
                line-height: 1.4;

            }
        </style>


        <div class="profile-container">

            <div class="dataUser">

                <h1 class="welcome">Bienvenido,
                    <?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]); ?>
                </h1>
                <p class="since">Usuario activo desde:
                    <?php echo htmlspecialchars(explode(' ', $_SESSION['user_signdate'])[0]); ?>
                </p>
                <p class="infotext">Correo electrónico de contacto:
                    <?php echo htmlspecialchars(explode(' ', $_SESSION['user_email'])[0]); ?>
                </p>
                <p class="infotext">Ubicación:
                    <?php echo htmlspecialchars($_SESSION['user_location']); ?>
                </p>
                <?php
                $user_description = htmlspecialchars($_SESSION['user_description']);
                if ($_SESSION['user_description'] == '') {
                    ?>
                    <p class="infotextfinal">Aún no cuentas con una descripción</p>
                    <?php
                } else {
                    ?>
                    <p class="infotextfinal">Tu descripción:
                        <?php echo htmlspecialchars($user_description); ?>

                        <?php
                }
                ?>

            </div>

            <div class="userChanges">
                <a class="functions" href="changePassword.php">CAMBIAR CONTRASEÑA</a>
                <a class="functions" href="changeLocation.php">CAMBIAR LOCALIDAD</a>
                <a class="functions" href="changeDescription.php">CAMBIAR DESCRIPCIÓN</a>
                <a class="functions" href="balance.php">MI BALANCE</a>
                <a class="functions" href="logout.php">CERRAR SESIÓN</a>
            </div>

        </div>


        <style>
            .header-container {
                margin-top: clamp(3rem, 8vh, 6rem);
                margin-bottom: 1.5rem;
                color: #333333;

                h1 {
                    padding: 0;
                    margin: 0;
                    font-size: clamp(1.2rem, 2vw, 1.6rem);
                    color: #15152e;
                }
            }

            .bookbox-container {
                margin: 0 auto clamp(2.2rem, 2.8vh, 3.5rem) auto;
                border: 1px solid rgba(99, 99, 99, 0.37);
                background-color: #d8d8d888;
                backdrop-filter: blur(8px);
                width: 92%;
                padding: clamp(.8rem, 2vw, 2.2rem);
                display: flex;
                flex-wrap: wrap;
                justify-items: stretch;
                justify-content: center;
                align-items: stretch;
                gap: 1rem;
                border-radius: clamp(1rem, 1.5vw, 2rem);
                box-sizing: border-box;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            }

            .fullbook {
                flex: 0 0 auto;
                background-color: #d8d8d888;
                border: 1px solid rgba(99, 99, 99, 0.37);
                max-width: 280px;
                width: 100%;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: center;
                border-radius: clamp(15px, 1.8vw, 22px);
                align-items: center;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            }

            .statusbook {
                background-color: #08083033;
                border: 1px solid rgba(99, 99, 99, 0.37);
                width: clamp(8rem, 10vw, 5vw);
                text-align: center;
                color: #333333;
                margin: clamp(.6rem, 3.5vh, 2rem) 0 clamp(1rem, 1vh, 3rem) 0;
                border-radius: clamp(10px, 1.5vw, 20px);
                font-size: clamp(.8rem, 1.2vw, 1rem);
            }

            .imagenbox {
                background-color: transparent;
                height: 200px;
                margin: 0 auto clamp(.5rem, .8vh, 2rem) auto;
                width: 88%;
                border-radius: clamp(10px, 1.5vw, 20px);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                overflow: hidden;

                img {
                    height: auto;
                    width: 100%;
                }
            }

            .cajajunta {
                width: 82%;
                max-height: 20px;
                overflow: hidden;
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: space-between;
                text-overflow: ellipsis;
                font-size: clamp(.7rem, 1vw, 1rem);
            }

            .TituloLibro {
                width: 55%;
                text-align: start;
                text-overflow: ellipsis;
                overflow: hidden;
                color: #333333;
            }

            .PrecioLibro {
                width: 40%;
                color: #202020;
                text-align: center;
            }

            .AdquirirLibro {
                text-decoration: none;
                width: 40%;
                font-size: clamp(.8rem, 1.2vw, 1.4rem);
                text-align: center;
                border-radius: clamp(10px, 1.5vw, 20px);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                margin: clamp(.4rem, 2.6vh, 1.6rem) 0 clamp(1rem, 4vh, 3rem) 0;
                border: 1px solid rgba(99, 99, 99, 0.37);
                background-color: #08083069;

                a {
                    text-decoration: none;
                    color: #333333;

                }

                a::visited {
                    text-decoration: none;
                    color: ;
                }
            }
        </style>

        <?php

        $books = [];
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');

        if ($is_logged_in && isset($_SESSION['user_id'])) {
            $books = getBooksByUserId($_SESSION['user_id']);

            // Filtrar por búsqueda (nombre, autor, género)
            if ($search !== '') {
                $search_lower = strtolower($search);
                $books = array_filter($books, function ($book) use ($search_lower) {
                    return strpos(strtolower($book['name']), $search_lower) !== false ||
                        strpos(strtolower($book['author']), $search_lower) !== false ||
                        strpos(strtolower($book['genre']), $search_lower) !== false;
                });
            }

            // Filtrar por tipo de transacción
            if ($type !== '') {
                $books = array_filter($books, function ($book) use ($type) {
                    return $book['typeof'] === $type;
                });
            }
        }
        ?>


        <div class="header-container">
            <?php if (empty($books)): ?>
                <h1>No tienes libros en tu
                    catálogo<?php if ($search !== '' || $type !== '') {
                        echo ' con esos criterios';
                    } ?></h1>
            <?php else: ?>
                <h1>Mi catálogo</h1>
            </div>

            <style>
                .filtros {
                    width: 92%;
                    padding-top: clamp(2rem, 6vh, 3.2rem);
                    padding-bottom: clamp(1.4rem, 6vh, 2.4rem);
                    box-sizing: border-box;
                    margin: 0 auto;
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
                            flex: 1 1 400px;

                            input {
                                height: clamp(2rem, 8vh, 2.4rem);
                                width: 100%;
                            }
                        }

                        div:nth-child(2) {
                            display: flex;
                            flex: 1 1 400px;

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
                            flex: 1 1 180px;
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
                                cursor: pointer;
                                transition: all 0.3s ease;
                            }

                            button:hover {
                                background-color: #08083090;
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
                                placeholder="Buscar por título, autor o género...">
                        </div>
                        <div>
                            <select name="type">
                                <option value="">Todos</option>
                                <option value="Donacion" <?= $type == 'Donacion' ? 'selected' : ''; ?>>Donación</option>
                                <option value="Venta" <?= $type == 'Venta' ? 'selected' : ''; ?>>Venta</option>
                                <option value="Intercambio" <?= $type == 'Intercambio' ? 'selected' : ''; ?>>Intercambio
                                </option>
                                <option value="Subasta" <?= $type == 'Subasta' ? 'selected' : ''; ?>>Subasta</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit">BUSCAR</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bookbox-container">

                <?php foreach ($books as $book): ?>

                    <div class="fullbook">

                        <div class="statusbook">
                            <?= htmlspecialchars($book['typeof']) ?>
                        </div>


                        <div class="imagenbox">
                            <img src="<?= htmlspecialchars($book['bookpic']) ?>" alt="Libro publicado">
                        </div>

                        <div class="cajajunta">
                            <div class="TituloLibro">
                                <?= htmlspecialchars($book['name']) ?>
                            </div>


                            <?php if ($book['price'] !== null): ?>
                                <div class="PrecioLibro">$
                                    <?= htmlspecialchars($book['price']) ?>
                                </div>
                            <?php elseif ($book['price'] == null): ?>
                                <div class="PrecioLibro">($) No aplica</div>
                            <?php endif; ?>
                        </div>


                        <div class="AdquirirLibro">
                            <a href="pickedbook.php?id=<?= $book['id'] ?>">MÁS INFO</a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>


    </main>

</body>

</html>