<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] != 'vendedor') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: perfil.php');
    exit();
}

$id_producto = (int)$_GET['id'];

// Verificar que el producto pertenece al usuario
$stmt = $conn->prepare("SELECT * FROM productos WHERE id_producto = ? AND id_vendedor = ?");
$stmt->execute([$id_producto, $_SESSION['usuario_id']]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header('Location: perfil.php');
    exit();
}

$error = '';
$success = '';

// Obtener categorías
$stmt = $conn->query("SELECT * FROM categorias WHERE estado = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener características actuales
$stmt = $conn->prepare("SELECT * FROM caracteristicas_producto WHERE id_producto = ?");
$stmt->execute([$id_producto]);
$caracteristicas_actuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener imágenes actuales
$stmt = $conn->prepare("SELECT * FROM imagenes_producto WHERE id_producto = ? ORDER BY es_principal DESC");
$stmt->execute([$id_producto]);
$imagenes_actuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $categoria = filter_input(INPUT_POST, 'categoria', FILTER_VALIDATE_INT);
    $caracteristicas = $_POST['caracteristicas'] ?? [];
    $imagenes_eliminar = $_POST['imagenes_eliminar'] ?? [];

    // Validaciones
    if (empty($titulo) || empty($descripcion) || $precio === false || $categoria === false) {
        $error = 'Por favor, complete todos los campos requeridos.';
    } elseif ($precio <= 0) {
        $error = 'El precio debe ser mayor a 0.';
    } else {
        try {
            $conn->beginTransaction();

            // Actualizar producto
            $stmt = $conn->prepare("UPDATE productos SET titulo = ?, descripcion = ?, precio = ?, id_categoria = ? WHERE id_producto = ?");
            $stmt->execute([
                $titulo,
                $descripcion,
                $precio,
                $categoria,
                $id_producto
            ]);

            // Eliminar características existentes
            $stmt = $conn->prepare("DELETE FROM caracteristicas_producto WHERE id_producto = ?");
            $stmt->execute([$id_producto]);

            // Insertar nuevas características
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

            // Eliminar imágenes seleccionadas
            if (!empty($imagenes_eliminar)) {
                $stmt = $conn->prepare("DELETE FROM imagenes_producto WHERE id_imagen = ? AND id_producto = ?");
                foreach ($imagenes_eliminar as $id_imagen) {
                    $stmt->execute([$id_imagen, $id_producto]);
                }
            }

            // Procesar nuevas imágenes
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
                                0 // Las nuevas imágenes no son principales
                            ]);
                        }
                    }
                }
            }

            $conn->commit();
            $success = 'Producto actualizado exitosamente.';
            
            // Recargar datos
            $stmt = $conn->prepare("SELECT * FROM productos WHERE id_producto = ?");
            $stmt->execute([$id_producto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("SELECT * FROM caracteristicas_producto WHERE id_producto = ?");
            $stmt->execute([$id_producto]);
            $caracteristicas_actuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("SELECT * FROM imagenes_producto WHERE id_producto = ? ORDER BY es_principal DESC");
            $stmt->execute([$id_producto]);
            $imagenes_actuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Error al actualizar el producto: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - CompuTec Marketplace</title>
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
            <h2>Editar Producto</h2>

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

            <form method="POST" action="editar_producto.php?id=<?php echo $id_producto; ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="titulo">Título:</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" 
                           value="<?php echo htmlspecialchars($producto['titulo']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="precio">Precio:</label>
                    <input type="number" id="precio" name="precio" class="form-control" step="0.01" min="0" 
                           value="<?php echo $producto['precio']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select id="categoria" name="categoria" class="form-control" required>
                        <?php foreach($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id_categoria']; ?>" 
                                    <?php echo $categoria['id_categoria'] == $producto['id_categoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Características:</label>
                    <div id="caracteristicas-container">
                        <?php foreach($caracteristicas_actuales as $index => $caracteristica): ?>
                            <div class="caracteristica-item">
                                <input type="text" name="caracteristicas[<?php echo $index; ?>][nombre]" 
                                       class="form-control" placeholder="Nombre" 
                                       value="<?php echo htmlspecialchars($caracteristica['nombre']); ?>">
                                <input type="text" name="caracteristicas[<?php echo $index; ?>][valor]" 
                                       class="form-control" placeholder="Valor" 
                                       value="<?php echo htmlspecialchars($caracteristica['valor']); ?>">
                                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Eliminar</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="agregarCaracteristica()">Agregar Característica</button>
                </div>

                <div class="form-group">
                    <label>Imágenes Actuales:</label>
                    <div class="imagenes-actuales">
                        <?php foreach($imagenes_actuales as $imagen): ?>
                            <div class="imagen-item">
                                <img src="<?php echo $imagen['url_imagen']; ?>" alt="Imagen del producto" style="max-width: 100px;">
                                <div>
                                    <label>
                                        <input type="checkbox" name="imagenes_eliminar[]" value="<?php echo $imagen['id_imagen']; ?>">
                                        Eliminar
                                    </label>
                                    <?php if($imagen['es_principal']): ?>
                                        <span class="badge">Principal</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="imagenes">Agregar Nuevas Imágenes:</label>
                    <input type="file" id="imagenes" name="imagenes[]" class="form-control" multiple accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary">Actualizar Producto</button>
                <a href="perfil.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CompuTec Marketplace. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        let caracteristicaCount = <?php echo count($caracteristicas_actuales); ?>;

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