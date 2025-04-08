<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificador RRSS - Ebone</title>
    <!-- Google Fonts - Open Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="app-container">
        <!-- Menú lateral -->
        <aside class="sidebar">
            <div class="logo">
                <h1>Planificador</h1>
                <span>RRSS Ebone</span>
            </div>
            <nav class="sidebar-nav">
                <p class="nav-title">Líneas de Negocio</p>
                <ul>
                    <?php
                    $lineasNegocio = getAllLineasNegocio();
                    foreach ($lineasNegocio as $linea): 
                    ?>
                    <li>
                        <a href="index.php?linea=<?php echo $linea['id']; ?>" <?php echo (isset($_GET['linea']) && $_GET['linea'] == $linea['id']) ? 'class="active"' : ''; ?>>
                            <i class="fas fa-chart-line"></i>
                            <?php echo $linea['nombre']; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <p>&copy; <?php echo date('Y'); ?> Ebone</p>
            </div>
        </aside>

        <!-- Contenido principal -->
        <main class="main-content">
            <header class="content-header">
                <?php if (isset($_GET['linea'])): 
                    $currentLinea = null;
                    foreach ($lineasNegocio as $linea) {
                        if ($linea['id'] == $_GET['linea']) {
                            $currentLinea = $linea;
                            break;
                        }
                    }
                ?>
                <h2><?php echo $currentLinea ? $currentLinea['nombre'] : 'Todas las líneas'; ?></h2>
                <?php else: ?>
                <h2>Dashboard</h2>
                <?php endif; ?>
            </header>
            <div class="content-body">
                <!-- El contenido específico irá aquí -->
            </div>
        </main>
    </div>
</body>
</html> 