<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'vendedor') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Obtener categorías
$stmt = $conn->query("SELECT * FROM categorias WHERE estado = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $categoria = filter_input(INPUT_POST, 'categoria', FILTER_VALIDATE_INT);
    $caracteristicas = $_POST['caracteristicas'] ?? [];

    // Validaciones
    if (empty($titulo) || empty($descripcion) || $precio === false || $categoria === false) {
        $error = 'Por favor, complete todos los campos requeridos.';
    } elseif ($precio <= 0) {
        $error = 'El precio debe ser mayor a 0.';
    } else {
        try {
            $conn->beginTransaction();

            // Insertar producto
            $stmt = $conn->prepare("INSERT INTO productos (id_vendedor, id_categoria, titulo, descripcion, precio) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['usuario_id'],
                $categoria,
                $titulo,
                $descripcion,
                $precio
            ]);

            $id_producto = $conn->lastInsertId();

            // Insertar características
            if (!empty($caracteristicas)) {
                $stmt = $conn->prepare("INSERT INTO caracteristicas_producto (id_producto, nombre, valor) VALUES (?, ?, ?)");
                foreach ($caracteristicas as $caracteristica) {
                    if (!empty($caracteristica['nombre']) && !empty($caracteristica['valor'])) {
                        $stmt->execute([
                            $id_producto,
                            $caracteristica['nombre'],
                            $caracteristica['valor']
                        ]);
                    }
                }
            }

            // Procesar imágenes
            if (!empty($_FILES['imagenes']['name'][0])) {
                $upload_dir = 'uploads/productos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $stmt = $conn->prepare("INSERT INTO imagenes_producto (id_producto, url_imagen, es_principal) VALUES (?, ?, ?)");
                
                foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = uniqid() . '_' . $_FILES['imagenes']['name'][$key];
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $stmt->execute([
                                $id_producto,
                                $file_path,
                                $key === 0 ? 1 : 0 // La primera imagen es la principal
                            ]);
                        }
                    }
                }
            }

            $conn->commit();
            $success = 'Producto agregado exitosamente.';
            header("refresh:2;url=perfil.php");
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Error al agregar el producto: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - CompuTec Marketplace</title>
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
        <div class="card" style="max-width: 800px; margin: 40px auto;">
            <h2>Agregar Nuevo Producto</h2>

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

            <form method="POST" action="agregar_producto.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titulo">Título:</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" required></textarea>
                </div>

                <div class="form-group">
                    <label for="precio">Precio:</label>
                    <input type="number" id="precio" name="precio" class="form-control" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" class="form-control" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id_categoria']; ?>">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Características:</label>
                    <div id="caracteristicas-container">
                        <div class="caracteristica-item">
                            <input type="text" name="caracteristicas[0][nombre]" class="form-control" placeholder="Nombre">
                            <input type="text" name="caracteristicas[0][valor]" class="form-control" placeholder="Valor">
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="agregarCaracteristica()">Agregar Característica</button>
                </div>

                <div class="form-group">
                    <label for="imagenes">Imágenes:</label>
                    <input type="file" id="imagenes" name="imagenes[]" class="form-control" multiple accept="image/*">
                    <small>La primera imagen será la imagen principal del producto.</small>
                </div>

                <button type="submit" class="btn btn-primary">Agregar Producto</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CompuTec Marketplace. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        let caracteristicaCount = 1;

        function agregarCaracteristica() {
            const container = document.getElementById('caracteristicas-container');
            const div = document.createElement('div');
            div.className = 'caracteristica-item';
            div.innerHTML = `
                <input type="text" name="caracteristicas[${caracteristicaCount}][nombre]" class="form-control" placeholder="Nombre">
                <input type="text" name="caracteristicas[${caracteristicaCount}][valor]" class="form-control" placeholder="Valor">
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Eliminar</button>
            `;
            container.appendChild(div);
            caracteristicaCount++;
        }
    </script>
</body>
</html> 