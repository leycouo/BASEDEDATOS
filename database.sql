-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS computec_marketplace;
USE computec_marketplace;

-- Tabla de Usuarios
CREATE TABLE usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    tipo_usuario ENUM('admin', 'vendedor', 'comprador') NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado BOOLEAN DEFAULT TRUE
);

-- Tabla de Categorías
CREATE TABLE categorias (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    estado BOOLEAN DEFAULT TRUE
);

-- Tabla de Productos
CREATE TABLE productos (
    id_producto INT PRIMARY KEY AUTO_INCREMENT,
    id_vendedor INT NOT NULL,
    id_categoria INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    estado ENUM('disponible', 'vendido', 'reservado') DEFAULT 'disponible',
    fecha_publicacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vendedor) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

-- Tabla de Características de Productos
CREATE TABLE caracteristicas_producto (
    id_caracteristica INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE
);

-- Tabla de Imágenes de Productos
CREATE TABLE imagenes_producto (
    id_imagen INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    url_imagen VARCHAR(255) NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE
);

-- Tabla de Transacciones
CREATE TABLE transacciones (
    id_transaccion INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_comprador INT NOT NULL,
    id_vendedor INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_transaccion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'pendiente',
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_comprador) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_vendedor) REFERENCES usuarios(id_usuario)
);

-- Tabla de Comentarios
CREATE TABLE comentarios (
    id_comentario INT PRIMARY KEY AUTO_INCREMENT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    comentario TEXT NOT NULL,
    fecha_comentario DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Tabla de Favoritos
CREATE TABLE favoritos (
    id_favorito INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_producto INT NOT NULL,
    fecha_agregado DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    UNIQUE KEY unique_favorito (id_usuario, id_producto)
);

-- Índices
CREATE INDEX idx_productos_categoria ON productos(id_categoria);
CREATE INDEX idx_productos_vendedor ON productos(id_vendedor);
CREATE INDEX idx_productos_estado ON productos(estado);
CREATE INDEX idx_transacciones_producto ON transacciones(id_producto);
CREATE INDEX idx_transacciones_comprador ON transacciones(id_comprador);
CREATE INDEX idx_transacciones_vendedor ON transacciones(id_vendedor);
CREATE INDEX idx_comentarios_producto ON comentarios(id_producto);
CREATE INDEX idx_favoritos_usuario ON favoritos(id_usuario);

-- Procedimiento almacenado para actualizar el estado del producto
DELIMITER //
CREATE PROCEDURE actualizar_estado_producto(
    IN p_id_producto INT,
    IN p_nuevo_estado VARCHAR(20)
)
BEGIN
    UPDATE productos 
    SET estado = p_nuevo_estado 
    WHERE id_producto = p_id_producto;
END //
DELIMITER ;

-- Trigger para actualizar el estado del producto después de una transacción
DELIMITER //
CREATE TRIGGER after_transaccion_completada
AFTER UPDATE ON transacciones
FOR EACH ROW
BEGIN
    IF NEW.estado = 'completada' AND OLD.estado != 'completada' THEN
        CALL actualizar_estado_producto(NEW.id_producto, 'vendido');
    END IF;
END //
DELIMITER ;

-- Función para calcular el promedio de precios por categoría
DELIMITER //
CREATE FUNCTION calcular_promedio_precio_categoria(p_id_categoria INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE promedio DECIMAL(10,2);
    SELECT AVG(precio) INTO promedio
    FROM productos
    WHERE id_categoria = p_id_categoria AND estado = 'disponible';
    RETURN IFNULL(promedio, 0);
END //
DELIMITER ;

-- Creación de usuarios y privilegios
CREATE USER 'admin_computec'@'localhost' IDENTIFIED BY 'admin123';
CREATE USER 'vendedor_computec'@'localhost' IDENTIFIED BY 'vendedor123';
CREATE USER 'comprador_computec'@'localhost' IDENTIFIED BY 'comprador123';

-- Privilegios para administrador
GRANT ALL PRIVILEGES ON computec_marketplace.* TO 'admin_computec'@'localhost';

-- Privilegios para vendedor
GRANT SELECT, INSERT, UPDATE ON computec_marketplace.productos TO 'vendedor_computec'@'localhost';
GRANT SELECT ON computec_marketplace.categorias TO 'vendedor_computec'@'localhost';
GRANT SELECT ON computec_marketplace.transacciones TO 'vendedor_computec'@'localhost';

-- Privilegios para comprador
GRANT SELECT ON computec_marketplace.productos TO 'comprador_computec'@'localhost';
GRANT SELECT ON computec_marketplace.categorias TO 'comprador_computec'@'localhost';
GRANT INSERT, SELECT ON computec_marketplace.favoritos TO 'comprador_computec'@'localhost';
GRANT INSERT, SELECT ON computec_marketplace.comentarios TO 'comprador_computec'@'localhost';

FLUSH PRIVILEGES; 