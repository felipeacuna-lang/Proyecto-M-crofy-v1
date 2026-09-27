<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mi_usuario_id = $_SESSION['usuario_id'];

// Detectamos si estamos en modo comparativa a través de la URL
$modo_comparativa = isset($_GET['modo']) && $_GET['modo'] == 'comparativa'; 

// ==========================================
// MODO 1: COMPARATIVA ANUAL PERSONALIZADA
// ==========================================
if ($modo_comparativa) {
    // Si el usuario eligió años, los usamos. Si no, usamos el año actual y el anterior por defecto.
    $anio1 = isset($_GET['anio1']) ? (int)$_GET['anio1'] : (int)date('Y');
    $anio2 = isset($_GET['anio2']) ? (int)$_GET['anio2'] : $anio1 - 1;

    $ventas_anio1 = array_fill(1, 12, 0);
    $ventas_anio2 = array_fill(1, 12, 0);

    try {
        // Consultamos el Año Principal (Año 1)
        $stmt = $conexion->prepare("SELECT MONTH(fecha_venta) as mes, SUM(total) as total FROM ventas WHERE YEAR(fecha_venta) = :anio AND usuario_id = :uid GROUP BY MONTH(fecha_venta)");
        $stmt->execute([':anio' => $anio1, ':uid' => $mi_usuario_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $ventas_anio1[$row['mes']] = (float)$row['total']; }

        // Consultamos el Año a Comparar (Año 2)
        $stmt = $conexion->prepare("SELECT MONTH(fecha_venta) as mes, SUM(total) as total FROM ventas WHERE YEAR(fecha_venta) = :anio AND usuario_id = :uid GROUP BY MONTH(fecha_venta)");
        $stmt->execute([':anio' => $anio2, ':uid' => $mi_usuario_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $ventas_anio2[$row['mes']] = (float)$row['total']; }
    } catch(PDOException $e) {}

    $total_anio1 = array_sum($ventas_anio1);
    $total_anio2 = array_sum($ventas_anio2);

    $labels_grafica = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $datos_grafica_anio1 = array_values($ventas_anio1);
    $datos_grafica_anio2 = array_values($ventas_anio2);

// ==========================================
// MODO 2: REPORTE NORMAL (RANGO DE FECHAS)
// ==========================================
} else {
    $fecha_inicio = isset($_GET['inicio']) ? $_GET['inicio'] : date('Y-m-d');
    $fecha_fin = isset($_GET['fin']) ? $_GET['fin'] : date('Y-m-d');
    $total_periodo = 0;

    try {
        $stmt_total = $conexion->prepare("SELECT SUM(total) as gran_total FROM ventas WHERE DATE(fecha_venta) >= :inicio AND DATE(fecha_venta) <= :fin AND usuario_id = :uid");
        $stmt_total->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin, ':uid' => $mi_usuario_id]);
        $resultado = $stmt_total->fetch(PDO::FETCH_ASSOC);
        if ($resultado['gran_total']) $total_periodo = $resultado['gran_total'];
    } catch (PDOException $e) { }

    $ventas_periodo = [];
    try {
        $stmt_ventas = $conexion->prepare("SELECT v.id as ticket, v.total, v.fecha_venta, GROUP_CONCAT(CONCAT(dv.cantidad, 'x ', p.nombre) SEPARATOR '<br>') as detalle_productos FROM ventas v JOIN detalles_venta dv ON v.id = dv.venta_id JOIN productos p ON dv.producto_id = p.id WHERE DATE(v.fecha_venta) >= :inicio AND DATE(v.fecha_venta) <= :fin AND v.usuario_id = :uid GROUP BY v.id ORDER BY v.fecha_venta DESC");
        $stmt_ventas->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin, ':uid' => $mi_usuario_id]);
        $ventas_periodo = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }

    $dias_diferencia = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / (60 * 60 * 24);
    $labels_grafica = [];
    $datos_grafica = [];
    try {
        if ($dias_diferencia <= 62) { 
            $stmt_graf = $conexion->prepare("SELECT DATE(fecha_venta) as etiqueta, SUM(total) as total_ventas FROM ventas WHERE DATE(fecha_venta) >= :inicio AND DATE(fecha_venta) <= :fin AND usuario_id = :uid GROUP BY DATE(fecha_venta) ORDER BY DATE(fecha_venta) ASC");
            $tipo_agrupacion = 'diaria';
        } else { 
            $stmt_graf = $conexion->prepare("SELECT DATE_FORMAT(fecha_venta, '%Y-%m') as etiqueta, SUM(total) as total_ventas FROM ventas WHERE DATE(fecha_venta) >= :inicio AND DATE(fecha_venta) <= :fin AND usuario_id = :uid GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m') ORDER BY DATE_FORMAT(fecha_venta, '%Y-%m') ASC");
            $tipo_agrupacion = 'mensual';
        }
        $stmt_graf->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin, ':uid' => $mi_usuario_id]);
        while ($fila = $stmt_graf->fetch(PDO::FETCH_ASSOC)) {
            $labels_grafica[] = $fila['etiqueta'];
            $datos_grafica[] = (float) $fila['total_ventas'];
        }
    } catch (PDOException $e) { }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes y Analíticas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; color: #333; }
        .contenedor { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
        .header-reporte { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 20px; }
        .total-destacado { font-size: 32px; color: #28a745; font-weight: bold; margin: 15px 0; }
        .grafica-contenedor { width: 100%; max-width: 800px; margin: 0 auto 30px auto; background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #eee;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top;}
        th { background-color: #f8f9fa; }
        .botones-accion { display: flex; justify-content: space-between; margin-top: 30px; }
        .btn { padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; cursor: pointer; border: none; font-size: 16px; }
        .btn-volver { background-color: #6c757d; color: white; }
        .btn-imprimir { background-color: #007bff; color: white; }
        
        .filtro-fechas { background: #e9ecef; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: center; gap: 15px; align-items: center; flex-wrap: wrap; }
        .filtro-fechas input[type="date"], .filtro-fechas input[type="number"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;}
        .btn-filtrar { background-color: #17a2b8; color: white; padding: 9px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .link-rapido { color: #007bff; text-decoration: none; font-weight: bold; margin-left: 10px; }
        .link-anual { color: #28a745; text-decoration: none; font-weight: bold; margin-left: 10px; }
        .link-comparativa { color: #6f42c1; text-decoration: none; font-weight: bold; margin-left: 10px; background: #fff; padding: 8px 12px; border-radius: 4px; border: 1px solid #6f42c1;}
        .link-comparativa:hover { background: #6f42c1; color: #fff;}

        @media print { 
            body { background-color: white; padding: 0; } 
            .contenedor { box-shadow: none; max-width: 100%; } 
            .no-imprimir { display: none !important; } 
            .grafica-contenedor { border: none; padding: 0; margin-bottom: 20px;}
        }
    </style>
</head>
<body>
    <div class="contenedor">
        
        <div class="filtro-fechas no-imprimir">
            <?php if(!$modo_comparativa): ?>
                <!-- FORMULARIO: REPORTE NORMAL -->
                <form action="reportes.php" method="GET" style="margin: 0; display: flex; gap: 10px; align-items: center;">
                    <label>Desde:</label> <input type="date" name="inicio" value="<?php echo $fecha_inicio; ?>" required>
                    <label>Hasta:</label> <input type="date" name="fin" value="<?php echo $fecha_fin; ?>" required>
                    <button type="submit" class="btn-filtrar">🔍 Buscar</button>
                    <a href="reportes.php?inicio=<?php echo date('Y-m-01'); ?>&fin=<?php echo date('Y-m-t'); ?>" class="link-rapido">Mes Actual</a>
                    <a href="reportes.php?inicio=<?php echo date('Y-01-01'); ?>&fin=<?php echo date('Y-12-31'); ?>" class="link-anual">Año Actual</a>
                    <!-- Cambiamos el botón para que apunte al modo comparativa -->
                    <a href="reportes.php?modo=comparativa" class="link-comparativa">📊 Comparativa Anual</a>
                </form>
            <?php else: ?>
                <!-- FORMULARIO: COMPARATIVA ANUAL (NUEVO) -->
                <form action="reportes.php" method="GET" style="margin: 0; display: flex; gap: 10px; align-items: center;">
                    <input type="hidden" name="modo" value="comparativa">
                    
                    <label>Año Principal:</label> 
                    <input type="number" name="anio1" value="<?php echo $anio1; ?>" min="2000" max="2100" style="width: 80px;" required>
                    
                    <label>Comparar contra:</label> 
                    <input type="number" name="anio2" value="<?php echo $anio2; ?>" min="2000" max="2100" style="width: 80px;" required>
                    
                    <button type="submit" class="btn-filtrar" style="background-color: #6f42c1;">📊 Comparar</button>
                    <a href="reportes.php" class="link-rapido" style="margin-left: 20px;">⬅ Volver al Reporte Normal</a>
                </form>
            <?php endif; ?>
        </div>

        <?php if($modo_comparativa): ?>
            <!-- VISTA: COMPARATIVA ANUAL -->
            <div class="header-reporte">
                <h1>Comparativa de Ingresos Anuales</h1>
                <p>Análisis de crecimiento entre <strong><?php echo $anio1; ?></strong> y <strong><?php echo $anio2; ?></strong></p>
            </div>

            <div class="grafica-contenedor">
                <canvas id="miGrafica"></canvas>
            </div>

            <h3>Desglose Mes a Mes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Año <?php echo $anio2; ?> (Base)</th>
                        <th>Año <?php echo $anio1; ?> (Principal)</th>
                        <th>Diferencia / Crecimiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    for($i = 1; $i <= 12; $i++): 
                        $val_act = $ventas_anio1[$i];
                        $val_ant = $ventas_anio2[$i];
                        $diferencia = $val_act - $val_ant;
                        $color_dif = $diferencia >= 0 ? '#28a745' : '#dc3545';
                        $signo = $diferencia > 0 ? '+' : '';
                    ?>
                    <tr>
                        <td><strong><?php echo $labels_grafica[$i-1]; ?></strong></td>
                        <td>$<?php echo number_format($val_ant, 2); ?></td>
                        <td>$<?php echo number_format($val_act, 2); ?></td>
                        <td style="color: <?php echo $color_dif; ?>; font-weight: bold;">
                            <?php if($val_act == 0 && $val_ant == 0) echo "0.00"; else echo $signo . "$" . number_format($diferencia, 2); ?>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: #e9ecef; font-weight: bold;">
                        <td>TOTALES</td>
                        <td>$<?php echo number_format($total_anio2, 2); ?></td>
                        <td>$<?php echo number_format($total_anio1, 2); ?></td>
                        <?php 
                            $dif_total = $total_anio1 - $total_anio2;
                            $color_tot = $dif_total >= 0 ? '#28a745' : '#dc3545';
                        ?>
                        <td style="color: <?php echo $color_tot; ?>;">
                            <?php echo $dif_total > 0 ? '+' : ''; echo "$" . number_format($dif_total, 2); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>

        <?php else: ?>
            <!-- VISTA: REPORTE NORMAL -->
            <div class="header-reporte">
                <h1>Reporte de Ingresos</h1>
                <p>Periodo: <strong><?php echo date('d/m/Y', strtotime($fecha_inicio)); ?></strong> al <strong><?php echo date('d/m/Y', strtotime($fecha_fin)); ?></strong></p>
                <div class="total-destacado">Total: $<?php echo number_format($total_periodo, 2); ?></div>
            </div>

            <?php if (count($datos_grafica) > 0): ?>
                <div class="grafica-contenedor"><canvas id="miGrafica"></canvas></div>
            <?php endif; ?>

            <h3>Desglose de Tickets</h3>
            <?php if (count($ventas_periodo) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th># Ticket</th>
                            <th>Fecha y Hora</th>
                            <th>Productos (Cant.)</th>
                            <th>Total Cobrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas_periodo as $venta): ?>
                        <tr>
                            <td>TKT-<?php echo str_pad($venta['ticket'], 5, "0", STR_PAD_LEFT); ?></td>
                            <td><?php echo date('d/m/Y H:i A', strtotime($venta['fecha_venta'])); ?></td>
                            <td><?php echo $venta['detalle_productos']; ?></td>
                            <td><strong>$<?php echo number_format($venta['total'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #888; font-style: italic;">No hay ventas registradas en este periodo.</p>
            <?php endif; ?>
        <?php endif; ?>

        <div class="botones-accion no-imprimir">
            <a href="panel.php" class="btn btn-volver">Volver al Panel</a>
            <button onclick="window.print()" class="btn btn-imprimir">🖨️ Imprimir / PDF</button>
        </div>
    </div>

    <script>
        const modoComparativa = <?php echo $modo_comparativa ? 'true' : 'false'; ?>;
        const etiquetas = <?php echo json_encode($labels_grafica); ?>;
        
        if (etiquetas.length > 0) {
            const ctx = document.getElementById('miGrafica').getContext('2d');
            
            const opcionesComunes = {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '$' + value.toLocaleString('es-CO'); }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) { 
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-CO'); 
                            }
                        }
                    }
                }
            };
            
            if (modoComparativa) {
                const anio1 = "<?php echo $anio1 ?? ''; ?>";
                const anio2 = "<?php echo $anio2 ?? ''; ?>";
                const datos_anio1 = <?php echo json_encode($datos_grafica_anio1 ?? []); ?>;
                const datos_anio2 = <?php echo json_encode($datos_grafica_anio2 ?? []); ?>;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: etiquetas,
                        datasets: [
                            {
                                label: 'Año Principal (' + anio1 + ')',
                                data: datos_anio1,
                                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                                borderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 3,
                                tension: 0.3,
                                fill: true,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: 'rgba(40, 167, 69, 1)'
                            },
                            {
                                label: 'Comparación (' + anio2 + ')',
                                data: datos_anio2,
                                backgroundColor: 'rgba(108, 117, 125, 0.1)',
                                borderColor: 'rgba(108, 117, 125, 0.6)',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                tension: 0.3,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: 'rgba(108, 117, 125, 0.6)'
                            }
                        ]
                    },
                    options: opcionesComunes
                });
            } else {
                const datos_ventas = <?php echo json_encode($datos_grafica ?? []); ?>;
                const labelTitulo = "<?php echo ($tipo_agrupacion ?? '') === 'diaria' ? 'Ingresos Diarios' : 'Ingresos Mensuales'; ?>";

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: etiquetas,
                        datasets: [{
                            label: labelTitulo,
                            data: datos_ventas,
                            backgroundColor: 'rgba(23, 162, 184, 0.2)',
                            borderColor: 'rgba(23, 162, 184, 1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: 'rgba(23, 162, 184, 1)',
                            pointBorderWidth: 2,
                            pointRadius: 4
                        }]
                    },
                    options: opcionesComunes
                });
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