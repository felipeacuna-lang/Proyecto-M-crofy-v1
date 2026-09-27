<?php
session_start(); // Arrancamos la sesión antes de cualquier código HTML
require 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Buscamos al usuario en la base de datos usando su correo
    $sql = "SELECT id, nombre, password FROM usuarios WHERE email = :email";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    // Obtenemos los datos del usuario en un arreglo
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Comprobamos si el usuario existe y si la contraseña coincide con el hash guardado
    if ($usuario && password_verify($password, $usuario['password'])) {
        // Credenciales correctas: guardamos sus datos en variables de sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre_usuario'] = $usuario['nombre'];
        
        // Lo enviamos directo a la pantalla principal del negocio
        header("Location: panel.php");
        exit;
    } else {
        $error = "<div class='error'>Correo o contraseña incorrectos.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .contenedor { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        button:hover { background: #218838; }
        .error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px;}
        p { text-align: center; font-size: 14px; margin-top: 15px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="contenedor">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="img/logo.png" alt="Mícrofy" style="max-height: 200px; width: auto;">
        </div>
        <h2 style="margin-top: 0;">Ingreso a Mícrofy</h2>
        
        <?php if(!empty($error)) echo $error; ?>

        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
        </form>
        
        <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>
<!-- ========================================== -->
    <!-- SCRIPT UNIVERSAL DE MODO OSCURO Y BURBUJA  -->
    <!-- ========================================== -->
    <script>
        // 1. Inyectamos los estilos (Modo oscuro + Burbuja flotante)
        const estiloGlobal = document.createElement('style');
        estiloGlobal.innerHTML = `
            /* Modo Oscuro */
            body.dark-mode { background-color: #121212 !important; color: #e0e0e0 !important; }
            body.dark-mode .contenedor, body.dark-mode .tarjeta, body.dark-mode .card-producto, 
            body.dark-mode .carrito-panel, body.dark-mode .resumen-pago, body.dark-mode .filtro-fechas { 
                background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; box-shadow: 0 4px 15px rgba(0,0,0,0.6) !important; 
            }
            body.dark-mode input, body.dark-mode select, body.dark-mode table td { background-color: #2c2c2c !important; color: #fff !important; border-color: #444 !important; }
            body.dark-mode table th { background-color: #333 !important; color: #fff !important; border-bottom: 2px solid #555 !important; }
            body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, body.dark-mode h4 { color: #fff !important; }
            body.dark-mode .header, body.dark-mode .header-reporte { background-color: #000 !important; border-bottom: 1px solid #333 !important; }
            body.dark-mode .item-carrito { border-bottom-color: #444 !important; }
            body.dark-mode .stock { background-color: #333 !important; color: #bbb !important; }
            body.dark-mode .precio { color: #4ade80 !important; }
            body.dark-mode .grafica-contenedor { background-color: #1e1e1e !important; border-color: #333 !important; }

            /* Botón del Tema (Sol/Luna) */
            .btn-theme-toggle {
                position: fixed; bottom: 20px; left: 20px; width: 50px; height: 50px; border-radius: 50%;
                background: #fff; color: #333; border: 2px solid #ccc; cursor: pointer; font-size: 24px;
                display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
                z-index: 9999; transition: transform 0.3s, background 0.3s;
            }
            .btn-theme-toggle:hover { transform: scale(1.1); }
            body.dark-mode .btn-theme-toggle { background: #333; color: #f1c40f; border-color: #555; }

            /* Burbuja Flotante de Mícrofy */
            .burbuja-logo {
                position: fixed;
                top: 15px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(255, 255, 255, 0.85); /* Semitransparente */
                backdrop-filter: blur(5px); /* Efecto cristalino */
                padding: 6px 25px;
                border-radius: 30px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9998;
                pointer-events: none; /* TRUCO MÁGICO: Los clics la atraviesan, no estorba nada */
                transition: 0.3s;
            }
            body.dark-mode .burbuja-logo {
                background: rgba(30, 30, 30, 0.85);
                box-shadow: 0 4px 12px rgba(0,0,0,0.5);
                border: 1px solid #444;
            }
            .burbuja-logo img { height: 35px; width: auto; display: block; }

            @media print { 
                .btn-theme-toggle, .burbuja-logo { display: none !important; } 
            }
        `;
        document.head.appendChild(estiloGlobal);

        // 2. Lógica del Botón de Modo Oscuro
        const themeBtn = document.createElement('button');
        themeBtn.className = 'btn-theme-toggle no-imprimir';
        themeBtn.title = 'Alternar Tema';
        document.body.appendChild(themeBtn);

        let isDark = localStorage.getItem('theme') !== 'light'; 
        function applyTheme() {
            if (isDark) {
                document.body.classList.add('dark-mode');
                themeBtn.innerHTML = '☀️'; 
            } else {
                document.body.classList.remove('dark-mode');
                themeBtn.innerHTML = '🌙'; 
            }
        }
        themeBtn.onclick = () => {
            isDark = !isDark;
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            applyTheme();
        };
        applyTheme();

        // 3. Inyectar la Burbuja del Logo (Solo si NO es Login ni Registro)
        const rutaActual = window.location.pathname.toLowerCase();
        if (!rutaActual.includes('login.php') && !rutaActual.includes('registro.php')) {
            const burbuja = document.createElement('div');
            burbuja.className = 'burbuja-logo no-imprimir';
            burbuja.innerHTML = '<img src="img/logo.png" alt="Mícrofy">';
            document.body.appendChild(burbuja);
        }
    </script>
</body>
</html>