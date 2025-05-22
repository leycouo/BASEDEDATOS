<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING);
    $tipo_usuario = filter_input(INPUT_POST, 'tipo_usuario', FILTER_SANITIZE_STRING);

    // Validaciones
    if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Este correo electrónico ya está registrado.';
        } else {
            try {
                $conn->beginTransaction();

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellido, email, password, telefono, direccion, tipo_usuario) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $nombre,
                    $apellido,
                    $email,
                    $hashed_password,
                    $telefono,
                    $direccion,
                    $tipo_usuario
                ]);

                $conn->commit();
                $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
                
                // Redirigir después de 2 segundos
                header("refresh:2;url=login.php");
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = 'Error al registrar el usuario. Por favor, intente nuevamente.';
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
    <title>Registro - CompuTec Marketplace</title>
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
                    <a href="login.php">Iniciar Sesión</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="card" style="max-width: 600px; margin: 40px auto;">
            <h2>Registro de Usuario</h2>
            
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

            <form method="POST" action="registro.php">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono:</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control">
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección:</label>
                    <textarea id="direccion" name="direccion" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label for="tipo_usuario">Tipo de Usuario:</label>
                    <select id="tipo_usuario" name="tipo_usuario" class="form-control" required>
                        <option value="comprador">Comprador</option>
                        <option value="vendedor">Vendedor</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Registrarse</button>
            </form>

            <p style="margin-top: 20px; text-align: center;">
                ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
            </p>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CompuTec Marketplace. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html> 