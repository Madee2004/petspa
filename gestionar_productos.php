<?php
// C:\xampp\htdocs\petspa\gestionar_productos.php
require 'auth_check.php';
require 'db.php';

// Seguridad: Solo Admin
if ($_SESSION['rol'] != 1) { header("Location: index.php"); exit(); }

$mensaje = "";

// 1. LÓGICA PARA AGREGAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_producto'])) {
    $id_producto = $pdo->query("SELECT UUID()")->fetchColumn();
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo_producto'];
    $stock = $_POST['stock_actual'];
    $precio = $_POST['precio_venta'];
    $es_insumo = $_POST['es_insumo_grooming'];
    $foto_url = 'default_product.png';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nuevo_nombre = "prod_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $nuevo_nombre)) {
                $foto_url = $nuevo_nombre;
            }
        }
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO productos (id_producto, nombre, tipo_producto, stock_actual, precio_venta, es_insumo_grooming, foto_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_producto, $nombre, $tipo, $stock, $precio, $es_insumo, $foto_url]);

        if ($tipo === 'Alimento') {
            $pdo->prepare("INSERT INTO detalles_alimento (id_producto, peso_kg, fecha_vencimiento, sabor) VALUES (?, ?, ?, ?)")
                ->execute([$id_producto, $_POST['peso_kg'] ?: null, $_POST['fecha_vencimiento'] ?: null, $_POST['sabor']]);
        } 
        elseif ($tipo === 'Juguete') {
            $pdo->prepare("INSERT INTO detalles_juguete (id_producto, material, durabilidad_estimada, edad_recomendada) VALUES (?, ?, ?, ?)")
                ->execute([$id_producto, $_POST['material'], $_POST['durabilidad_estimada'], $_POST['edad_recomendada']]);
        } 
        elseif ($tipo === 'Ropa') {
            $pdo->prepare("INSERT INTO detalles_ropa (id_producto, talla, color, tipo_tela, temporada) VALUES (?, ?, ?, ?, ?)")
                ->execute([$id_producto, $_POST['talla'], $_POST['color'], $_POST['tipo_tela'], $_POST['temporada']]);
        }
        elseif ($tipo === 'Insumo_Spa') {
            $ml_por_envase = $_POST['ml_por_envase'] ?: 1000; 
            $ml_totales_iniciales = $stock * $ml_por_envase;
            $pdo->prepare("INSERT INTO detalles_insumo (id_producto, unidad_medida, contenido_por_envase, ml_totales) VALUES (?, 'ml', ?, ?)")
                ->execute([$id_producto, $ml_por_envase, $ml_totales_iniciales]);
        }

        $pdo->commit();
        $mensaje = "<div class='alert success' style='background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px;'>✅ Producto registrado correctamente.</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "<div class='alert error' style='background:#fee2e2; color:#dc2626; padding:10px; border-radius:5px; margin-bottom:15px;'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// 2. LÓGICA PARA ELIMINAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_producto'])) {
    $id_eliminar = $_POST['id_eliminar'];
    
    try {
        $pdo->beginTransaction();
        
        // Primero borramos de las tablas hijas para evitar conflictos de relación
        $pdo->prepare("DELETE FROM detalles_alimento WHERE id_producto = ?")->execute([$id_eliminar]);
        $pdo->prepare("DELETE FROM detalles_juguete WHERE id_producto = ?")->execute([$id_eliminar]);
        $pdo->prepare("DELETE FROM detalles_ropa WHERE id_producto = ?")->execute([$id_eliminar]);
        $pdo->prepare("DELETE FROM detalles_insumo WHERE id_producto = ?")->execute([$id_eliminar]);
        
        // Finalmente borramos el producto principal
        $pdo->prepare("DELETE FROM productos WHERE id_producto = ?")->execute([$id_eliminar]);
        
        $pdo->commit();
        $mensaje = "<div class='alert success' style='background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px;'>🗑️ Producto eliminado permanentemente del catálogo.</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        // Si el error es de restricción de llave foránea (ya tiene ventas o citas)
        if ($e->getCode() == 23000 || strpos($e->getMessage(), '1451') !== false) {
            $mensaje = "<div class='alert error' style='background:#fff3cd; color:#856404; padding:15px; border-radius:5px; margin-bottom:15px; border-left: 5px solid #ffc107;'>⚠️ <b>No se puede eliminar:</b> Este producto ya se encuentra registrado en el historial de ventas o en el consumo de citas pasadas. Eliminarlo corrompería la contabilidad del Spa.</div>";
        } else {
            $mensaje = "<div class='alert error' style='background:#fee2e2; color:#dc2626; padding:10px; border-radius:5px; margin-bottom:15px;'>❌ Error al eliminar: " . $e->getMessage() . "</div>";
        }
    }
}

