<?php
require_once 'includes/functions.php';

// Cerrar sesión usando la función mejorada
logout_user();

// Redirigir a la página de login
header("Location: login.php");
exit;
?> 