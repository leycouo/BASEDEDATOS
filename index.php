<?php
session_start();
require_once 'config/database.php';

// Obtener productos destacados
$stmt = $conn->query("SELECT p.*, u.nombre as vendedor_nombre, c.nombre as categoria_nombre 
        FROM productos p 
        JOIN usuarios u ON p.id_vendedor = u.id_usuario 
        JOIN categorias c ON p.id_categoria = c.id_categoria 
        WHERE p.estado = 'disponible' 
        ORDER BY p.fecha_publicacion DESC 
        LIMIT 8");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CompuTec Marketplace - Computadoras de Segunda Mano</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern.css">
    <style>
        /* Estilos de prueba para verificar que los cambios se apliquen */
        body {
            background-color: #121212 !important;
            color: #FFFFFF !important;
            font-family: 'Poppins', sans-serif !important;
        }
        .header {
            background-color: rgba(30, 30, 30, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.4) !important;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2196F3, #1976D2) !important;
            color: #FFFFFF !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.4) !important;
        }
        .producto-card {
            background-color: rgba(30, 30, 30, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
        }
        .hero {
            background: linear-gradient(135deg, #1976D2, #00BCD4) !important;
            padding: 80px 0 !important;
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

    <main>
        <section class="hero">
            <div class="container">
                <h2>Bienvenido a CompuTec Marketplace</h2>
                <p>Tu plataforma de confianza para comprar y vender computadoras de segunda mano</p>
                <div style="margin-top: calc(var(--spacing-unit) * 4);">
                    <a href="productos.php" class="btn btn-primary">Ver Productos</a>
                    <?php if(!isset($_SESSION['usuario_id'])): ?>
                        <a href="registro.php" class="btn btn-secondary" style="margin-left: var(--spacing-unit);">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="container">
            <h2 style="text-align: center; margin-bottom: calc(var(--spacing-unit) * 4);">Productos Destacados</h2>
            <div class="productos-grid">
                <?php foreach($productos as $producto): ?>
                    <div class="producto-card">
                        <?php
                        $stmt = $conn->prepare("SELECT url_imagen FROM imagenes_producto WHERE id_producto = ? AND es_principal = 1 LIMIT 1");
                        $stmt->execute([$producto['id_producto']]);
                        $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                        <img src="<?php echo $imagen ? $imagen['url_imagen'] : 'assets/img/no-image.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($producto['titulo']); ?>" 
                             class="producto-imagen">
                        <div class="producto-info">
                            <h3 class="producto-titulo"><?php echo htmlspecialchars($producto['titulo']); ?></h3>
                            <p class="producto-precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                            <p class="producto-categoria"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></p>
                            <a href="producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-primary" style="width: 100%;">Ver Detalles</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CompuTec Marketplace. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 