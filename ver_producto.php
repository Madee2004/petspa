<?php
// C:\xampp\htdocs\petspa\ver_producto.php
session_start();
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: catalogo.php");
    exit();
}

$id_producto = $_GET['id'];
$es_cliente = (isset($_SESSION['rol']) && $_SESSION['rol'] == 4);

// Obtener datos generales del producto
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ?");
$stmt->execute([$id_producto]);
$producto = $stmt->fetch();

if (!$producto) {
    header("Location: catalogo.php");
    exit();
}

// Obtener especificaciones según la categoría
$especificaciones = [];
if ($producto['tipo_producto'] === 'Alimento') {
    $stmtD = $pdo->prepare("SELECT * FROM detalles_alimento WHERE id_producto = ?");
    $stmtD->execute([$id_producto]);
    $especificaciones = $stmtD->fetch();
} elseif ($producto['tipo_producto'] === 'Juguete') {
    $stmtD = $pdo->prepare("SELECT * FROM detalles_juguete WHERE id_producto = ?");
    $stmtD->execute([$id_producto]);
    $especificaciones = $stmtD->fetch();
} elseif ($producto['tipo_producto'] === 'Ropa') {
    $stmtD = $pdo->prepare("SELECT * FROM detalles_ropa WHERE id_producto = ?");
    $stmtD->execute([$id_producto]);
    $especificaciones = $stmtD->fetch();
} elseif ($producto['tipo_producto'] === 'Insumo_Spa') {
    $stmtD = $pdo->prepare("SELECT * FROM detalles_insumo WHERE id_producto = ?");
    $stmtD->execute([$id_producto]);
    $especificaciones = $stmtD->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #2c3e50; color: white; padding: 15px 30px; border-radius: 8px; margin-bottom: 20px; }
        .container { max-width: 900px; margin: auto; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: flex; overflow: hidden; }
        .img-section { flex: 1; padding: 30px; background: #fff; text-align: center; border-right: 1px solid #eee; }
        .img-section img { max-width: 100%; border-radius: 8px; max-height: 400px; object-fit: cover; }
        .info-section { flex: 1; padding: 40px; }
        .badge { background: #00b894; color: white; padding: 5px 12px; border-radius: 15px; font-size: 13px; font-weight: bold; }
        .price { font-size: 32px; color: #2d3436; margin: 15px 0; font-weight: bold; }
        .specs-table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 30px; }
        .specs-table th, .specs-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .specs-table th { background: #f1f2f6; width: 40%; color: #636e72; }
        .btn-add { background: #0984e3; color: white; border: none; padding: 15px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: bold; font-size: 16px; text-decoration: none; display: block; text-align: center; }
        .btn-add:hover { background: #074b83; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Detalles del Producto</h2>
        <a href="catalogo.php" style="color: white; text-decoration: none; font-weight: bold;">⬅️ Volver a la Tienda</a>
    </div>

    <div class="container">
        <div class="img-section">
            <img src="uploads/<?php echo htmlspecialchars($producto['foto_url'] ?? 'default_product.png'); ?>" alt="Foto Producto">
        </div>
        
        <div class="info-section">
            <span class="badge"><?php echo htmlspecialchars($producto['tipo_producto']); ?></span>
            <h1 style="margin: 15px 0; color: #2c3e50;"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
            <div class="price">Bs. <?php echo $producto['precio_venta']; ?></div>
            <p style="color: #636e72;">Stock disponible: <b><?php echo $producto['stock_actual']; ?> unidades</b></p>

            <table class="specs-table">
                <?php if ($producto['tipo_producto'] === 'Alimento' && $especificaciones): ?>
                    <tr><th>Peso:</th><td><?php echo $especificaciones['peso_kg'] ? $especificaciones['peso_kg'] . ' Kg' : 'N/A'; ?></td></tr>
                    <tr><th>Sabor:</th><td><?php echo htmlspecialchars($especificaciones['sabor']); ?></td></tr>
                    <tr><th>Vencimiento:</th><td><?php echo $especificaciones['fecha_vencimiento'] ? date('d/m/Y', strtotime($especificaciones['fecha_vencimiento'])) : 'N/A'; ?></td></tr>
                
                <?php elseif ($producto['tipo_producto'] === 'Juguete' && $especificaciones): ?>
                    <tr><th>Material:</th><td><?php echo htmlspecialchars($especificaciones['material']); ?></td></tr>
                    <tr><th>Durabilidad:</th><td><?php echo htmlspecialchars($especificaciones['durabilidad_estimada']); ?></td></tr>
                    <tr><th>Edad Recomendada:</th><td><?php echo htmlspecialchars($especificaciones['edad_recomendada']); ?></td></tr>
                
                <?php elseif ($producto['tipo_producto'] === 'Ropa' && $especificaciones): ?>
                    <tr><th>Talla:</th><td><b style="font-size: 16px;"><?php echo htmlspecialchars($especificaciones['talla']); ?></b></td></tr>
                    <tr><th>Color:</th><td><?php echo htmlspecialchars($especificaciones['color']); ?></td></tr>
                    <tr><th>Material/Tela:</th><td><?php echo htmlspecialchars($especificaciones['tipo_tela']); ?></td></tr>
                    <tr><th>Temporada:</th><td><?php echo htmlspecialchars($especificaciones['temporada']); ?></td></tr>
                
                <?php elseif ($producto['tipo_producto'] === 'Insumo_Spa' && $especificaciones): ?>
                    <tr><th>Contenido por envase:</th><td><?php echo htmlspecialchars($especificaciones['contenido_por_envase']); ?> ml</td></tr>
                    <tr><th>Uso Recomendado:</th><td>Aplicación profesional</td></tr>
                <?php endif; ?>
            </table>

            <?php if ($es_cliente): ?>
                <form action="catalogo.php" method="POST">
                    <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                    <input type="hidden" name="nombre_producto" value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    <input type="hidden" name="precio" value="<?php echo $producto['precio_venta']; ?>">
                    <button type="submit" name="agregar_carrito" class="btn-add">➕ Agregar al Carrito</button>
                </form>
            <?php else: ?>
                <a href="portal_clientes.php" class="btn-add" style="background: #e17055;">🔑 Inicia sesión para comprar</a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>