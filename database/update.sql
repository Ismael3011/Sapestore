CREATE TABLE Categoria_Marca (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    marca_id INT NOT NULL,
    FOREIGN KEY (marca_id) REFERENCES Marca(ID) ON DELETE CASCADE
);

ALTER TABLE Producto ADD COLUMN categoria_marca_id INT NULL;
ALTER TABLE Producto ADD CONSTRAINT fk_categoria_marca FOREIGN KEY (categoria_marca_id) REFERENCES Categoria_Marca(ID) ON DELETE SET NULL;

CREATE TABLE Producto_Imagen (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    imagen_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (producto_id) REFERENCES Producto(ID) ON DELETE CASCADE
);

ALTER TABLE Producto MODIFY COLUMN categoria_marca_id INT NULL;

ALTER TABLE Pedidos ADD COLUMN direccion TEXT NOT NULL;

