<?php
require_once 'includes/functions.php';

// Verificar que tengamos el parámetro de línea de negocio
if (!isset($_GET['linea'])) {
    header("Location: index.php");
    exit;
}

$lineaId = $_GET['linea'];

// Determinar la línea de negocio y la página de retorno
$lineaNombre = '';
$paginaRetorno = '';
switch($lineaId) {
    case 1: 
        $lineaNombre = 'Ebone Servicios'; 
        $paginaRetorno = 'ebone.php'; 
        break;
    case 2: 
        $lineaNombre = 'CUBOFIT'; 
        $paginaRetorno = 'cubofit.php'; 
        break;
    case 3: 
        $lineaNombre = 'Uniges-3'; 
        $paginaRetorno = 'uniges.php'; 
        break;
    case 4: 
        $lineaNombre = 'Teiá'; 
        $paginaRetorno = 'teia.php'; 
        break;
    default: 
        header("Location: index.php");
        exit;
}

// Establecer conexión a BD
$db = getDbConnection();

// Obtener las redes sociales disponibles para esta línea
$stmt = $db->prepare("
    SELECT r.* 
    FROM redes_sociales r
    JOIN linea_negocio_red_social lnrs ON r.id = lnrs.red_social_id
    WHERE lnrs.linea_negocio_id = ?
");
$stmt->execute([$lineaId]);
$redesDisponibles = $stmt->fetchAll();

// Variables para el formulario
$modo = 'crear';
$publicacion = [
    'id' => '',
    'contenido' => '',
    'imagen_url' => '',
    'fecha_programada' => date('Y-m-d'),
    'estado' => 'borrador',
    'linea_negocio_id' => $lineaId
];
$redesSeleccionadas = [];
$errores = [];

// Verificar si estamos editando
if (isset($_GET['id'])) {
    $modo = 'editar';
    $publicacionId = $_GET['id'];
    
    // Obtener la publicación
    $stmt = $db->prepare("SELECT * FROM publicaciones WHERE id = ? AND linea_negocio_id = ?");
    $stmt->execute([$publicacionId, $lineaId]);
    $publicacionBD = $stmt->fetch();
    
    if ($publicacionBD) {
        $publicacion = $publicacionBD;
        
        // Formatear la fecha para que funcione con el input type="date"
        if (!empty($publicacion['fecha_programada'])) {
            $publicacion['fecha_programada'] = date('Y-m-d', strtotime($publicacion['fecha_programada']));
        }
        
        // Obtener redes sociales seleccionadas
        $stmt = $db->prepare("SELECT red_social_id FROM publicacion_red_social WHERE publicacion_id = ?");
        $stmt->execute([$publicacionId]);
        $redesBD = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $redesSeleccionadas = $redesBD;
    } else {
        header("Location: " . $paginaRetorno);
        exit;
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar inputs
    $contenido = $_POST['contenido']; // No filtramos para permitir formato
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
    $redes = isset($_POST['redes']) ? $_POST['redes'] : [];
    
    // Validar campos requeridos
    if (empty($contenido)) {
        $errores[] = "El contenido es obligatorio";
    }
    
    if (empty($fecha)) {
        $errores[] = "La fecha es obligatoria";
    }
    
    if (empty($redes)) {
        $errores[] = "Debe seleccionar al menos una red social";
    }
    
    // Procesar imagen si se sube una nueva
    $imagen_url = $publicacion['imagen_url']; // Mantener la actual por defecto
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['imagen']['name'];
        $tmp_name = $_FILES['imagen']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errores[] = "Formato de imagen no permitido. Use: jpg, jpeg, png o gif.";
        } else {
            // Crear directorio si no existe
            $upload_dir = 'uploads/' . $lineaId . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generar nombre único
            $new_filename = uniqid() . '.' . $ext;
            $destino = $upload_dir . $new_filename;
            
            if (move_uploaded_file($tmp_name, $destino)) {
                // Si hay una imagen anterior, la eliminamos
                if (!empty($publicacion['imagen_url']) && file_exists($publicacion['imagen_url']) && $modo === 'editar') {
                    unlink($publicacion['imagen_url']);
                }
                $imagen_url = $destino;
            } else {
                $errores[] = "Error al subir la imagen";
            }
        }
    }
    
    // Si no hay errores, guardar en la BD
    if (empty($errores)) {
        try {
            $db->beginTransaction();
            
            if ($modo === 'crear') {
                $stmt = $db->prepare("
                    INSERT INTO publicaciones (contenido, imagen_url, fecha_programada, estado, linea_negocio_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$contenido, $imagen_url, $fecha, $estado, $lineaId]);
                $publicacionId = $db->lastInsertId();
            } else {
                $stmt = $db->prepare("
                    UPDATE publicaciones 
                    SET contenido = ?, imagen_url = ?, fecha_programada = ?, estado = ?
                    WHERE id = ?
                ");
                $stmt->execute([$contenido, $imagen_url, $fecha, $estado, $publicacion['id']]);
                $publicacionId = $publicacion['id'];
            }
            
            // Eliminar relaciones anteriores con redes sociales si estamos editando
            if ($modo === 'editar') {
                $stmt = $db->prepare("DELETE FROM publicacion_red_social WHERE publicacion_id = ?");
                $stmt->execute([$publicacionId]);
            }
            
            // Insertar nuevas relaciones con redes sociales
            $stmt = $db->prepare("INSERT INTO publicacion_red_social (publicacion_id, red_social_id) VALUES (?, ?)");
            foreach ($redes as $redId) {
                $stmt->execute([$publicacionId, $redId]);
            }
            
            $db->commit();
            
            // Redirigir a la página de la línea
            header("Location: " . $paginaRetorno);
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
    
    // Si hay errores, prepopular el formulario con los valores enviados
    $publicacion['contenido'] = $contenido;
    $publicacion['fecha_programada'] = $fecha;
    $publicacion['estado'] = $estado;
    $publicacion['imagen_url'] = $imagen_url;
    $redesSeleccionadas = $redes;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($modo === 'crear' ? 'Nueva' : 'Editar'); ?> Publicación - <?php echo $lineaNombre; ?></title>
    <link rel="icon" type="image/png" href="assets/images/logos/isotipo-ebone.png">
    <!-- Google Fonts - Open Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .app-simple {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .header-simple {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .nav-simple {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .nav-simple a {
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
        }
        .nav-simple a.active {
            background-color: #23AAC5;
            color: white;
        }
        .form-section {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            padding: 25px;
            border: 1px solid #eaeaea;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-column {
            flex: 1;
            min-width: 250px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group:last-child {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #23AAC5;
            box-shadow: 0 0 0 3px rgba(35, 170, 197, 0.15);
            outline: none;
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .redes-container {
            margin-bottom: 0;
        }
        .redes-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        .redes-checkboxes .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: #f8f9fa;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        .redes-checkboxes .checkbox-item:hover {
            background-color: #e9ecef;
        }
        .redes-checkboxes .checkbox-item input:checked + label {
            font-weight: 500;
            color: #23AAC5;
        }
        .preview-container {
            margin-top: 15px;
            max-width: 100%;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .preview-container p {
            margin-top: 0;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        .preview-container img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            object-fit: contain;
        }
        .required {
            color: #dc3545;
            font-weight: bold;
        }
        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #23AAC5;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1d8fa6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(35, 170, 197, 0.2);
        }
        .btn-secondary {
            background-color: #e9ecef;
            color: #495057;
        }
        .btn-secondary:hover {
            background-color: #dee2e6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .form-column {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="app-simple">
        <div class="header-simple">
            <h1><?php echo ($modo === 'crear' ? 'Nueva' : 'Editar'); ?> Publicación - <?php echo $lineaNombre; ?></h1>
        </div>
        
        <div class="nav-simple">
            <a href="index.php">Dashboard</a>
            <a href="ebone.php" <?php if($lineaId == 1) echo 'class="active"'; ?>>Ebone Servicios</a>
            <a href="cubofit.php" <?php if($lineaId == 2) echo 'class="active"'; ?>>CUBOFIT</a>
            <a href="uniges.php" <?php if($lineaId == 3) echo 'class="active"'; ?>>Uniges-3</a>
            <a href="teia.php" <?php if($lineaId == 4) echo 'class="active"'; ?>>Teiá</a>
        </div>
        
        <?php if (!empty($errores)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <strong>Por favor corrija los siguientes errores:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="contenido">Contenido: <span class="required">*</span></label>
                    <textarea id="contenido" name="contenido" class="form-control" rows="4" required><?php echo htmlspecialchars($publicacion['contenido']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="imagen">Imagen:</label>
                            <input type="file" id="imagen" name="imagen" class="form-control" accept="image/*">
                            
                            <?php if (!empty($publicacion['imagen_url'])): ?>
                                <div class="preview-container">
                                    <p>Imagen actual:</p>
                                    <img src="<?php echo $publicacion['imagen_url']; ?>" alt="Vista previa" id="preview-actual">
                                </div>
                            <?php endif; ?>
                            
                            <div class="preview-container" id="preview-container" style="display: none;">
                                <p>Vista previa:</p>
                                <img src="" alt="Vista previa" id="preview-img">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-column">
                        <div class="form-group">
                            <label for="fecha">Fecha programada: <span class="required">*</span></label>
                            <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo $publicacion['fecha_programada']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="estado">Estado: <span class="required">*</span></label>
                            <select id="estado" name="estado" class="form-control" required>
                                <option value="borrador" <?php if ($publicacion['estado'] === 'borrador') echo 'selected'; ?>>Borrador</option>
                                <option value="programado" <?php if ($publicacion['estado'] === 'programado') echo 'selected'; ?>>Programado</option>
                                <option value="publicado" <?php if ($publicacion['estado'] === 'publicado') echo 'selected'; ?>>Publicado</option>
                            </select>
                        </div>
                        
                        <div class="form-group redes-container">
                            <label>Redes sociales: <span class="required">*</span></label>
                            <div class="redes-checkboxes">
                                <?php foreach ($redesDisponibles as $red): 
                                    $checked = in_array($red['id'], $redesSeleccionadas) ? 'checked' : '';
                                    
                                    // Determinar el ícono según el nombre de la red
                                    $icon = '';
                                    switch (strtolower($red['nombre'])) {
                                        case 'instagram':
                                            $icon = '<i class="fab fa-instagram"></i>';
                                            break;
                                        case 'facebook':
                                            $icon = '<i class="fab fa-facebook-f"></i>';
                                            break;
                                        case 'twitter':
                                        case 'twitter (x)':
                                            $icon = '<i class="fab fa-twitter"></i>';
                                            break;
                                        case 'linkedin':
                                            $icon = '<i class="fab fa-linkedin-in"></i>';
                                            break;
                                        default:
                                            $icon = '<i class="fas fa-share-alt"></i>';
                                    }
                                ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="red_<?php echo $red['id']; ?>" name="redes[]" value="<?php echo $red['id']; ?>" <?php echo $checked; ?>>
                                    <label for="red_<?php echo $red['id']; ?>"><?php echo $icon; ?> <?php echo $red['nombre']; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group buttons">
                    <a href="<?php echo $paginaRetorno; ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Publicación</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Script para vista previa de imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.getElementById('preview-container');
                    const previewImg = document.getElementById('preview-img');
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 