<?php
$host = "localhost";
$usuario = "root"; // Usuario por defecto de MySQL en XAMPP
$password = ""; // En XAMPP, la contraseña suele estar vacía
$base_de_datos = "mi_negocio";

try {
    // Creamos la conexión a la base de datos
    $conexion = new PDO("mysql:host=$host;dbname=$base_de_datos;charset=utf8", $usuario, $password);
    
    // Configuramos PDO para que nos muestre los errores si algo falla
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    //echo "¡Conexión exitosa a la base de datos!"; 
} catch(PDOException $e) {
    //echo "Error de conexión: " . $e->getMessage();
}
?>

