<?php
session_start();
session_destroy(); // Destruimos todas las variables de sesión
header("Location: login.php"); // Lo mandamos de vuelta al inicio
exit;
?>