<?php

session_start();

require_once __DIR__ . '/../src/auth_functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';
    $location = $_POST['location'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($cpassword) || empty($location)) {
        $error = 'Por favor, completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo electrónico no es válido.';
    } elseif (!preg_match('/@udistrital\.edu\.co$/', $email)) {
        $error = 'Dominio de correo invalido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $cpassword) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (userExists($email)) {
        $error = 'Ya existe una cuenta registrada con este correo electrónico.';
    } else {
        // Intentar registrar al usuario
        $result = signUp($name, $email, $password, $location);

        if ($result['success']) {
            $success = $result['message'];
            $_SESSION['message'] = $success;
            header('Location: login.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// Si ya hay sesión activa, redirigir según rol
if (isset($_SESSION['user_id'])) {
    if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header('Location: adminpanel.php');
        exit();
    } else if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'banned') {
        header('Location: banned.php');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta</title>
    <link rel="icon" href="img/icon.png">
    <link rel="stylesheet" href="style.css">

    <style>
        html {
            margin: 0;
            padding: 0;
            background-color: white;
        }

        body {
            margin: 0 auto;
            padding: 0;
            font-family: 'HovesDemiBold';
            align-items: center;
            justify-content: center;
        }

        main {
            max-width: 1440px;
            min-width: 200px;
            width: 100%;
            height: auto;
            display: flex;
            flex-direction: column;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            position: relative;
        }

        .background {
            position: fixed;
            width: 100%;
            max-width: 100dvw;
            height: auto;
            opacity: 55%;
            z-index: -4;
        }

        @media (max-width: 750px) {
            .auth-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .getbackson {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 1rem;
                justify-content: center;
            }
        }

        .auth-container {
            margin: 0 auto;
        }
    </style>


</head>

<body>

    <img src="img/background.png" class="background">


    <main>

        <style>
            .getback {
                display: flex;
                width: 42%;
                flex-direction: row;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: space-between;
                margin: 0 auto;
                padding: 3.2% 0 2% 0;
            }

            .getbackson1 {
                width: 45%;
                margin: 0 auto;
            }

            .getbackson2 {
                width: 60%;
                margin: 0 auto;
                justify-content: flex-start;
            }

            .getbackson {
                width: 95%;
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                color: white;
                text-decoration: none;
                font-size: 20px;
                align-items: center;
                justify-content: flex-start;
                padding: 0 0 0 0;
                margin: 0;
                transition: 5s;
                color: #15152e;
                box-sizing: border-box;
            }

            .getbackson:hover {
                color: #292929cc
            }

            @media (max-width: 750px) {

                .getback {
                    padding: 0;
                    display: flex;
                    flex-direction: column;
                    flex-wrap: nowrap;
                    width: 95%;
                }

                .getbackson {
                    justify-content: center;
                    font-size: 15px;
                    padding: 1.5rem 0 1.5rem 0;
                    margin: 0;
                }

                .getbackson1 {
                    width: 90%;
                }

                .getbackson2 {
                    width: 90%;
                    font-size: 10px;
                }
            }
        </style>


        <div class="getback">

            <div class="getbackson1">
                <a href="index.php" class="getbackson">
                    <svg width="25" height="25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    VOLVER AL INICIO
                </a>
            </div>

            <div class="getbackson2">
                <?php if (!empty($_SESSION['message'])): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($_SESSION['message']); ?>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>


        <style>
            .auth-container {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                margin: 0 0 2rem 0;
            }

            .auth-card {
                box-sizing: border-box;
                background-color: #64646402;
                border-radius: .8rem;
                border: 1px solid rgba(99, 99, 99, 0.66);
                backdrop-filter: blur(38px);
                width: 42%;
                padding: 2.5rem 3rem 3.5rem 3rem;
            }

            .auth-header {
                width: 85%;
                display: flex;
                flex-direction: column;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: space-between;
                margin: 0 auto 1.5rem auto;

                p {
                    margin: 0;
                    padding: 0;
                    display: block;
                    font-size: 16px;
                    color: #15152e;
                }

                h1 {
                    margin: 0;
                    padding: 0;
                    font-size: 26px;
                    color: #15152e;
                }
            }

            .formulario {
                width: 72%;
                display: flex;
                flex-direction: column;
                flex-wrap: nowrap;
                justify-content: center;
                align-items: center;
                box-sizing: border-box;
                margin: 0 auto;
                gap: 16px;
            }

            .form-group {
                width: 100%;
                display: flex;
                flex-direction: column;
                flex-wrap: nowrap;
                align-items: center;
                justify-content: center;
                margin: 1rem 0 1rem 0;

                label {
                    text-align: start;
                    align-self: flex-start;
                    color: #303030;
                    margin: 0 0 5px 10px;
                }
            }

            .password-container {
                position: relative;
                width: 100%;
            }

            .form-control {
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
            }

            .toggle-password {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                cursor: pointer;
                color: #666;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                width: 20px;
                height: 20px;
                transition: color 0.2s;
            }

            .toggle-password:hover {
                color: #333;
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
            }

            .auth-button {
                width: 58%;
                background-color: #08083069;
                backdrop-filter: blur(5px);
                padding: 2%;
                border: none;
                border: 1px solid rgba(99, 99, 99, 0.71);
                border-radius: 10px;
                margin-top: 5%;
                color: #333333;
                font-family: "HovesDemiBold";
                font-size: 16px;
                cursor: pointer;
            }

            .auth-links {
                color: #333333;

                a {
                    text-decoration: none;
                    color: #15152e;
                    transition: 3s;
                }
            }

            a:hover {
                color: #000000;
            }

            select {
                background-color: transparent;
                border: 1px solid #333333;
                font-family: 'HovesDemiBold';
                color: #333333;
                padding: 0 1rem 0 1rem;
            }

            @media (max-width: 750px) {
                .auth-card {
                    box-sizing: border-box;
                    background-color: #64646402;
                    border-radius: 10px;
                    border: .8px solid rgba(99, 99, 99, 0.66);
                    backdrop-filter: blur(80px);
                    width: 88%;
                    padding: 2.2rem 1rem 3rem 1rem;
                }

                .auth-header {
                    width: 90%;
                    display: flex;
                    flex-direction: column;
                    flex-wrap: nowrap;
                    align-items: center;
                    justify-content: space-between;
                    margin: 2% auto 14% auto;

                    p {
                        margin: 0;
                        padding: 0;
                        display: block;
                        font-size: 16px;
                        color: #333333;
                    }

                    h1 {
                        margin: 0;
                        padding: 0;
                        font-size: 20px;
                        color: #333333;
                    }
                }

                .formulario {
                    width: 98%;
                    display: flex;
                    flex-direction: column;
                    flex-wrap: nowrap;
                    justify-content: center;
                    align-items: center;
                    box-sizing: border-box;
                    margin: 0 auto;
                    gap: 25px;
                }

                .form-group {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    flex-wrap: nowrap;
                    align-items: center;
                    justify-content: center;

                    label {
                        text-align: start;
                        color: #303030;
                        margin: 0 0 5px 10px;
                        font-size: 14px;
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
                    text-align: center;
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
                    text-align: center;
                }

                .auth-button {
                    width: 90%;
                    background-color: #ffffff57;
                    backdrop-filter: blur(5px);
                    padding: 2%;
                    border: none;
                    border: 1px solid rgba(99, 99, 99, 0.71);
                    border-radius: 10px;
                    margin-top: 4%;
                    color: #333333;
                    font-family: "HovesDemiBold";
                    font-size: 20px;
                    cursor: pointer;
                }



            }
        </style>


        <div class="auth-container <?php echo $error ? 'has-error' : ($success ? 'has-success' : ''); ?>">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Crea tu cuenta</h1>
                    <p>Libros al alcance de un click</p>
                </div>

                <form method="POST" action="">

                    <div class="form-group">
                        <label for="name">¿Cuál es tu nombre?</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo institucional</label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="password">Crea una contraseña</label>

                        <div class="password-container">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <svg class="eye-icon" width="18" height="18" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cpassword">Confirma tu contraseña</label>
                        <div class="password-container">
                            <input type="password" id="cpassword" name="cpassword" class="form-control" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('cpassword')">
                                <svg class="eye-icon" width="18" height="18" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </button>
                        </div>

                    </div>

                    <div class="form-group full-width">
                        <label for="location">Localidad de residencia</label>
                        <select id="location" name="location" class="form-control" required>
                            <option value="" disabled selected>Selecciona tu localidad</option>
                            <option value="Usaquén">Usaquén</option>
                            <option value="Chapinero">Chapinero</option>
                            <option value="Santa Fe">Santa Fe</option>
                            <option value="San Cristóbal">San Cristóbal</option>
                            <option value="Usme">Usme</option>
                            <option value="Tunjuelito">Tunjuelito</option>
                            <option value="Bosa">Bosa</option>
                            <option value="Kennedy">Kennedy</option>
                            <option value="Fontibón">Fontibón</option>
                            <option value="Engativá">Engativá</option>
                            <option value="Suba">Suba</option>
                            <option value="Barrios Unidos">Barrios Unidos</option>
                            <option value="Teusaquillo">Teusaquillo</option>
                            <option value="Los Mártires">Los Mártires</option>
                            <option value="Antonio Nariño">Antonio Nariño</option>
                            <option value="Puente Aranda">Puente Aranda</option>
                            <option value="La Candelaria">La Candelaria</option>
                            <option value="Rafael Uribe Uribe">Rafael Uribe Uribe</option>
                            <option value="Ciudad Bolívar">Ciudad Bolívar</option>
                            <option value="Sumapaz">Sumapaz</option>
                            <option value="Sumapaz">Fuera de Bogotá</option>
                        </select>
                    </div>

                    <button type="submit" class="auth-button full-width" href="index.php">CREAR CUENTA</button>

                </form>

                <div class="auth-links">
                    <p>¿Ya tienes una cuenta? <a href="login.php">INICIA SESIÓN</a></p>
                </div>
                
                <div class="auth-links">
                    <p>Conoce los <a href="signup.php"><a href="conditions.php">TERMINOS DE USO</a></p>
                </div>
            </div>
        </div>

        <script>
            function togglePassword(inputId) {
                const input = document.getElementById(inputId);
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
            }
        </script>

</body>

</html>