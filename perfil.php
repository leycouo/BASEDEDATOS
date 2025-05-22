<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Obtener información del usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING);
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING);
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    try {
        $conn->beginTransaction();

        // Actualizar información básica
        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ?, direccion = ?";
        $params = [$nombre, $apellido, $telefono, $direccion];

        // Si se proporcionó una nueva contraseña
        if (!empty($password_actual)) {
            if (password_verify($password_actual, $usuario['password'])) {
                if ($password_nueva === $password_confirmar) {
                    if (strlen($password_nueva) >= 6) {
                        $sql .= ", password = ?";
                        $params[] = password_hash($password_nueva, PASSWORD_DEFAULT);
                    } else {
                        throw new Exception('La nueva contraseña debe tener al menos 6 caracteres.');
                    }
                } else {
                    throw new Exception('Las contraseñas nuevas no coinciden.');
                }
            } else {
                throw new Exception('La contraseña actual es incorrecta.');
            }
        }

        $sql .= " WHERE id_usuario = ?";
        $params[] = $_SESSION['usuario_id'];

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $conn->commit();
        $success = 'Perfil actualizado exitosamente.';
        
        // Actualizar información de sesión
        $_SESSION['usuario_nombre'] = $nombre;
        
        // Recargar información del usuario
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// Obtener productos del usuario
$stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre 
                       FROM productos p 
                       JOIN categorias c ON p.id_categoria = c.id_categoria 
                       WHERE p.id_vendedor = ? 
                       ORDER BY p.fecha_publicacion DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener transacciones del usuario
$stmt = $conn->prepare("SELECT t.*, p.titulo as producto_titulo, 
                              CASE 
                                  WHEN t.id_comprador = ? THEN 'compra'
                                  ELSE 'venta'
                              END as tipo_transaccion
                       FROM transacciones t 
                       JOIN productos p ON t.id_producto = p.id_producto 
                       WHERE t.id_comprador = ? OR t.id_vendedor = ?
                       ORDER BY t.fecha_transaccion DESC");
$stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id'], $_SESSION['usuario_id']]);
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - CompuTec Marketplace</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="nav">
                <div class="logo">
                    <h1>CompuTec Marketplace</h1>
                </div>
                <div class="nav-links">
                    <a href="index.php">Inicio</a>
                    <a href="productos.php">Productos</a>
                    <a href="perfil.php">Mi Perfil</a>
                    <a href="logout.php">Cerrar Sesión</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="perfil-container">
            <div class="perfil-info card">
                <h2>Información Personal</h2>
                <form method="POST" action="perfil.php">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico:</label>
                        <input type="email" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="tel" id="telefono" name="telefono" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="direccion">Dirección:</label>
                        <textarea id="direccion" name="direccion" class="form-control"><?php echo htmlspecialchars($usuario['direccion']); ?></textarea>
                    </div>

                    <h3>Cambiar Contraseña</h3>
                    <div class="form-group">
                        <label for="password_actual">Contraseña Actual:</label>
                        <input type="password" id="password_actual" name="password_actual" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="password_nueva">Nueva Contraseña:</label>
                        <input type="password" id="password_nueva" name="password_nueva" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="password_confirmar">Confirmar Nueva Contraseña:</label>
                        <input type="password" id="password_confirmar" name="password_confirmar" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                </form>
            </div>

            <?php if ($usuario['tipo_usuario'] == 'vendedor'): ?>
                <div class="productos-usuario card">
                    <h2>Mis Productos</h2>
                    <a href="agregar_producto.php" class="btn btn-primary">Agregar Nuevo Producto</a>
                    
                    <?php if (!empty($productos)): ?>
                        <div class="product-grid">
                            <?php foreach($productos as $producto): ?>
                                <div class="product-card">
                                    <?php
                                    $stmt = $conn->prepare("SELECT url_imagen FROM imagenes_producto WHERE id_producto = ? AND es_principal = 1 LIMIT 1");
                                    $stmt->execute([$producto['id_producto']]);
                                    $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <img src="<?php echo $imagen ? $imagen['url_imagen'] : 'assets/img/default.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($producto['titulo']); ?>" 
                                         class="product-image">
                                    <div class="product-info">
                                        <h3 class="product-title"><?php echo htmlspecialchars($producto['titulo']); ?></h3>
                                        <p class="product-price">$<?php echo number_format($producto['precio'], 2); ?></p>
                                        <p>Estado: <?php echo ucfirst($producto['estado']); ?></p>
                                        <div class="product-actions">
                                            <a href="editar_producto.php?id=<?php echo $producto['id_producto']; ?>" 
                                               class="btn btn-primary">Editar</a>
                                            <a href="eliminar_producto.php?id=<?php echo $producto['id_producto']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar este producto?')">Eliminar</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No tienes productos publicados.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="transacciones card">
                <h2>Mis Transacciones</h2>
                <?php if (!empty($transacciones)): ?>
                    <div class="transacciones-lista">
                        <?php foreach($transacciones as $transaccion): ?>
                            <div class="transaccion-item">
                                <div class="transaccion-info">
                                    <h4><?php echo htmlspecialchars($transaccion['producto_titulo']); ?></h4>
                                    <p>Tipo: <?php echo ucfirst($transaccion['tipo_transaccion']); ?></p>
                                    <p>Monto: $<?php echo number_format($transaccion['monto'], 2); ?></p>
                                    <p>Estado: <?php echo ucfirst($transaccion['estado']); ?></p>
                                    <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($transaccion['fecha_transaccion'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No tienes transacciones.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CompuTec Marketplace. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 