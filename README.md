**Mícrofy - Sistema POS y Gestión de Inventario Multi-negocio**

Mícrofy es una plataforma web (SaaS) diseñada para ayudar a los micronegocios a administrar su inventario, registrar ventas de forma rápida mediante una interfaz de Punto de Venta (POS) y analizar su crecimiento a través de reportes financieros y gráficas dinámicas.



El sistema utiliza una arquitectura Multi-tenant (Multi-inquilino), lo que significa que múltiples usuarios pueden registrarse y operar sus negocios de forma totalmente aislada, privada y segura en la misma plataforma.



**Características Principales**

Arquitectura Multi-Negocio: Cada usuario registrado tiene su propio espacio de trabajo. Los productos, ventas y reportes están completamente aislados del resto de usuarios mediante validaciones estrictas en el servidor.



Punto de Venta (POS) Interactivo: Interfaz visual con catálogo de productos, carrito de compras en tiempo real, validación de stock y calculadora automática de cambio en efectivo.



Gestión de Inventario (CRUD): Creación, lectura, edición y eliminación de productos con soporte para carga de imágenes fotográficas.



Analíticas y Reportes Avanzados:



Generación de tickets detallados de venta.



Filtros de búsqueda por rangos de fechas personalizables.



Gráficas dinámicas (Chart.js): Visualización de ingresos diarios y mensuales.



Modo Comparativa Anual: Herramienta para cruzar los datos de ingresos entre dos años distintos con cálculo automático de crecimiento.



Exportación nativa a impresión y PDF.



Experiencia de Usuario (UX/UI):



Script universal inyectado para Modo Oscuro (Dark Mode) con persistencia de preferencias usando localStorage.



Diseño 100% responsivo y estético sin depender de frameworks CSS pesados.



Burbuja flotante interactiva para navegación rápida.



Seguridad: Autenticación segura mediante encriptación password\_hash() y prevención de inyección SQL utilizando PDO (PHP Data Objects) y sentencias preparadas.



Tecnologías Utilizadas

Backend: PHP 8+ (Sesiones y PDO)



Base de Datos: MySQL / MariaDB (Entorno XAMPP)



Frontend: HTML5, CSS3, Vanilla JavaScript



Librerías Externas: Chart.js (Para la renderización de estadísticas)



**Instalación y Configuración Local**

Sigue estos pasos para ejecutar el proyecto en tu entorno local usando XAMPP, WAMP o similar:



Clonar o Descargar el Proyecto

Descarga los archivos de este repositorio y colócalos en la carpeta raíz de tu servidor web local.



Si usas XAMPP, la ruta es: C:\\xampp\\htdocs\\microfy



Configurar la Base de Datos



Inicia los servicios de Apache y MySQL en el panel de control de XAMPP.



Abre tu navegador y accede a http://localhost/phpmyadmin/.



Crea una nueva base de datos llamada mi\_negocio (o el nombre que hayas definido).



Selecciona la base de datos recién creada, ve a la pestaña Importar y sube el archivo .sql incluido en este repositorio (ej. base\_de\_datos.sql).



Verificar la Conexión (Opcional)

Si tu servidor local tiene una contraseña configurada para MySQL, abre el archivo conexion.php y actualiza las credenciales:



PHP

$host = 'localhost';

$dbname = 'mi\_negocio';

$username = 'root'; // Tu usuario de MySQL

$password = '';     // Tu contraseña de MySQL (vacío por defecto en XAMPP)

Ejecutar la Aplicación

Abre tu navegador web y visita:

http://localhost/microfy



**Uso Básico**

Registro: Crea una cuenta nueva en la pantalla principal.



Inventario: Agrega al menos un producto con nombre, precio, stock inicial y una fotografía.



Vender: Dirígete a "Caja Registradora", haz clic en los productos para agregarlos al carrito, ingresa el efectivo recibido y confirma la venta.



Reportes: Ve a "Mis Reportes" para ver el ticket generado, visualizar la gráfica de ingresos de hoy o hacer una comparativa anual.



Desarrollado por Felipe Alejandro Acuña Jaime



Estudiante de Ingeniería de Sistemas - Proyecto Académico y Portafolio

