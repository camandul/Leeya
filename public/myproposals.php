<?php

session_start();

require_once __DIR__ . '/../src/auth_functions.php';
require_once __DIR__ . '/../src/database.php';

$is_logged_in = false;
$user_role = '';

refreshSessionUser();
updateExpiredAuctions();
cancelOldProposals();
cancelInvalidExchangeProposals();

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

$action_message = '';
$action_error = '';

// Procesar acciones solo en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    if (isset($_POST['cancel_proposal'])) {
        $proposal_id = intval($_POST['cancel_proposal']);
        $result = updateProposalStatus($proposal_id, 'Cancelada');
        $_SESSION['action_message'] = $result ? 'Propuesta cancelada.' : 'Error al cancelar propuesta.';
        header("Location: myproposals.php");
        exit();
    }
    if (isset($_POST['accept_proposal'])) {
        $proposal_id = intval($_POST['accept_proposal']);
        $result = finalizeProposal($proposal_id);
        $_SESSION['action_message'] = $result ? 'Propuesta aceptada y libro marcado como no disponible.' : 'Error al aceptar propuesta.';
        header("Location: myproposals.php");
        exit();
    }
    if (isset($_POST['reject_proposal'])) {
        $proposal_id = intval($_POST['reject_proposal']);
        $result = updateProposalStatus($proposal_id, 'Rechazada');
        $_SESSION['action_message'] = $result ? 'Propuesta rechazada.' : 'Error al rechazar propuesta.';
        header("Location: myproposals.php");
        exit();
    }
    if (isset($_POST['rate_user'])) {
        $proposal_id = intval($_POST['proposal_id']);
        $ratee_id = intval($_POST['ratee_id']);
        $rating = intval($_POST['stars']);
        $commentary = trim($_POST['rate_description']);

        // Validar datos básicos
        if (empty($proposal_id) || empty($ratee_id) || $rating < 1 || $rating > 5 || empty($commentary)) {
            $_SESSION['action_message'] = 'Los datos de la reseña no son válidos.';
        } elseif (existsRatingForProposal($proposal_id, $_SESSION['user_id'])) {
            $_SESSION['action_message'] = 'Ya has reseñado esta transacción.';
        } else {
            $result = rateUser($_SESSION['user_id'], $ratee_id, $rating, htmlspecialchars($commentary), $proposal_id);
            if ($result) {
                $_SESSION['action_message'] = 'Reseña enviada exitosamente.';
            } else {
                $_SESSION['action_message'] = 'Error al enviar la reseña. Por favor intenta de nuevo.';
            }
        }

        header("Location: myproposals.php");
        exit();
    }
}

// Mensajes POST/REDIRECT/GET
if (isset($_SESSION['action_message'])) {
    $action_message = $_SESSION['action_message'];
    unset($_SESSION['action_message']);
}

$sent_proposals = getSentProposals($_SESSION['user_id']);
$received_proposals = getReceivedProposals($_SESSION['user_id']);

