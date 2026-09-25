<?php
// HISTORIAL DE AUDITORIA DE ADMINISTRADORES
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

// FILTRO OPCIONAL POR ACCION
$filtroAccion = $_GET['accion'] ?? '';

$sql = "SELECT a.id, a.accion, a.detalle, a.fecha_hora,
               adm.nombre AS admin_nombre, adm.rut AS admin_rut
        FROM auditoria_administradores a
        INNER JOIN administradores adm ON a.administrador_id = adm.id";

if (!empty($filtroAccion)) {
    $sql .= " WHERE a.accion = :accion";
}

$sql .= " ORDER BY a.fecha_hora DESC LIMIT 150";

$stmt = $pdo->prepare($sql);
if (!empty($filtroAccion)) {
    $stmt->execute([':accion' => $filtroAccion]);
} else {
    $stmt->execute();
}
$logs = $stmt->fetchAll();
?>

<div class="panel-grilla" style="padding: 20px; overflow-y: auto; height: calc(100vh - 100px);">
    <div class="grilla-header-info">
        <h2>Historial de Auditoría de Administradores</h2>
        
        <!-- FILTROS RAPIDOS -->
        <form method="GET" style="display: flex; gap: 10px; align-items: center;">
            <label for="accion" style="font-size: 13px; font-weight: bold;">Filtrar Acción:</label>
            <select name="accion" id="accion" onchange="this.form.submit()" style="padding: 5px 8px; border-radius: 4px; border: 1px solid #cbd5e0; font-size: 13px;">
                <option value="">Todas las acciones</option>
                <option value="AGENDAR" <?= $filtroAccion === 'AGENDAR' ? 'selected' : '' ?>>Agendar Cita</option>
                <option value="ELIMINAR" <?= $filtroAccion === 'ELIMINAR' ? 'selected' : '' ?>>Eliminar Cita</option>
                <option value="DERIVACION" <?= $filtroAccion === 'DERIVACION' ? 'selected' : '' ?>>Derivación Mamografía</option>
                <option value="MAMOGRAFIA" <?= $filtroAccion === 'MAMOGRAFIA' ? 'selected' : '' ?>>Cambio Estado Mamografía</option>
            </select>
            <?php if (!empty($filtroAccion)): ?>
                <a href="auditoria.php" class="btn btn-light" style="padding: 5px 10px; font-size: 12px;">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; background: #ffffff; font-size: 13px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead>
            <tr style="background-color: #1a365d; color: #ffffff; text-align: left;">
                <th style="padding: 10px; border: 1px solid #cbd5e0; width: 160px;">Fecha y Hora</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0; width: 220px;">Administrador</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0; width: 140px;">Acción</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0;">Detalle de la Operación</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="4" style="padding: 15px; text-align: center; color: #718096;">No hay registros de auditoría disponibles.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px; border: 1px solid #cbd5e0;"><?= date('d/m/Y H:i:s', strtotime($log['fecha_hora'])) ?></td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0;">
                            <strong><?= htmlspecialchars($log['admin_nombre']) ?></strong><br>
                            <small style="color: #718096;">RUT: <?= htmlspecialchars($log['admin_rut']) ?></small>
                        </td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0;">
                            <?php
                                $colorBadge = '#4a5568';
                                if ($log['accion'] === 'AGENDAR') $colorBadge = '#2b6cb0';
                                if ($log['accion'] === 'ELIMINAR') $colorBadge = '#c53030';
                                if ($log['accion'] === 'MAMOGRAFIA') $colorBadge = '#9c27b0';
                                if ($log['accion'] === 'DERIVACION') $colorBadge = '#d69e2e';
                            ?>
                            <span style="background: <?= $colorBadge ?>; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                                <?= htmlspecialchars($log['accion']) ?>
                            </span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0;"><?= htmlspecialchars($log['detalle']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>