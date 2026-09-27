<?php
require 'conexion.php'; // Traemos la conexión a la base de datos

$mensaje = ''; // Variable para mostrar alertas al usuario

// Verificamos si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    // Encriptamos la contraseña por seguridad antes de guardarla
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    try {
        // Preparamos la consulta SQL para insertar el usuario
        $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password)";
        $stmt = $conexion->prepare($sql);
        
        // Asignamos los valores
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);

        // Ejecutamos y verificamos si se guardó
        if ($stmt->execute()) {
            $mensaje = "<div class='exito'>Usuario registrado exitosamente. Ya puedes iniciar sesión.</div>";
        }
    } catch (PDOException $e) {
        // El código 23000 en MySQL significa que hay un dato duplicado (en este caso el email)
        if ($e->getCode() == 23000) { 
            $mensaje = "<div class='error'>Error: El correo electrónico ya está registrado.</div>";
        } else {
            $mensaje = "<div class='error'>Error al registrar: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Administrador</title>
    <style>
        /* Estilos básicos para que no se vea feo */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .contenedor { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        button:hover { background: #0056b3; }
        .error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px;}
        .exito { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px;}
        p { text-align: center; font-size: 14px; margin-top: 15px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="contenedor">
        <h2>Crear Cuenta</h2>
        
        <!-- Aquí mostramos los mensajes de error o éxito -->
        <?php if(!empty($mensaje)) echo $mensaje; ?>

        <form action="registro.php" method="POST">
            <input type="text" name="nombre" placeholder="Nombre de tu negocio o tuyo" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Registrar Administrador</button>
        </form>
        
        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
<!-- ========================================== -->
    <!-- SCRIPT UNIVERSAL DE MODO OSCURO Y BURBUJA  -->
    <!-- ========================================== -->
    <script>
        // 1. Inyectamos los estilos
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

            /* Burbuja Flotante Interactiva de Mícrofy */
            .burbuja-logo {
                position: fixed;
                top: 15px;
                left: 50%;
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(5px);
                padding: 10px 30px;
                border-radius: 20px; /* Bordes redondeados */
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 9998;
                cursor: pointer; /* Indica que se puede hacer clic */
                
                /* Efecto de entrada y salida suave */
                transform: translateX(-50%);
                opacity: 1;
                transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s;
            }
            
            /* Estado oculto de la burbuja (se esconde hacia arriba) */
            .burbuja-logo.escondida {
                transform: translate(-50%, -120%); /* Sube fuera de la pantalla */
                opacity: 0;
                pointer-events: none; /* No estorba cuando está oculta */
            }

            body.dark-mode .burbuja-logo {
                background: rgba(30, 30, 30, 0.9);
                box-shadow: 0 4px 15px rgba(0,0,0,0.6);
                border: 1px solid #444;
            }
            
            /* Imagen ampliada a ~100px */
            .burbuja-logo img { height: 100px; width: auto; display: block; transition: 0.2s;}
            .burbuja-logo:hover img { transform: scale(1.05); } /* Pequeño zoom al pasar el mouse por encima */

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

        // 3. Lógica dinámica de la Burbuja del Logo
        const rutaActual = window.location.pathname.toLowerCase();
        if (!rutaActual.includes('login.php') && !rutaActual.includes('registro.php')) {
            const burbuja = document.createElement('div');
            // Inicia con la clase "escondida" para que no aparezca de golpe
            burbuja.className = 'burbuja-logo escondida no-imprimir'; 
            burbuja.innerHTML = '<img src="img/logo.png" alt="Mícrofy">';
            
            // Función para ir al panel cuando le dan clic
            burbuja.onclick = () => {
                window.location.href = 'panel.php';
            };
            
            document.body.appendChild(burbuja);

            let temporizador;

            // Función que la muestra y programa su desaparición
            function mostrarBurbuja() {
                burbuja.classList.remove('escondida');
                clearTimeout(temporizador);
                // Si el mouse no vuelve a moverse arriba en 2.5 segundos, se esconde
                temporizador = setTimeout(() => {
                    burbuja.classList.add('escondida');
                }, 2500); 
            }

            // Detectar cuando el mouse se mueve en la franja superior de la pantalla (primeros 150 pixeles)
            document.addEventListener('mousemove', (e) => {
                if (e.clientY < 150) {
                    mostrarBurbuja();
                }
            });

            // Si el mouse está posado directamente sobre la burbuja, cancelamos el temporizador para que no se desaparezca
            burbuja.addEventListener('mouseenter', () => clearTimeout(temporizador));
            burbuja.addEventListener('mouseleave', () => mostrarBurbuja());
        }
    </script>
</body>
</html>