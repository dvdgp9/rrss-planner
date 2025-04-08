<?php
// Componente para mostrar estadísticas básicas en el dashboard

// Obtener todas las líneas de negocio
$lineasNegocio = getAllLineasNegocio();

// Función auxiliar para contar publicaciones por estado para una línea
function countPublicacionesByEstado($lineaId, $estado) {
    $db = getDbConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM publicaciones 
        WHERE linea_negocio_id = ? AND estado = ?
    ");
    $stmt->execute([$lineaId, $estado]);
    $result = $stmt->fetch();
    return $result['total'];
}
?>

<div class="dashboard-stats">
    <?php foreach ($lineasNegocio as $linea): 
        $borradores = countPublicacionesByEstado($linea['id'], 'borrador');
        $programadas = countPublicacionesByEstado($linea['id'], 'programado');
        $publicadas = countPublicacionesByEstado($linea['id'], 'publicado');
        $total = $borradores + $programadas + $publicadas;
    ?>
    <div class="stat-card">
        <div class="stat-header">
            <h3><?php echo $linea['nombre']; ?></h3>
            <a href="index.php?linea=<?php echo $linea['id']; ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-external-link-alt"></i> Ver
            </a>
        </div>
        <div class="stat-body">
            <div class="stat-item">
                <span class="stat-label">Borradores</span>
                <span class="stat-value badge badge-draft"><?php echo $borradores; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Programadas</span>
                <span class="stat-value badge badge-scheduled"><?php echo $programadas; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Publicadas</span>
                <span class="stat-value badge badge-published"><?php echo $publicadas; ?></span>
            </div>
            <div class="stat-total">
                <span class="stat-label">Total</span>
                <span class="stat-value"><?php echo $total; ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.stat-card {
    background-color: var(--white);
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.stat-header {
    padding: 15px 20px;
    background-color: var(--primary-color);
    color: var(--white);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 500;
}

.stat-body {
    padding: 20px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--light-gray);
}

.stat-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    font-weight: 600;
}

.stat-label {
    color: var(--dark-gray);
}

.stat-value {
    font-weight: 500;
}
</style> 