// Obtener todos los productos para la tabla
$productos = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Productos - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 10px; margin: 5px 0 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;}
        .btn { background: #27ae60; color: white; padding: 12px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 15px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: middle; }
        th { background: #2c3e50; color: white; }
        .detalles-dinamicos { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #3498db; display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📦 Gestión de Inventario y Catálogo</h2>
        <a href="admin_dashboard.php" style="color: #0984e3; text-decoration: none; font-weight: bold;">⬅️ Volver al Panel Admin</a>
        <hr>
        <?php echo $mensaje; ?>
        
        <form method="POST" enctype="multipart/form-data" style="background: #f1f2f6; padding: 20px; border-radius: 5px;">
            <h3 style="margin-top: 0;">➕ Nuevo Producto</h3>
            <div style="display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label>Nombre del Producto:</label>
                    <input type="text" name="nombre" required>
                </div>
                <div style="flex: 1;">
                    <label>Categoría:</label>
                    <select name="tipo_producto" id="tipo_producto" onchange="mostrarDetalles()" required>
                        <option value="">-- Selecciona Categoría --</option>
                        <option value="Alimento">Alimento / Snacks</option>
                        <option value="Juguete">Juguetes</option>
                        <option value="Ropa">Ropa / Accesorios</option>
                        <option value="Insumo_Spa">Insumo de Spa (Shampoos, Tratamientos)</option>
                    </select>
                </div>
            </div>

            <div id="div_alimento" class="detalles-dinamicos">
                <h4 style="margin-top:0;">📝 Detalles Específicos (Alimento)</h4>
                <div style="display: flex; gap: 10px;">
                    <input type="number" step="0.01" name="peso_kg" placeholder="Peso (Kg)">
                    <input type="date" name="fecha_vencimiento" title="Fecha Vencimiento">
                    <input type="text" name="sabor" placeholder="Sabor (Ej. Pollo y Res)">
                </div>
            </div>

            <div id="div_juguete" class="detalles-dinamicos">
                <h4 style="margin-top:0;">🧸 Detalles Específicos (Juguete)</h4>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="material" placeholder="Material (Ej. Goma Dura)">
                    <input type="text" name="durabilidad_estimada" placeholder="Durabilidad (Ej. Alta)">
                    <input type="text" name="edad_recomendada" placeholder="Edad (Ej. Cachorros)">
                </div>
            </div>

            <div id="div_ropa" class="detalles-dinamicos">
                <h4 style="margin-top:0;">👕 Detalles Específicos (Ropa)</h4>
                <div style="display: flex; gap: 10px;">
                    <select name="talla">
                        <option value="">- Selecciona Talla -</option>
                        <option value="XS">Extra Pequeña (XS)</option>
                        <option value="S">Pequeña (S)</option>
                        <option value="M">Mediana (M)</option>
                        <option value="L">Grande (L)</option>
                        <option value="XL">Extra Grande (XL)</option>
                    </select>
                    <input type="text" name="color" placeholder="Color Principal">
                    <input type="text" name="tipo_tela" placeholder="Tela (Ej. Algodón polar)">
                    <input type="text" name="temporada" placeholder="Temporada (Ej. Otoño, Invierno)">
                </div>
            </div>

            <div id="div_insumo" class="detalles-dinamicos">
                <h4 style="margin-top:0;">🧴 Detalles Específicos (Insumo para Groomer)</h4>
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <input type="number" name="ml_por_envase" placeholder="¿Cuántos ml tiene una botella? (Ej. 1000)">
                        <p style="font-size: 12px; color: #636e72; margin-top: -10px;">*El sistema multiplicará este valor por tu Stock Inicial.</p>
                    </div>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">

            <div style="display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label>Stock Inicial (Cant. envases/piezas):</label>
                    <input type="number" name="stock_actual" min="0" required>
                </div>
                <div style="flex: 1;">
                    <label>Precio de Venta (Bs.):</label>
                    <input type="number" step="0.01" name="precio_venta" required>
                </div>
                <div style="flex: 1;">
                    <label>Disponibilidad:</label>
                    <select name="es_insumo_grooming" required>
                        <option value="0">🛒 Solo Catálogo (Venta al Público)</option>
                        <option value="1">✂️ Solo Groomer (Uso Interno)</option>
                        <option value="2">🌟 Ambos (Venta y Uso Interno)</option>
                    </select>
                </div>
            </div>

            <div style="width: 100%;">
                <label>Foto del producto:</label>
                <input type="file" name="foto" accept="image/*" required style="background: white;">
            </div>
            <button type="submit" name="agregar_producto" class="btn">💾 Guardar Producto</button>
        </form>

        <script>
            function mostrarDetalles() {
                document.getElementById('div_alimento').style.display = 'none';
                document.getElementById('div_juguete').style.display = 'none';
                document.getElementById('div_ropa').style.display = 'none';
                document.getElementById('div_insumo').style.display = 'none';
                
                let tipo = document.getElementById('tipo_producto').value;
                if(tipo === 'Alimento') document.getElementById('div_alimento').style.display = 'block';
                if(tipo === 'Juguete') document.getElementById('div_juguete').style.display = 'block';
                if(tipo === 'Ropa') document.getElementById('div_ropa').style.display = 'block';
                if(tipo === 'Insumo_Spa') document.getElementById('div_insumo').style.display = 'block';
            }
        </script>

        <h3 style="margin-top: 40px;">📋 Catálogo de Productos Actual</h3>
        <table>
            <tr>
                <th>Imagen</th> 
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Stock (Uds)</th>
                <th>Precio</th>
                <th>Disponibilidad</th>
                <th style="text-align: center;">Acciones</th>
            </tr>
            <?php foreach($productos as $p): ?>
            <tr>
                <td>
                    <img src="uploads/<?php echo htmlspecialchars($p['foto_url'] ?? 'default_product.png'); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; border: 1px solid #ccc;">
                </td>
                <td><b><?php echo htmlspecialchars($p['nombre']); ?></b></td>
                <td><span style="background: #ecf0f1; padding: 3px 8px; border-radius: 10px; font-size: 12px;"><?php echo $p['tipo_producto']; ?></span></td>
                <td style="font-size: 16px;"><b><?php echo $p['stock_actual']; ?></b></td>
                <td>Bs. <?php echo $p['precio_venta']; ?></td>
                <td>
                    <?php if($p['es_insumo_grooming'] == 1): ?>
                        <span style="color:#e74c3c; font-weight:bold;">✂️ Uso Interno</span>
                    <?php elseif($p['es_insumo_grooming'] == 2): ?>
                        <span style="color:#f39c12; font-weight:bold;">🌟 Tienda e Interno</span>
                    <?php else: ?>
                        <span style="color:#27ae60; font-weight:bold;">🛒 Tienda Pública</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <form method="POST" style="margin: 0;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar permanentemente este producto del sistema?');">
                        <input type="hidden" name="id_eliminar" value="<?php echo $p['id_producto']; ?>">
                        <button type="submit" name="eliminar_producto" style="background:#e74c3c; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:bold; font-size:12px;">🗑️ Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>