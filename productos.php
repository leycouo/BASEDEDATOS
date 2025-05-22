<?php
session_start();
require_once 'config/database.php';

// Parámetros de filtrado y paginación
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$precio_min = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : 0;
$precio_max = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : 999999;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 12;

// Construir la consulta base
$sql = "SELECT p.*, u.nombre as vendedor_nombre, c.nombre as categoria_nombre 
        FROM productos p 
        JOIN usuarios u ON p.id_vendedor = u.id_usuario 
        JOIN categorias c ON p.id_categoria = c.id_categoria 
        WHERE p.estado = 'disponible'";

$params = [];

if ($categoria > 0) {
    $sql .= " AND p.id_categoria = ?";
    $params[] = $categoria;
}

if ($precio_min > 0) {
    $sql .= " AND p.precio >= ?";
    $params[] = $precio_min;
}

if ($precio_max < 999999) {
    $sql .= " AND p.precio <= ?";
    $params[] = $precio_max;
}

if (!empty($busqueda)) {
    $sql .= " AND (p.titulo LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

// Obtener el total de productos para la paginación
$stmt = $conn->prepare(str_replace("p.*, u.nombre as vendedor_nombre, c.nombre as categoria_nombre", "COUNT(*)", $sql));
$stmt->execute($params);
$total_productos = $stmt->fetchColumn();
$total_paginas = ceil($total_productos / $por_pagina);

// Ajustar la página actual si es necesario
if ($pagina > $total_paginas) {
    $pagina = $total_paginas;
}
if ($pagina < 1) {
    $pagina = 1;
}

// Calcular el offset
$offset = ($pagina - 1) * $por_pagina;

// Agregar ordenamiento y límite
$sql .= " ORDER BY p.fecha_publicacion DESC LIMIT $por_pagina OFFSET $offset";

// Obtener los productos
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el filtro
$stmt = $conn->query("SELECT * FROM categorias WHERE estado = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - CompuTec Marketplace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filtros-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .imagenes-actuales {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .imagen-item {
            border: 1px solid #ddd;
            padding: 0.5rem;
            border-radius: 4px;
        }
        .imagen-item img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        .caracteristica-item {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .paginacion {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        .paginacion a {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .paginacion a:hover {
            background-color: #f5f5f5;
        }
        .paginacion .btn-primary {
            background-color: var(--secondary-color);
            color: white;
            border: none;
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
                <h2>Explora Nuestros Productos</h2>
                <p>Encuentra la computadora de segunda mano que estás buscando</p>
            </div>
        </section>

        <section class="container">
            <div class="card">
                <form method="GET" action="productos.php" class="filtros-form">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: calc(var(--spacing-unit) * 2);">
                        <div class="form-group">
                            <label for="busqueda">Buscar:</label>
                            <input type="text" id="busqueda" name="busqueda" class="form-control" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Título o descripción">
                        </div>

                        <div class="form-group">
                            <label for="categoria">Categoría:</label>
                            <select id="categoria" name="categoria" class="form-control">
                                <option value="">Todas las categorías</option>
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id_categoria']; ?>" 
                                            <?php echo $categoria == $cat['id_categoria'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="precio_min">Precio mínimo:</label>
                            <input type="number" id="precio_min" name="precio_min" class="form-control" 
                                   value="<?php echo $precio_min; ?>" min="0" step="0.01">
                        </div>

                        <div class="form-group">
                            <label for="precio_max">Precio máximo:</label>
                            <input type="number" id="precio_max" name="precio_max" class="form-control" 
                                   value="<?php echo $precio_max; ?>" min="0" step="0.01">
                        </div>
                    </div>

                    <div style="margin-top: calc(var(--spacing-unit) * 2); text-align: center;">
                        <button type="submit" class="btn btn-primary">Filtrar Productos</button>
                        <a href="productos.php" class="btn btn-secondary" style="margin-left: var(--spacing-unit);">Limpiar Filtros</a>
                    </div>
                </form>
            </div>

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

            <?php if($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if($pagina > 1): ?>
                        <a href="?pagina=<?php echo $pagina-1; ?>&categoria=<?php echo $categoria; ?>&precio_min=<?php echo $precio_min; ?>&precio_max=<?php echo $precio_max; ?>&busqueda=<?php echo urlencode($busqueda); ?>">&laquo; Anterior</a>
                    <?php endif; ?>

                    <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?pagina=<?php echo $i; ?>&categoria=<?php echo $categoria; ?>&precio_min=<?php echo $precio_min; ?>&precio_max=<?php echo $precio_max; ?>&busqueda=<?php echo urlencode($busqueda); ?>" 
                           class="<?php echo $i == $pagina ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if($pagina < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina+1; ?>&categoria=<?php echo $categoria; ?>&precio_min=<?php echo $precio_min; ?>&precio_max=<?php echo $precio_max; ?>&busqueda=<?php echo urlencode($busqueda); ?>">Siguiente &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CompuTec Marketplace. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 