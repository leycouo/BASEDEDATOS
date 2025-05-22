<?php
session_start();
require_once 'config/database.php';

// Verificar si se proporcionó un ID de producto
if (!isset($_GET['id'])) {
    header('Location: productos.php');
    exit;
}

$id_producto = (int)$_GET['id'];

// Obtener información del producto
$stmt = $conn->prepare("SELECT p.*, u.nombre as vendedor_nombre, u.email as vendedor_email, 
                              c.nombre as categoria_nombre 
                       FROM productos p 
                       JOIN usuarios u ON p.id_vendedor = u.id_usuario 
                       JOIN categorias c ON p.id_categoria = c.id_categoria 
                       WHERE p.id_producto = ?");
$stmt->execute([$id_producto]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header('Location: productos.php');
    exit;
}

// Obtener imágenes del producto
$stmt = $conn->prepare("SELECT * FROM imagenes_producto WHERE id_producto = ? ORDER BY es_principal DESC");
$stmt->execute([$id_producto]);
$imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener características del producto
$stmt = $conn->prepare("SELECT * FROM caracteristicas_producto WHERE id_producto = ?");
$stmt->execute([$id_producto]);
$caracteristicas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener comentarios del producto
$stmt = $conn->prepare("SELECT c.*, u.nombre as usuario_nombre 
                       FROM comentarios c 
                       JOIN usuarios u ON c.id_usuario = u.id_usuario 
                       WHERE c.id_producto = ? 
                       ORDER BY c.fecha_comentario DESC");
$stmt->execute([$id_producto]);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar nuevo comentario
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    if (!isset($_SESSION['usuario_id'])) {
        $error = 'Debes iniciar sesión para comentar';
    } else {
        $comentario = trim($_POST['comentario']);
        if (empty($comentario)) {
            $error = 'El comentario no puede estar vacío';
        } else {
            $stmt = $conn->prepare("INSERT INTO comentarios (id_producto, id_usuario, comentario) VALUES (?, ?, ?)");
            if ($stmt->execute([$id_producto, $_SESSION['usuario_id'], $comentario])) {
                $success = 'Comentario agregado exitosamente';
                // Recargar comentarios
                $stmt = $conn->prepare("SELECT c.*, u.nombre as usuario_nombre 
                                      FROM comentarios c 
                                      JOIN usuarios u ON c.id_usuario = u.id_usuario 
                                      WHERE c.id_producto = ? 
                                      ORDER BY c.fecha_comentario DESC");
                $stmt->execute([$id_producto]);
                $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error = 'Error al agregar el comentario';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['titulo']); ?> - CompuTec Marketplace</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .producto-detalle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        .imagenes-producto {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .imagen-producto {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .caracteristicas {
            margin: 2rem 0;
        }
        .caracteristica-item {
            display: grid;
            grid-template-columns: 1fr 2fr;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .comentarios {
            margin: 2rem 0;
        }
        .comentario {
            border: 1px solid #ddd;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        .comentario-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #666;
        }
        .contacto-vendedor {
            margin: 2rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
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
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <a href="perfil.php">Mi Perfil</a>
                        <a href="logout.php">Cerrar Sesión</a>
                    <?php else: ?>
                        <a href="login.php">Iniciar Sesión</a>
                        <a href="registro.php">Registrarse</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="producto-detalle">
            <div class="imagenes-producto">
                <?php if (empty($imagenes)): ?>
                    <img src="assets/img/default.jpg" alt="Imagen por defecto" class="imagen-producto">
                <?php else: ?>
                    <?php foreach($imagenes as $imagen): ?>
                        <img src="<?php echo htmlspecialchars($imagen['url_imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['titulo']); ?>" 
                             class="imagen-producto">
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="producto-info">
                <h1><?php echo htmlspecialchars($producto['titulo']); ?></h1>
                <p class="precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                <p class="categoria">Categoría: <?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                <p class="vendedor">Vendedor: <?php echo htmlspecialchars($producto['vendedor_nombre']); ?></p>
                <p class="estado">Estado: <?php echo ucfirst($producto['estado']); ?></p>
                <p class="descripcion"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>

                <?php if(!empty($caracteristicas)): ?>
                    <div class="caracteristicas">
                        <h2>Características</h2>
                        <?php foreach($caracteristicas as $caracteristica): ?>
                            <div class="caracteristica-item">
                                <strong><?php echo htmlspecialchars($caracteristica['nombre']); ?>:</strong>
                                <span><?php echo htmlspecialchars($caracteristica['valor']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="contacto-vendedor">
                    <h2>Contactar al Vendedor</h2>
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <?php if($_SESSION['usuario_id'] != $producto['id_vendedor']): ?>
                            <p>Email del vendedor: <?php echo htmlspecialchars($producto['vendedor_email']); ?></p>
                            <p>Estado del producto: <?php echo ucfirst($producto['estado']); ?></p>
                            <?php if($producto['estado'] == 'disponible'): ?>
                                <a href="mailto:<?php echo htmlspecialchars($producto['vendedor_email']); ?>" 
                                   class="btn btn-primary">Contactar Vendedor</a>
                            <?php else: ?>
                                <p class="text-muted">Este producto no está disponible actualmente</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Este es tu propio producto</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Debes <a href="login.php">iniciar sesión</a> para contactar al vendedor</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="comentarios">
            <h2>Comentarios</h2>
            <?php if(isset($_SESSION['usuario_id'])): ?>
                <form method="POST" class="form-comentario">
                    <div class="form-group">
                        <label for="comentario">Agregar comentario:</label>
                        <textarea name="comentario" id="comentario" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Publicar Comentario</button>
                </form>
            <?php else: ?>
                <p>Debes <a href="login.php">iniciar sesión</a> para comentar</p>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if(empty($comentarios)): ?>
                <p>No hay comentarios aún. ¡Sé el primero en comentar!</p>
            <?php else: ?>
                <?php foreach($comentarios as $comentario): ?>
                    <div class="comentario">
                        <div class="comentario-header">
                            <span class="usuario"><?php echo htmlspecialchars($comentario['usuario_nombre']); ?></span>
                            <span class="fecha"><?php echo date('d/m/Y H:i', strtotime($comentario['fecha_comentario'])); ?></span>
                        </div>
                        <p class="contenido"><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CompuTec Marketplace. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 