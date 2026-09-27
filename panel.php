<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mensaje = '';
$mi_usuario_id = $_SESSION['usuario_id']; // Identificador del negocio actual

// Lógica para AGREGAR producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_producto'])) {
    $nombre = trim($_POST['nombre']);
    $precio = trim($_POST['precio']);
    $stock = trim($_POST['stock']);
    $ruta_imagen = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta_dest = 'img/' . $nombre_archivo;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_dest)) {
            $ruta_imagen = $ruta_dest;
        }
    }

    try {
        // AHORA INSERTAMOS TAMBIÉN EL usuario_id
        $sql = "INSERT INTO productos (usuario_id, nombre, precio, stock, imagen) VALUES (:usuario_id, :nombre, :precio, :stock, :imagen)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':usuario_id', $mi_usuario_id);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':imagen', $ruta_imagen);
        
        if ($stmt->execute()) {
            $mensaje = "<div class='exito'>Producto agregado a tu negocio.</div>";
        }
    } catch (PDOException $e) {
        $mensaje = "<div class='error'>Error al agregar: " . $e->getMessage() . "</div>";
    }
}

// Lógica para ELIMINAR producto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_producto'])) {
    $id_eliminar = $_POST['id_producto'];
    try {
        // SEGURIDAD: Validamos que el producto a eliminar realmente pertenezca a este usuario
        $stmt = $conexion->prepare("DELETE FROM productos WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->bindParam(':id', $id_eliminar);
        $stmt->bindParam(':usuario_id', $mi_usuario_id);
        $stmt->execute();
        $mensaje = "<div class='exito'>Producto eliminado correctamente.</div>";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $mensaje = "<div class='error'>No puedes eliminar este producto porque ya tiene ventas registradas. Te recomendamos Editarlo y poner su stock en 0.</div>";
        } else {
            $mensaje = "<div class='error'>Error al eliminar: " . $e->getMessage() . "</div>";
        }
    }
}

// Consultar SOLO los productos de este negocio/usuario
$productos = [];
try {
    $sql_listar = "SELECT * FROM productos WHERE usuario_id = :usuario_id ORDER BY id DESC";
    $stmt = $conexion->prepare($sql_listar);
    $stmt->bindParam(':usuario_id', $mi_usuario_id);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "<div class='error'>Error al cargar tu inventario.</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a1a1a; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .nav-links a { color: white; text-decoration: none; padding: 10px 15px; background: #333; border-radius: 5px; margin-left: 10px; font-weight: 500; transition: 0.3s;}
        .nav-links a:hover { background: #4CAF50; }
        .nav-links a.btn-rojo:hover { background: #ff4c4c; }
        .contenedor-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 20px; }
        .tarjeta { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        input, button { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        input[type="file"] { padding: 8px; background: #f8f9fa; }
        button { background: #007bff; color: white; border: none; cursor: pointer; font-size: 16px; font-weight: bold; transition: 0.3s; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #333; }
        .miniatura { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .exito { color: #155724; background-color: #d4edda; padding: 15px; border-radius: 6px; margin-bottom: 20px;}
        .error { color: #721c24; background-color: #f8d7da; padding: 15px; border-radius: 6px; margin-bottom: 20px;}
        .btn-accion { padding: 6px 12px; border-radius: 4px; color: white; text-decoration: none; font-size: 14px; margin-right: 5px; display: inline-block;}
        .btn-editar { background-color: #ffc107; color: #000; }
        .btn-editar:hover { background-color: #e0a800; }
        .btn-eliminar { background-color: #dc3545; border: none; padding: 8px 12px; width: auto; font-size: 14px;}
        .btn-eliminar:hover { background-color: #c82333; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Inventario</h2>
        
        <div class="nav-links">
            <a href="ventas.php">💳 Ir a la Caja (Vender)</a>
            <a href="reportes.php" style="background-color: #17a2b8;">📊 Mis Reportes</a>
            <a href="logout.php" class="btn-rojo">Cerrar Sesión</a>
        </div>
    </div>

    <?php if(!empty($mensaje)) echo $mensaje; ?>

    <div class="contenedor-grid">
        <div class="tarjeta">
            <h3>Nuevo Producto</h3>
            <form action="panel.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="agregar_producto" value="1"> 
                <label>Nombre:</label>
                <input type="text" name="nombre" required>
                <label>Precio:</label>
                <input type="number" step="0.01" name="precio" required>
                <label>Stock inicial:</label>
                <input type="number" name="stock" required>
                <label>Foto del producto:</label>
                <input type="file" name="imagen" accept="image/*">
                <button type="submit">Guardar Producto</button>
            </form>
        </div>

        <div class="tarjeta">
            <h3>Mis Productos Registrados</h3>
            <table>
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td>
                            <?php if($producto['imagen']): ?>
                                <img src="<?php echo $producto['imagen']; ?>" class="miniatura" alt="Foto">
                            <?php else: ?>
                                <span>No img</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                        <td><?php echo $producto['stock']; ?></td>
                        <td>
                            <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn-accion btn-editar">Editar</a>
                            <form action="panel.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este producto?');">
                                <input type="hidden" name="eliminar_producto" value="1">
                                <input type="hidden" name="id_producto" value="<?php echo $producto['id']; ?>">
                                <button type="submit" class="btn-eliminar">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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