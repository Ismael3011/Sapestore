# Sapestore

Sapestore es una tienda en línea especializada en zapatillas y ropa de moda urbana. Este proyecto incluye funciones para la gestión de productos, usuarios, pedidos y más.

## Características

- **Catálogo de productos**: Visualización de productos con imágenes, tallas y precios.
- **Carrito de compras**: Añade productos al carrito y realiza pedidos.
- **Gestión de usuarios**: Registro, inicio de sesión, actualización de datos y ver tus pedidos realizados.
- **Panel de administración**: Gestión de productos, marcas, categorías, pedidos... Con la posibilidad de poder registrar, actualizar, eliminar...
- **Búsqueda avanzada**: Encuentra productos por nombre o marca.
- **Productos populares y entrega rápida**: Destaca productos populares y con entrega rápida, los productos destacados por un administrador desde la parte de administracion apareceran en la pagina con un fuego, en caso de ser productos populares, o un cohete en caso de ser productos con entrega rapida.
- **Diseño responsivo**: Adaptado para dispositivos móviles y de escritorio.

## Requisitos

- **Servidor web**: Apache.
- **Base de datos**: MySQL.
- **PHP**: Versión 8.1 o superior.
- **Docker (opcional)**: Para despliegue con contenedores.

## Instalación

1. Clona este repositorio en tu servidor local:
   ```bash
   git clone https://github.com/Ismael3011/Sapestore
   ```
2. Configura la base de datos:
   - Descargar el archivo `db.sql` y `update.sql` y crear la base de datos.

3. Accede al proyecto:
   - Si usas XAMPP, coloca los archivos en la carpeta `htdocs` y accede a `http://localhost/Sapestore`.
   - Si usas Docker:
     - Ve a la carpeta `docker` y ejecuta:
       ```bash
       docker-compose up -d
       ```
     - Accede a `http://localhost:8080`.

## Uso

### Usuario
- Regístrate o inicia sesión para explorar productos y realizar pedidos.
- Añade productos al carrito y procede al pago.
- Ve tus pedidos realizados en el apartado de usuario.

### Administrador
- Accede al panel de administración para gestionar usuarios, productos, marcas, categorías, pedidos...
- Destaca productos como populares o con entrega rápida.
- Visualiza en el apartado de carrito todos los pedidos realizados por los clientes.

## Estructura del Proyecto

- **admin/**: Archivos para la gestión administrativa.
- **partes/**: Componentes reutilizables como el navbar y footer.
- **fotos/**: Imágenes de productos y recursos visuales.
- **styles.css**: Estilos principales del proyecto.
- **config.php**: Configuración de la base de datos.
- **database**: Archivos para la creacion de la base de datos.
- **js/css**: Archivos de bootstrap para la correcta visualizacion de la pagina.

## Créditos

- **Propietario**: Ismael Fernandez Archilla.
- **Contacto**: [ismaelarchi@gmail.com](mailto:ismaelarchi@gmail.com)
