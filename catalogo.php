<?php
// C:\xampp\htdocs\petspa\catalogo.php
session_start();
require 'db.php';

$mensaje = "";
$es_cliente = (isset($_SESSION['rol']) && $_SESSION['rol'] == 4);

if ($es_cliente && !isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$cliente_nombre = "Desconocido";
if ($es_cliente) {
    $stmtC = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id_usuario = ?");
    $stmtC->execute([$_SESSION['usuario_id']]);
    $cliente_nombre = $stmtC->fetchColumn();
}

if ($es_cliente) {
    // AGREGAR AL CARRITO
    if (isset($_POST['agregar_carrito'])) {
        $id_prod = $_POST['id_producto'];
        if (isset($_SESSION['carrito'][$id_prod])) {
            $_SESSION['carrito'][$id_prod]['cantidad'] += 1;
        } else {
            $_SESSION['carrito'][$id_prod] = [
                'nombre' => $_POST['nombre_producto'],
                'precio' => $_POST['precio'],
                'cantidad' => 1
            ];
        }
        header("Location: catalogo.php?carrito_abierto=1");
        exit();
    }

    if (isset($_GET['vaciar'])) {
        $_SESSION['carrito'] = [];
        header("Location: catalogo.php");
        exit();
    }

    // PROCESAR COMPRA Y CÓDIGO DE RECOJO
    if (isset($_POST['procesar_compra'])) {
        if (!empty($_SESSION['carrito'])) {
            try {
                $pdo->beginTransaction();
                
                $total = 0;
                $metodo_pago = $_POST['metodo_pago'];
                // Generar código único de recojo (Ej. PED-4F2A9)
                $codigo_recojo = 'PED-' . strtoupper(substr(md5(uniqid()), 0, 5));

                $detalle_telegram = "🛒 *NUEVO PEDIDO WEB*\n\n";
                $detalle_telegram .= "👤 Cliente: " . $cliente_nombre . "\n";
                $detalle_telegram .= "📦 Código Recojo: *" . $codigo_recojo . "*\n";
                $detalle_telegram .= "💳 Método: " . $metodo_pago . "\n";
                $detalle_telegram .= "------------------------\n";

                foreach ($_SESSION['carrito'] as $item) {
                    $sub = $item['precio'] * $item['cantidad'];
                    $total += $sub;
                    $detalle_telegram .= "- {$item['cantidad']}x {$item['nombre']} (Bs. {$sub})\n";
                }
                $detalle_telegram .= "------------------------\n";
                $detalle_telegram .= "💰 *TOTAL: Bs. {$total}*\n";

                // Insertar Venta
                $id_venta = $pdo->query("SELECT UUID()")->fetchColumn();
                $stmtVenta = $pdo->prepare("INSERT INTO ventas (id_venta, cliente_id, total, metodo_pago, codigo_recojo, estado_pedido) VALUES (?, ?, ?, ?, ?, 'Pendiente')");
                $stmtVenta->execute([$id_venta, $_SESSION['usuario_id'], $total, $metodo_pago, $codigo_recojo]);

                foreach ($_SESSION['carrito'] as $id_prod => $item) {
                    $subtotal = $item['precio'] * $item['cantidad'];
                    $id_detalle = $pdo->query("SELECT UUID()")->fetchColumn();
                    $pdo->prepare("INSERT INTO detalle_ventas_productos (id_detalle, venta_id, producto_id, cantidad, subtotal) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$id_detalle, $id_venta, $id_prod, $item['cantidad'], $subtotal]);
                    
                    $pdo->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id_producto = ?")
                        ->execute([$item['cantidad'], $id_prod]);
                }

                $env = parse_ini_file(__DIR__ . '/.env');
                $telegram_token = $env['TELEGRAM_TOKEN']; 
                $chat_id = $env['TELEGRAM_CHAT_ID']; //Busca @userinfobot en telegram, dale /start, y reemplaza este id con el tuyo para que las confirmaciones te lleguen a ti
                $url_telegram = "https://api.telegram.org/bot{$telegram_token}/sendMessage";
                $data = ['chat_id' => $chat_id, 'text' => $detalle_telegram, 'parse_mode' => 'Markdown'];
                $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)]];
                @file_get_contents($url_telegram, false, stream_context_create($options));

                $pdo->commit();
                $_SESSION['carrito'] = [];

                // Generar la pantalla de éxito según el método de pago
                if ($metodo_pago === 'QR') {
                    $mensaje = "
                    <div class='alert success' style='text-align:center;'>
                        <h2>✅ ¡Pedido Reservado!</h2>
                        <p>Tu código de recojo es: <b style='font-size:24px; color:#2c3e50;'>$codigo_recojo</b></p>
                        <p>Escanea este QR para realizar el pago de <b>Bs. $total</b>.</p>
                        <img src='uploads/qr_pago.png' style='width: 200px; border-radius: 10px; margin: 10px 0; border: 2px solid #27ae60;' alt='Código QR'>
                        <p style='font-size:13px; color:#636e72;'>Por favor envía el comprobante por WhatsApp o muéstralo al recoger tu pedido en el Spa.</p>
                        <a href='dashboard.php' class='btn-add' style='background:#0984e3; display:inline-block; width:auto; padding:10px 20px;'>Ir a Mis Pedidos</a>
                    </div>";
                } else {
                    $mensaje = "
                    <div class='alert success' style='text-align:center;'>
                        <h2>✅ ¡Pedido Reservado!</h2>
                        <p>Tu código de recojo es: <b style='font-size:24px; color:#2c3e50;'>$codigo_recojo</b></p>
                        <p>Total a pagar en caja: <b>Bs. $total</b></p>
                        <p style='font-size:13px; color:#636e72;'>Te esperamos en el local para entregar tus productos.</p>
                        <a href='dashboard.php' class='btn-add' style='background:#0984e3; display:inline-block; width:auto; padding:10px 20px;'>Ir a Mis Pedidos</a>
                    </div>";
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $mensaje = "<div class='alert error'>❌ Error en la compra: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// CONSULTA OPTIMIZADA (LEFT JOIN) PARA TRAER LAS ESPECIFICACIONES A LA VISTA PRINCIPAL
$sql = "
    SELECT p.*, 
           da.peso_kg, 
           dr.talla, 
           di.contenido_por_envase 
    FROM productos p
    LEFT JOIN detalles_alimento da ON p.id_producto = da.id_producto
    LEFT JOIN detalles_ropa dr ON p.id_producto = dr.id_producto
    LEFT JOIN detalles_insumo di ON p.id_producto = di.id_producto
    WHERE p.es_insumo_grooming IN (0, 2) AND p.stock_actual > 0
";
$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #2c3e50; color: white; padding: 15px 30px; border-radius: 8px; margin-bottom: 20px; }
        .grid-productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .card-prod { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .btn-add { background: #0984e3; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 10px; text-decoration: none;}
        .cart-panel { position: fixed; right: -350px; top: 0; width: 300px; height: 100vh; background: white; box-shadow: -2px 0 10px rgba(0,0,0,0.2); transition: right 0.3s ease; padding: 20px; overflow-y: auto; z-index: 1000; }
        .cart-panel.open { right: 0; }
        .cart-item { border-bottom: 1px solid #eee; padding: 10px 0; display: flex; justify-content: space-between; font-size: 14px;}
        .btn-cart { background: #27ae60; color: white; border: none; padding: 10px; width: 100%; font-weight: bold; cursor: pointer; margin-top: 10px; border-radius: 4px;}
        .alert { padding: 20px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .success { background: #e8f8f5; border: 2px solid #1abc9c; color: #2c3e50; }
    </style>
</head>
<body>
    <div class="header">
        <h2>🛍️ Catálogo Pet Spa</h2>
        <div>
            <?php if ($es_cliente): ?>
                <button onclick="toggleCart()" style="padding: 10px; font-weight: bold; border-radius: 4px; cursor: pointer; border: none;">🛒 Carrito (<?php echo count($_SESSION['carrito']); ?>)</button>
                <a href="dashboard.php" style="color: white; margin-left: 15px; text-decoration: none;">🏠 Mi Panel</a>
            <?php else: ?>
                <a href="portal_clientes.php" style="background: #e17055; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;">🔑 Iniciar Sesión para Comprar</a>
            <?php endif; ?>
        </div>
    </div>

    <?php echo $mensaje; ?>

    <div class="grid-productos">
        <?php foreach ($productos as $p): ?>
            <?php 
                // CONSTRUIMOS EL NOMBRE DINÁMICO DEL PRODUCTO
                $nombre_display = htmlspecialchars($p['nombre']);
                if ($p['tipo_producto'] === 'Ropa' && !empty($p['talla'])) {
                    $nombre_display .= " (Talla " . htmlspecialchars($p['talla']) . ")";
                } elseif ($p['tipo_producto'] === 'Insumo_Spa' && !empty($p['contenido_por_envase'])) {
                    $nombre_display .= " (" . htmlspecialchars($p['contenido_por_envase']) . " ml)";
                } elseif ($p['tipo_producto'] === 'Alimento' && !empty($p['peso_kg'])) {
                    $nombre_display .= " (" . htmlspecialchars($p['peso_kg']) . " Kg)";
                }
            ?>
            <div class="card-prod">
                <span style="background: #00b894; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;"><?php echo htmlspecialchars($p['tipo_producto']); ?></span>
                <img src="uploads/<?php echo htmlspecialchars($p['foto_url'] ?? 'default_product.png'); ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 8px; margin-top: 10px;">
                
                <h3 style="margin: 10px 0; color: #2c3e50; font-size: 18px;"><?php echo $nombre_display; ?></h3>
                <h2 style="color: #2d3436; margin: 10px 0;">Bs. <?php echo $p['precio_venta']; ?></h2>
                
                <a href="ver_producto.php?id=<?php echo $p['id_producto']; ?>" style="display:block; background:#f1c40f; color:#2d3436; text-decoration:none; padding:8px; border-radius:4px; font-weight:bold; margin-bottom: 8px;">🔍 Ver Detalles</a>
                
                <?php if ($es_cliente): ?>
                    <form method="POST">
                        <input type="hidden" name="id_producto" value="<?php echo $p['id_producto']; ?>">
                        <input type="hidden" name="nombre_producto" value="<?php echo $nombre_display; ?>"> <input type="hidden" name="precio" value="<?php echo $p['precio_venta']; ?>">
                        <button type="submit" name="agregar_carrito" class="btn-add">➕ Agregar al Carrito</button>
                    </form>
                <?php else: ?>
                    <a href="portal_clientes.php" class="btn-add" style="display:block; background:#e17055; text-align:center;">Inicia sesión</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($es_cliente): ?>
    <div class="cart-panel" id="cartPanel">
        <button onclick="toggleCart()" style="background: none; border: none; font-size: 20px; cursor: pointer; float:right;">✖</button>
        <h3>Resumen de Compra</h3>
        
        <?php if (empty($_SESSION['carrito'])): ?>
            <p>Tu carrito está vacío.</p>
        <?php else: ?>
            <?php $total_carrito = 0; foreach ($_SESSION['carrito'] as $id => $item): ?>
                <?php $sub = $item['precio'] * $item['cantidad']; $total_carrito += $sub; ?>
                <div class="cart-item">
                    <div><b><?php echo htmlspecialchars($item['nombre']); ?></b><br><small><?php echo $item['cantidad']; ?> x Bs. <?php echo $item['precio']; ?></small></div>
                    <div><b>Bs. <?php echo $sub; ?></b></div>
                </div>
            <?php endforeach; ?>
            
            <h3 style="text-align: right; margin-top: 20px;">Total: Bs. <?php echo $total_carrito; ?></h3>
            
            <form method="POST">
                <label style="font-weight:bold; font-size:14px; margin-bottom:5px; display:block;">Método de Pago:</label>
                <select name="metodo_pago" required style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px;">
                    <option value="QR">📲 Pagar ahora (Código QR)</option>
                    <option value="Efectivo">💵 Pagar en Caja al recoger</option>
                </select>
                <button type="submit" name="procesar_compra" class="btn-cart">🛍️ Confirmar Pedido</button>
            </form>
            <a href="catalogo.php?vaciar=1" style="display: block; text-align: center; margin-top: 10px; color: #e74c3c; text-decoration: none; font-size: 14px;">Vaciar carrito</a>
        <?php endif; ?>
    </div>
    <script>
        function toggleCart() { document.getElementById('cartPanel').classList.toggle('open'); }
        <?php if (isset($_GET['carrito_abierto'])) echo "toggleCart();"; ?>
    </script>
    <?php endif; ?>
</body>
</html>