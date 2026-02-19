<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cesar's Flan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #000000ff; 
            margin: 0;
            height: 100vh;
            display: flex;
            position: relative;
            overflow: hidden;
        }
        
        .image-container {
            width: 65%;
            height: 100%;
            display: flex;
            justify-content: left;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        
        .background-image {
            width: 90%;
            height: 100%;
            object-fit: cover;
            object-position: left center;
        }
        
        .login-section {
            width: 35%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background-color: rgba(255, 255, 255, 1);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(232, 220, 128, 1);
            width: 100%;
            max-width: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        
        .login-container h1 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
            font-size: 24px;
        }
        
        .input-group {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: #1b1b1bee;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-container button:hover {
            background-color: #000000ff;
        }
        
        .brand {
            margin-top: 20px;
            color: #333;
            text-align: center;
        }

        /* Estilos para alertas personalizadas */
        .alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }

        .alert-box {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .alert-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .alert-success .alert-icon {
            color: #28a745;
        }

        .alert-error .alert-icon {
            color: #dc3545;
        }

        .alert-warning .alert-icon {
            color: #ffc107;
        }

        .alert-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .alert-message {
            font-size: 16px;
            margin-bottom: 20px;
            color: #666;
            line-height: 1.4;
        }

        .alert-button {
            background-color: #1b1b1bee;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .alert-button:hover {
            background-color: #000000ff;
        }
    </style>
</head>
<body>
    <!-- Overlay para alertas -->
    <div class="alert-overlay" id="alertOverlay">
        <div class="alert-box" id="alertBox">
            <div class="alert-icon" id="alertIcon"></div>
            <div class="alert-title" id="alertTitle"></div>
            <div class="alert-message" id="alertMessage"></div>
            <button class="alert-button" onclick="closeAlert()">Aceptar</button>
        </div>
    </div>

    <div class="image-container">
        <img src="./Imagenes/Logo.jpg" class="background-image" alt="Logo de Cesar's Flan">
    </div>
    
    <div class="login-section">
        <div class="login-container">
            <h1>Iniciar sesión</h1>
            <form method="post" action="process_login.php" autocomplete="off" id="loginForm">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Nombre de usuario" required autocomplete="off">
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Contraseña" required autocomplete="off">
                </div>
                <button type="submit">Ingresar</button>
            </form>
            <div class="brand">
                <h4>Cesar's Flan</h4>
            </div>
        </div>
    </div>

    <script>
        // Función para mostrar alertas personalizadas
        function showAlert(type, title, message) {
            const overlay = document.getElementById('alertOverlay');
            const alertBox = document.getElementById('alertBox');
            const alertIcon = document.getElementById('alertIcon');
            const alertTitle = document.getElementById('alertTitle');
            const alertMessage = document.getElementById('alertMessage');

            // Configurar según el tipo de alerta
            switch(type) {
                case 'success':
                    alertIcon.innerHTML = '✓';
                    alertBox.className = 'alert-box alert-success';
                    break;
                case 'error':
                    alertIcon.innerHTML = '✕';
                    alertBox.className = 'alert-box alert-error';
                    break;
                case 'warning':
                    alertIcon.innerHTML = '⚠';
                    alertBox.className = 'alert-box alert-warning';
                    break;
            }

            alertTitle.textContent = title;
            alertMessage.textContent = message;
            overlay.style.display = 'flex';
        }

        // Función para cerrar alerta
        function closeAlert() {
            document.getElementById('alertOverlay').style.display = 'none';
        }

        // Verificar si hay parámetros de error en la URL
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            const message = urlParams.get('message');

            if (error === 'empty_fields') {
                showAlert('warning', 'Campos vacíos', 'Por favor, completa todos los campos.');
            } else if (error === 'invalid_credentials') {
                showAlert('error', 'Credenciales incorrectas', 'Nombre de usuario o contraseña incorrectos.');
            } else if (error === 'db_error') {
                showAlert('error', 'Error del sistema', 'Ha ocurrido un error en el sistema. Por favor, intenta nuevamente.');
            } else if (error === 'logout') {
                showAlert('success', 'Sesión cerrada', 'Has cerrado sesión correctamente.');
            } else if (message) {
                showAlert('success', 'Éxito', decodeURIComponent(message));
            }
        });

        // Validación del formulario antes de enviar
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = this.username.value.trim();
            const password = this.password.value.trim();

            if (!username || !password) {
                e.preventDefault();
                showAlert('warning', 'Campos vacíos', 'Por favor, completa todos los campos.');
                return false;
            }
        });
    </script>
</body>
</html>