$pending_counts = getPendingProposalsCount($_SESSION['user_id']);
$total_pending = $pending_counts['sent'] + $pending_counts['received'];
$badge_text = $total_pending > 9 ? '+9' : ($total_pending > 0 ? $total_pending : '');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis propuestas</title>
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

        <div>
            <?php if ($action_message): ?>
                <div class="success-message"><?= htmlspecialchars($action_message) ?>
                </div>
            <?php endif; ?>
        </div>


        <style>
            .cabecera {
                width: 92%;
                margin-top: clamp(1.4rem, 3vw, 2.5rem);
                color: #15152e;
                text-align: justify;
                font-size: clamp(1.4rem, 8vh, 1.8px);
                border-bottom: 1px solid rgba(99, 99, 99, 0.37);
            }

            .prophice {
                width: 95%;
                display: flex;
                flex-wrap: wrap;
                gap: clamp(.8rem, 4vh, 1.5rem);
            }

            .proposal-item {
                border-radius: clamp(8px, 1.8vw, 14px);
                flex: 1 1 280px;
                display: flex;
                flex-direction: column;
                border: 1px solid rgba(99, 99, 99, 0.37);
                backdrop-filter: blur(8px);
                box-sizing: border-box;
                padding: clamp(.8rem, 4vh, 1.5rem);
                align-items: stretch;
                justify-content: space-between;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                background-color: #64646425;
                max-width: 100%;
            }

            .box1item {
                width: 100%;
                height: 200px;
                border: 1px solid rgba(99, 99, 99, 0.37);
                overflow: hidden;
                border-radius: clamp(8px, 1.8vw, 14px);
                box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06);
                display: flex;
                justify-content: center;

                img {
                    height: 100%;
                    width: auto;
                }
            }

            .proposal-info {
                width: 100%;
                margin: 0;
                padding: 0;
                justify-content: center;
                margin-top: clamp(.5rem, 3vh, 1rem);
                margin-bottom: clamp(.5rem, 3vh, 1.2rem);

                h3 {
                    width: 100%;
                    display: flex;
                    align-items: flex-start;
                    justify-content: center;
                    color: #333333;
                    margin: 0;
                    padding: 0;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    height: 24px;
                    border-bottom: 1px solid rgba(99, 99, 99, 0.37);
                    margin-bottom: clamp(.6rem, 4vh, 1.2rem);
                }

                p {
                    width: 95%;
                    display: flex;
                    margin: 0 auto;
                    padding: 0;
                    color: #333333;
                }
            }

            .proposal-actions {
                display: flex;
                flex-wrap: wrap;
                width: 100%;
                box-sizing: border-box;
                gap: clamp(.4rem, 4vh, .8rem);

                .botonaccion {
                    flex: 1 1 250px;
                    width: 100%;
                    font-size: clamp(.7rem, 6vh, .9rem);
                    font-family: 'HovesDemiBold';
                    color: #333333;
                    border: 1px solid rgba(99, 99, 99, 0.37);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                    border-radius: clamp(.5rem, 8vh, 1.2rem);
                    background-color: #08083069;
                    height: clamp(2rem, 8vh, 2.2rem);
                    box-sizing: border-box;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    text-decoration: none;

                    button {
                        text-decoration: none;
                        width: 100%;
                        height: 100%;
                        font-size: clamp(.7rem, 6vh, .9rem);
                        font-family: 'HovesDemiBold';
                        color: #333333;
                        border-radius: clamp(.5rem, 8vh, 1.2rem);
                        border: none;
                        cursor: pointer;
                        background-color: #08083012;
                    }
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
                margin-top: clamp(2rem, 10vh, 3.8rem);
            }

            .resenaescribir {
                width: 100%;
                border-top: 1px solid rgba(99, 99, 99, 0.37);
                margin-top: clamp(.8rem, 4vh, 1.5rem);
                padding-top: clamp(.8rem, 4vh, 1.5rem);
            }

            .resenaescribir h3 {
                color: #333333;
                font-size: clamp(.9rem, 4vh, 1.1rem);
                margin: 0 0 clamp(.6rem, 3vh, 1rem) 0;
            }

            .resenaescribir form {
                display: flex;
                flex-direction: column;
                gap: clamp(.6rem, 3vh, 1rem);
                width: 100%;
            }

            .resenaescribir div {
                display: flex;
                flex-direction: column;
                gap: 0.4rem;
            }

            .resenaescribir label {
                color: #333333;
                font-size: clamp(.8rem, 3vh, .9rem);
                font-weight: bold;
            }

            .resenaescribir select,
            .resenaescribir textarea {
                padding: 0.6rem;
                border: 1px solid rgba(99, 99, 99, 0.37);
                border-radius: 0.5rem;
                font-family: 'HovesDemiBold';
                color: #333333;
                background-color: rgba(216, 216, 216, 0.53);
                font-size: clamp(.8rem, 3vh, .9rem);
            }

            .resenaescribir textarea {
                resize: vertical;
                min-height: 60px;
            }

            .resenaescribir button {
                padding: 0.8rem 1.5rem;
                background-color: #08083069;
                color: #333333;
                border: 1px solid rgba(99, 99, 99, 0.37);
                border-radius: 0.6rem;
                font-family: 'HovesDemiBold';
                font-size: clamp(.8rem, 3vh, .9rem);
                cursor: pointer;
                transition: background-color 0.3s ease;
            }

            .resenaescribir button:hover {
                background-color: #08083090;
            }
        </style>


        <h2 class="cabecera">Propuestas que hice</h2>

        <section class="prophice">

            <?php if (empty($sent_proposals)): ?>
                <div class="error-message">No has realizado propuestas.</div>
            <?php else: ?>
                <?php foreach ($sent_proposals as $p): ?>
                    <div class="proposal-item">

                        <div class="box1item">
                            <img src="<?= htmlspecialchars($p['bookpic']) ?>" alt="Libro publicado">
                        </div>

                        <div class="proposal-info">

                            <h3><?= htmlspecialchars($p['book_name']) ?></h3>
                            <p class="author"><strong>Autor: </strong> <?= htmlspecialchars($p['author']) ?></p>

                            <p class="detail-item"><strong>Tipo: </strong> <?= htmlspecialchars($p['typeof']) ?></p>

                            <p class="detail-item"><strong>Precio: </strong>
                                <?= $p['price'] !== null ? '$' . htmlspecialchars($p['price']) : 'N/A' ?></p>

                            <p class="detail-item"><strong>Estado de propuesta: </strong> <?= htmlspecialchars($p['status']) ?>
                            </p>

                            <p class="detail-item"><strong>Dueñ@: </strong> <?= htmlspecialchars($p['owner_name']) ?></p>

                            <?php if ($p['typeof'] === 'Venta' && $p['money'] !== null): ?>
                                <div class="proposal-amount">
                                    <p><strong>Monto ofrecido: </strong> $<?= htmlspecialchars($p['money']) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($p['typeof'] === 'Intercambio'): ?>
                                <?php
                                $exchange_books = getExchangeBooks($p['id']);
                                if ($exchange_books):
                                    ?>
                                    <div class="proposal-exchange">
                                        <p><strong>Libros ofrecidos: </strong></p>
                                        <ul>
                                            <?php foreach ($exchange_books as $eb): ?>
                                                <li class="exchange-book">
                                                    <p>- <?= htmlspecialchars($eb['name']) ?> - <?= htmlspecialchars($eb['author']) ?></p>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>

                        <div class="proposal-actions">
                            <a class="botonaccion"
                                href="https://outlook.office.com/mail/deeplink/compose?to=<?= urlencode($p['owner_email']) ?>&subject=Consulta&body=Hola,%20estoy%20interesado%20en%20el%20libro"
                                target="_blank" class="btn-contact">CONTACTAR</a>
                            <a class="botonaccion" href="pickeduser.php?id=<?= $p['owner_id'] ?>" class="btn-profile">PERFIL DE
                                DUEÑ@</a>
                            <a class="botonaccion" href="pickedbook.php?id=<?= $p['book_id'] ?>" class="btn-view">VER
                                PUBLICACIÓN</a>
                            <?php if ($p['status'] === 'En proceso'): ?>
                                <form class="botonaccion" method="post">
                                    <input type="hidden" name="cancel_proposal" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-cancel"
                                        onclick="return confirm('¿Cancelar esta propuesta?');">CANCELAR</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <?php if ($p['status'] === 'Finalizada'): ?>
                            <?php
                            $already_rated = existsRatingForProposal($p['id'], $_SESSION['user_id']);
                            if (!$already_rated):
                                ?>
                                <div class="resenaescribir">
                                    <form method="post">
                                        <h3>Escribir una reseña</h3>
                                        <div>
                                            <label for="stars_sent_<?= $p['id'] ?>">Estrellas:</label>
                                            <select name="stars" id="stars_sent_<?= $p['id'] ?>" required>
                                                <option value="">Selecciona</option>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="rate_description_sent_<?= $p['id'] ?>">Descripción:</label>
                                            <textarea name="rate_description" id="rate_description_sent_<?= $p['id'] ?>" rows="2"
                                                required></textarea>
                                        </div>
                                        <input type="hidden" name="proposal_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="ratee_id" value="<?= $p['owner_id'] ?>">
                                        <button type="submit" name="rate_user" class="btn-save">Enviar reseña</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="resenaescribir">
                                    <p class="hola"><strong>✓ Ya has reseñado esta transacción</strong></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>


        <style>
            .proprecibi {
                width: 95%;
                display: flex;
                flex-wrap: wrap;
                gap: clamp(.8rem, 4vh, 1.5rem);
                margin-bottom: clamp(1rem, 6vh, 1.8rem);
            }

            .exchange-book {
                width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .hola{
                color: #333333;
            }
        </style>

        <h2 class="cabecera">Propuestas que recibí</h2>

        <section class="proprecibi">

            <?php if (empty($received_proposals)): ?>
                <div class="error-message">No has recibido propuestas.</div>
            <?php else: ?>
                <?php foreach ($received_proposals as $p): ?>
                    <div class="proposal-item">
                        <div class="box1item">
                            <img src="<?= htmlspecialchars($p['bookpic']) ?>" alt="Libro publicado">
                        </div>

                        <div class="proposal-info">
                            <h3><?= htmlspecialchars($p['book_name']) ?></h3>
                            <p class="author"><?= htmlspecialchars($p['author']) ?></p>

                            <p class="detail-item"><strong>Tipo: </strong> <?= htmlspecialchars($p['typeof']) ?></p>

                            <p class="detail-item"><strong>Precio: </strong>
                                <?= $p['price'] !== null ? '$' . htmlspecialchars($p['price']) : 'N/A' ?></p>
                            <p class="detail-item"><strong>Estado de propuesta: </strong> <?= htmlspecialchars($p['status']) ?>
                            </p>

                            <p class="detail-item"><strong>Interesad@: </strong>
                                <?= htmlspecialchars($p['interested_name']) ?></p>


                            <?php if ($p['typeof'] === 'Venta' && $p['money'] !== null): ?>
                                <div class="proposal-amount">
                                    <p><strong>Monto ofrecido: </strong> $<?= htmlspecialchars($p['money']) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($p['typeof'] === 'Intercambio'): ?>
                                <?php
                                $exchange_books = getExchangeBooks($p['id']);
                                if ($exchange_books):
                                    ?>
                                    <div class="proposal-exchange">
                                        <p><strong>Libros ofrecidos: </strong></p>
                                        <ul>
                                            <?php foreach ($exchange_books as $eb): ?>
                                                <li class="exchange-book">
                                                    <p>- <?= htmlspecialchars($eb['name']) ?> - <?= htmlspecialchars($eb['author']) ?></p>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="proposal-actions">
                            <a href="https://outlook.office.com/mail/deeplink/compose?to=<?= urlencode($p['interested_email']) ?>&subject=Consulta&body=Hola,%20estoy%20interesado%20en%20el%20libro"
                                target="_blank" class="botonaccion">CONTACTAR</a>
                            <a href="pickeduser.php?id=<?= $p['interested_id'] ?>" class="botonaccion">PERFIL DEL INTERESADO</a>
                            <a href="pickedbook.php?id=<?= $p['book_id'] ?>" class="botonaccion">VER PUBLICACIÓN</a>
                            <?php if ($p['status'] === 'En proceso'): ?>
                                <form method="post" class="botonaccion">
                                    <input type="hidden" name="accept_proposal" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-save"
                                        onclick="return confirm('¿Aceptar esta propuesta?');">ACEPTAR</button>
                                </form>
                                <form method="post" class="botonaccion">
                                    <input type="hidden" name="reject_proposal" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-cancel"
                                        onclick="return confirm('¿Rechazar esta propuesta?');">RECHAZAR</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <?php if ($p['status'] === 'Finalizada'): ?>
                            <?php
                            $already_rated = existsRatingForProposal($p['id'], $_SESSION['user_id']);
                            if (!$already_rated):
                                ?>
                                <div class="resenaescribir">
                                    <form method="post">
                                        <h3>Escribir una reseña</h3>
                                        <div>
                                            <label for="stars_received_<?= $p['id'] ?>">Estrellas:</label>
                                            <select name="stars" id="stars_received_<?= $p['id'] ?>" required>
                                                <option value="">Selecciona</option>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="rate_description_received_<?= $p['id'] ?>">Descripción:</label>
                                            <textarea name="rate_description" id="rate_description_received_<?= $p['id'] ?>" rows="2"
                                                required></textarea>
                                        </div>
                                        <input type="hidden" name="proposal_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="ratee_id" value="<?= $p['interested_id'] ?>">
                                        <button type="submit" name="rate_user" class="btn-save">Enviar reseña</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="resenaescribir">
                                    <p class="hola"><strong>✓ Ya has reseñado esta transacción</strong></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </main>

</body>

</html>