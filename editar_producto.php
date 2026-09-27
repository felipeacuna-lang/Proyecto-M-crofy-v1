<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mensaje = '';
$producto = null;

// Lógica para ACTUALIZAR el producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_producto'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $precio = trim($_POST['precio']);
    $stock = trim($_POST['stock']);
    
    // Obtenemos la imagen actual por si no se sube una nueva
    $ruta_imagen = $_POST['imagen_actual']; 

    // Si se subió una imagen nueva, la procesamos
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta_dest = 'img/' . $nombre_archivo;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_dest)) {
            $ruta_imagen = $ruta_dest;
        }
    }

    try {
        $sql = "UPDATE productos SET nombre = :nombre, precio = :precio, stock = :stock, imagen = :imagen WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':imagen', $ruta_imagen);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // Si se actualiza con éxito, lo regresamos al panel
            header("Location: panel.php");
            exit;
        }
    } catch (PDOException $e) {
        $mensaje = "<div class='error'>Error al actualizar: " . $e->getMessage() . "</div>";
    }
}

// Obtener los datos del producto a editar por la URL (?id=...)
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        die("Producto no encontrado.");
    }
} else {
    header("Location: panel.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; display: flex; justify-content: center; padding-top: 50px; }
        .tarjeta { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h3 { text-align: center; margin-top: 0; color: #333; }
        input, button { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { background: #28a745; color: white; border: none; cursor: pointer; font-size: 16px; font-weight: bold; transition: 0.3s; }
        button:hover { background: #218838; }
        .btn-cancelar { background: #6c757d; display: block; text-align: center; text-decoration: none; margin-top: 10px; color: white; padding: 12px; border-radius: 6px;}
        .btn-cancelar:hover { background: #5a6268; }
        .img-actual { display: block; margin: 10px auto; max-width: 150px; border-radius: 8px; }
    </style>
</head>
<body>

    <div class="tarjeta">
        <h3>Editar Producto</h3>
        
        <?php if(!empty($mensaje)) echo $mensaje; ?>

        <form action="editar_producto.php?id=<?php echo $producto['id']; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="actualizar_producto" value="1">
            <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
            <input type="hidden" name="imagen_actual" value="<?php echo $producto['imagen']; ?>">

            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
            
            <label>Precio:</label>
            <input type="number" step="0.01" name="precio" value="<?php echo $producto['precio']; ?>" required>
            
            <label>Stock:</label>
            <input type="number" name="stock" value="<?php echo $producto['stock']; ?>" required>
            
            <label>Foto actual (opcional subir nueva):</label>
            <?php if($producto['imagen']): ?>
                <img src="<?php echo $producto['imagen']; ?>" class="img-actual" alt="Imagen actual">
            <?php else: ?>
                <p style="font-size: 14px; color: #666; text-align: center;">Sin foto registrada</p>
            <?php endif; ?>
            
            <input type="file" name="imagen" accept="image/*">
            
            <button type="submit">Actualizar Cambios</button>
            <a href="panel.php" class="btn-cancelar">Cancelar / Volver</a>
        </form>
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