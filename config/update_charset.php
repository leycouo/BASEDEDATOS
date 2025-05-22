<?php
require_once 'database.php';

try {
    // Actualizar la tabla comentarios
    $conn->exec("ALTER TABLE comentarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE comentarios MODIFY comentario TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Actualizar otras tablas que puedan contener texto
    $conn->exec("ALTER TABLE productos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE productos MODIFY titulo VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE productos MODIFY descripcion TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    $conn->exec("ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE usuarios MODIFY nombre VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE usuarios MODIFY email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    $conn->exec("ALTER TABLE categorias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE categorias MODIFY nombre VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE categorias MODIFY descripcion TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    $conn->exec("ALTER TABLE caracteristicas_producto CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE caracteristicas_producto MODIFY nombre VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("ALTER TABLE caracteristicas_producto MODIFY valor VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "Base de datos actualizada exitosamente a UTF-8.";
} catch(PDOException $e) {
    echo "Error al actualizar la base de datos: " . $e->getMessage();
}
?> 