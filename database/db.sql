CREATE DATABASE Sapestore;
USE Sapestore;

CREATE TABLE Categoria (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT
);

CREATE TABLE Marca (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    logo_url VARCHAR(255)
);

CREATE TABLE Usuario (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    mail VARCHAR(255) UNIQUE NOT NULL,
    direccion TEXT,
    telefono VARCHAR(20),
    rol ENUM('admin', 'cliente') NOT NULL
);

CREATE TABLE Producto (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    imagen_url VARCHAR(255),
    categoria_id INT,
    marca_id INT,
    FOREIGN KEY (categoria_id) REFERENCES Categoria(ID) ON DELETE SET NULL,
    FOREIGN KEY (marca_id) REFERENCES Marca(ID) ON DELETE SET NULL
);

CREATE TABLE Talla (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    numero VARCHAR(10) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL
);

CREATE TABLE Producto_Talla (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT,
    talla_id INT,
    FOREIGN KEY (producto_id) REFERENCES Producto(ID) ON DELETE CASCADE,
    FOREIGN KEY (talla_id) REFERENCES Talla(ID) ON DELETE CASCADE
);

CREATE TABLE Pedidos (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES Usuario(ID) ON DELETE RESTRICT
);

CREATE TABLE Detalles_pedido (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    pedido_id INT,
    producto_talla_id INT,
    cantidad INT NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES Pedidos(ID) ON DELETE CASCADE,
    FOREIGN KEY (producto_talla_id) REFERENCES Producto_Talla(ID) ON DELETE CASCADE
);
