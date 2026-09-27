<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
$mensaje = '';
$mi_usuario_id =$_SESSION['usuario_id'];

// PROCESAR LA VENTA DEL CARRITO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['carrito_json'])) {
    $carrito = json_decode($_POST['carrito_json'], true);
    
    if (is_array($carrito) && count($carrito) > 0) {
        try {
            $conexion->beginTransaction();$gran_total = 0;

            // Validar stock real en BD
            foreach ($carrito as$item) {
                $stmt =$conexion->prepare("SELECT precio, stock FROM productos WHERE id = :id AND usuario_id = :usuario_id");
                $stmt->bindParam(':id', $item['id']);$stmt->bindParam(':usuario_id', $mi_usuario_id);$stmt->execute();
                $prod_bd =$stmt->fetch(PDO::FETCH_ASSOC);

                if (!$prod_bd || $prod_bd['stock'] <$item['cantidad']) {
                    throw new Exception("Stock insuficiente para uno de los productos.");
                }
                $gran_total += ($prod_bd['precio'] *$item['cantidad']);
            }

            // Insertar Ventas y Detalles
            $stmt_venta =$conexion->prepare("INSERT INTO ventas (usuario_id, total) VALUES (:usuario_id, :total)");
            $stmt_venta->bindParam(':usuario_id', $mi_usuario_id);$stmt_venta->bindParam(':total', $gran_total);$stmt_venta->execute();
            $id_venta =$conexion->lastInsertId();

            foreach ($carrito as$item) {
                $stmt =$conexion->prepare("SELECT precio, stock FROM productos WHERE id = :id");
                $stmt->bindParam(':id', $item['id']);$stmt->execute();
                $prod_bd =$stmt->fetch(PDO::FETCH_ASSOC);

                $subtotal = $prod_bd['precio'] *$item['cantidad'];

                $stmt_detalle =$conexion->prepare("INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (:venta_id, :producto_id, :cantidad, :precio, :subtotal)");
                $stmt_detalle->bindParam(':venta_id', $id_venta);$stmt_detalle->bindParam(':producto_id', $item['id']);$stmt_detalle->bindParam(':cantidad', $item['cantidad']);$stmt_detalle->bindParam(':precio', $prod_bd['precio']);$stmt_detalle->bindParam(':subtotal', $subtotal);$stmt_detalle->execute();

                $nuevo_stock = $prod_bd['stock'] -$item['cantidad'];
                $stmt_stock =$conexion->prepare("UPDATE productos SET stock = :nuevo_stock WHERE id = :id");
                $stmt_stock->bindParam(':nuevo_stock', $nuevo_stock);$stmt_stock->bindParam(':id', $item['id']);$stmt_stock->execute();
            }

            $conexion->commit();$mensaje = "<div class='exito'>✅ Venta registrada con éxito. Total: $" . number_format($gran_total, 2) . "</div>";
        } catch (Exception $e) {
            $conexion->rollBack();$mensaje = "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Consultar los productos
$productos_disponibles = [];
try {
    $sql_listar = "SELECT * FROM productos WHERE stock > 0 AND usuario_id = :usuario_id ORDER BY nombre ASC";
    $stmt =$conexion->prepare($sql_listar);$stmt->bindParam(':usuario_id', $mi_usuario_id);$stmt->execute();
    $productos_disponibles =$stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - Punto de Venta</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1a1a1a; color: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .nav-links a { color: white; text-decoration: none; padding: 10px 15px; background: #333; border-radius: 5px; margin-left: 10px; font-weight: 500;}
        .nav-links a:hover { background: #007bff; }
        .pos-container { display: grid; grid-template-columns: 2.5fr 1fr; gap: 20px; }
/* Modificamos la grilla para que los elementos se alineen al inicio y no se estiren */
        .grid-productos { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); 
            gap: 15px; 
            max-height: 75vh; 
            overflow-y: auto; 
            padding-right: 10px;
            align-content: start; 
        }
        
        /* Aseguramos que la tarjeta mantenga una forma consistente */
        .card-producto { 
            background: white; 
            border: 2px solid transparent; 
            border-radius: 10px; 
            padding: 15px; 
            text-align: center; 
            cursor: pointer; 
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            height: fit-content; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* ¡Aquí está la animación de vuelta! */
        .card-producto:hover { 
            border-color: #007bff; 
            transform: translateY(-5px); 
            box-shadow: 0 8px 15px rgba(0,0,0,0.15); 
        }
        .card-producto img { width: 100%; height: 100px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .precio { font-size: 18px; font-weight: bold; color: #28a745; margin: 5px 0;}
        .stock { font-size: 12px; color: #666; font-weight: bold; background: #e9ecef; padding: 3px 8px; border-radius: 12px; display: inline-block; margin-top: 5px;}

        /* ANIMACIÓN DE ERROR / AGOTADO */
        @keyframes agitarRojo {
            0% { transform: translateX(0); background-color: white; border-color: transparent; }
            25% { transform: translateX(-5px); background-color: #ffe6e6; border-color: #dc3545; }
            50% { transform: translateX(5px); background-color: #ffe6e6; border-color: #dc3545; }
            75% { transform: translateX(-5px); background-color: #ffe6e6; border-color: #dc3545; }
            100% { transform: translateX(0); background-color: white; border-color: transparent; }
        }
        .animacion-roja {
            animation: agitarRojo 0.4s ease;
        }

        .carrito-panel { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 75vh;}
        .lista-carrito { flex-grow: 1; overflow-y: auto; margin-bottom: 15px; border-bottom: 2px dashed #eee; padding-bottom: 10px; }
        .item-carrito { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 14px; border-bottom: 1px solid #f0f0f0; padding-bottom: 5px;}
        .resumen-pago { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .resumen-pago h3 { margin: 0 0 15px 0; display: flex; justify-content: space-between; color: #333;}
        .input-pago { width: 100%; padding: 12px; font-size: 18px; text-align: right; margin-bottom: 10px; border: 2px solid #ccc; border-radius: 6px; box-sizing: border-box;}
        .cambio { font-size: 20px; font-weight: bold; color: #dc3545; display: flex; justify-content: space-between; margin-bottom: 15px;}
        .btn-cobrar { width: 100%; padding: 15px; background: #28a745; color: white; border: none; border-radius: 6px; font-size: 18px; font-weight: bold; cursor: pointer; }
        .btn-cobrar:hover { background: #218838; }
        .btn-cobrar:disabled { background: #ccc; cursor: not-allowed; }
        .btn-eliminar-item { background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; padding: 5px 10px; font-size: 12px; transition: 0.2s;}
        .btn-eliminar-item:hover { background: #c82333; transform: scale(1.1); }
        .exito { color: #155724; background-color: #d4edda; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 18px; text-align: center; font-weight: bold;}
    </style>
</head>
<body>

    <div class="header">
        <h2>Mi Caja Registradora</h2>
        <div class="nav-links"><a href="panel.php">📦 Volver al Inventario</a></div>
    </div>

    <?php if(!empty($mensaje)) echo$mensaje; ?>

    <div class="pos-container">
        
        <div class="grid-productos">
            <?php foreach ($productos_disponibles as$prod): ?>
                <!-- A cada tarjeta le ponemos un ID único (card-prod-ID) -->
                <div class="card-producto" id="card-prod-<?php echo $prod['id']; ?>" onclick="agregarAlCarrito(<?php echo $prod['id']; ?>, '<?php echo addslashes($prod['nombre']); ?>', <?php echo $prod['precio']; ?>, <?php echo$prod['stock']; ?>)">
                    <?php if($prod['imagen']): ?>
                        <img src="<?php echo $prod['imagen']; ?>" alt="Foto">
                    <?php else: ?>
                        <div style="height:100px; background:#ddd; border-radius:8px; line-height:100px; color:#888;">Sin foto</div>
                    <?php endif; ?>
                    <div style="font-weight: 500; font-size: 14px;"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                    <div class="precio">$<?php echo number_format($prod['precio'], 2); ?></div>
                    <!-- Al texto del stock le ponemos un ID único (stock-ID) para cambiarlo con JS -->
                    <div class="stock" id="stock-<?php echo $prod['id']; ?>">Disponibles: <?php echo $prod['stock']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="carrito-panel">
            <h3 style="margin-top:0; border-bottom:2px solid #007bff; padding-bottom:10px;">Ticket Actual</h3>
            <div class="lista-carrito" id="lista-carrito">
                <p style="color:#888; text-align:center; margin-top:50px;">Haz clic en un producto para agregarlo</p>
            </div>
            <div class="resumen-pago">
                <h3><span>TOTAL:</span> <span id="total-text" style="color:#28a745;">$0.00</span></h3>
                <label style="font-weight:bold; font-size:14px; color:#555;">Efectivo recibido:</label>
                <input type="number" id="pago-recibido" class="input-pago" placeholder="$ 0.00" onkeyup="calcularCambio()">
                <div class="cambio">
                    <span>Cambio a devolver:</span>
                    <span id="cambio-text">$0.00</span>
                </div>
                <form action="ventas.php" method="POST" id="form-venta">
                    <input type="hidden" name="carrito_json" id="carrito_json">
                    <button type="submit" class="btn-cobrar" id="btn-cobrar" disabled>Confirmar Venta</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let carrito = [];
        let totalCarrito = 0;

        function agregarAlCarrito(id, nombre, precio, stockMaximo) {
            let itemExistente = carrito.find(item => item.id === id);
            let cantidadActual = itemExistente ? itemExistente.cantidad : 0;

            if (cantidadActual < stockMaximo) {
                if (itemExistente) {
                    itemExistente.cantidad++;
                } else {
                    carrito.push({
                        id: id,
                        nombre: nombre,
                        precio: parseFloat(precio),
                        cantidad: 1,
                        stockMaximo: stockMaximo
                    });
                }
                
                // RESTAMOS VISUALMENTE EN LA TARJETA
                let stockRestante = stockMaximo - (cantidadActual + 1);
                document.getElementById('stock-' + id).innerText = 'Disponibles: ' + stockRestante;

                // SI QUEDA EN CERO, HACEMOS TEMBLAR LA TARJETA EN ROJO
                if (stockRestante === 0) {
                    animarTarjetaError(id);
                }
            } else {
                // SI YA NO HAY Y LE VUELVEN A DAR CLIC, TEMBLOR ROJO INMEDIATO
                animarTarjetaError(id);
            }
            actualizarInterfaz();
        }

        function eliminarDelCarrito(id) {
            let itemIndex = carrito.findIndex(item => item.id === id);

            if (itemIndex !== -1) {
                let item = carrito[itemIndex];
                let stockOriginal = item.stockMaximo; // Guardamos esto antes de modificar

                if (item.cantidad > 1) {
                    item.cantidad--;
                } else {
                    carrito.splice(itemIndex, 1);
                }
                
                // DEVOLVEMOS EL STOCK A LA TARJETA VISUALMENTE
                let itemActualizado = carrito.find(i => i.id === id);
                let cantidadNueva = itemActualizado ? itemActualizado.cantidad : 0;
                let stockRestante = stockOriginal - cantidadNueva;
                
                let elementoStock = document.getElementById('stock-' + id);
                if (elementoStock) {
                    elementoStock.innerText = 'Disponibles: ' + stockRestante;
                }
            }
            actualizarInterfaz();
        }

        function animarTarjetaError(id) {
            let tarjeta = document.getElementById('card-prod-' + id);
            if (tarjeta) {
                tarjeta.classList.remove('animacion-roja');
                void tarjeta.offsetWidth; // Truco de JS para reiniciar animaciones
                tarjeta.classList.add('animacion-roja');
            }
        }

        function actualizarInterfaz() {
            let contenedorLista = document.getElementById('lista-carrito');
            let jsonInput = document.getElementById('carrito_json');
            let btnCobrar = document.getElementById('btn-cobrar');
            
            contenedorLista.innerHTML = '';
            totalCarrito = 0;

            if (carrito.length === 0) {
                contenedorLista.innerHTML = '<p style="color:#888; text-align:center; margin-top:50px;">Haz clic en un producto para agregarlo</p>';
                btnCobrar.disabled = true;
                jsonInput.value = '';
                document.getElementById('total-text').innerText = '$0.00';
                calcularCambio();
                return;
            }

            carrito.forEach((item) => {
                let subtotal = item.precio * item.cantidad;
                totalCarrito += subtotal;

                contenedorLista.innerHTML += `
                    <div class="item-carrito">
                        <div style="flex-grow: 1;">
                            <strong>${item.nombre}</strong><br>
                            <span style="color:#666;">${item.cantidad} x $${item.precio.toLocaleString('es-CO')}</span>
                        </div>
                        <div style="font-weight:bold; margin-right: 15px;">
                            $${subtotal.toLocaleString('es-CO')}
                        </div>
                        <button type="button" class="btn-eliminar-item" onclick="eliminarDelCarrito(${item.id})" title="Quitar 1 unidad">
                            🗑️
                        </button>
                    </div>
                `;
            });

            document.getElementById('total-text').innerText = '$' + totalCarrito.toLocaleString('es-CO');
            jsonInput.value = JSON.stringify(carrito);
            btnCobrar.disabled = false;
            
            calcularCambio();
        }

        function calcularCambio() {
            let pago = parseFloat(document.getElementById('pago-recibido').value) || 0;
            let cambio = pago - totalCarrito;
            let textoCambio = document.getElementById('cambio-text');

            if (carrito.length === 0) {
                textoCambio.innerText = '$0.00';
                textoCambio.style.color = '#dc3545';
                document.getElementById('pago-recibido').value = ''; 
                return;
            }

            if (pago >= totalCarrito) {
                textoCambio.innerText = '$' + cambio.toLocaleString('es-CO');
                textoCambio.style.color = '#28a745';
                document.getElementById('btn-cobrar').disabled = false;
            } else {
                textoCambio.innerText = 'Falta dinero';
                textoCambio.style.color = '#dc3545';
                document.getElementById('btn-cobrar').disabled = true;
            }
        }
    </script>
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