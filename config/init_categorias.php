<?php
require_once 'database.php';

try {
    $conn->beginTransaction();

    // Insertar categorías básicas para computadoras
    $categorias = [
        ['Laptops', 'Computadoras portátiles de segunda mano'],
        ['Desktops', 'Computadoras de escritorio de segunda mano'],
        ['Componentes', 'Partes y componentes de computadora'],
        ['Monitores', 'Monitores y pantallas'],
        ['Periféricos', 'Teclados, mouse, audífonos y otros accesorios'],
        ['Servidores', 'Servidores y equipos de red'],
        ['Tablets', 'Tablets y dispositivos similares'],
        ['Otros', 'Otros equipos y accesorios informáticos']
    ];

    $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion, estado) VALUES (?, ?, 1)");
    
    foreach ($categorias as $categoria) {
        $stmt->execute($categoria);
    }

    $conn->commit();
    echo "Categorías creadas exitosamente.";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error al crear las categorías: " . $e->getMessage();
}
?> 