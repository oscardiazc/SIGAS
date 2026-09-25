<?php
// HISTORIAL GENERAL DE ATENCIONES
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

$filtroBox = $_GET['box_id'] ?? '';
$filtroRut = trim($_GET['rut'] ?? '');
$filtroDesde = $_GET['desde'] ?? date('Y-m-01');
$filtroHasta = $_GET['hasta'] ?? date('Y-m-d');

$sql = "SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.es_sobrecupo, c.box_id,
               p.rut, p.nombre, p.apellidos, p.telefono, p.correo, p.seccion_unidad, p.prevision,
               t.nombre AS tipo_atencion, t.color_hex
        FROM citas c
        INNER JOIN pacientes p ON c.paciente_id = p.id
        INNER JOIN tipos_atencion t ON c.tipo_atencion_id = t.id
        WHERE c.fecha BETWEEN :desde AND :hasta";

$params = [
    ':desde' => $filtroDesde,
    ':hasta' => $filtroHasta
];

if (!empty($filtroBox)) {
    $sql .= " AND c.box_id = :box_id";
    $params[':box_id'] = $filtroBox;
}

if (!empty($filtroRut)) {
    $sql .= " AND p.rut LIKE :rut";
    $params[':rut'] = "%$filtroRut%";
}

$sql .= " ORDER BY c.fecha DESC, c.hora_inicio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$historial = $stmt->fetchAll();
?>

<div class="panel-grilla" style="padding: 20px; overflow-y: auto; height: calc(100vh - 100px);">
    <div class="grilla-header-info">
        <h2>Historial de Atenciones Agendadas</h2>
    </div>

    <!-- BARRA DE FILTROS -->
    <form method="GET" style="display: flex; gap: 12px; align-items: flex-end; background: #edf2f7; padding: 12px; border-radius: 6px; margin-top: 10px; margin-bottom: 15px; flex-wrap: wrap;">
        <div>
            <label style="display:block; font-size: 11px; font-weight: bold; margin-bottom: 3px;">Desde:</label>
            <input type="date" name="desde" value="<?= htmlspecialchars($filtroDesde) ?>" style="padding: 6px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 12px;">
        </div>
        <div>
            <label style="display:block; font-size: 11px; font-weight: bold; margin-bottom: 3px;">Hasta:</label>
            <input type="date" name="hasta" value="<?= htmlspecialchars($filtroHasta) ?>" style="padding: 6px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 12px;">
        </div>
        <div>
            <label style="display:block; font-size: 11px; font-weight: bold; margin-bottom: 3px;">Box:</label>
            <select name="box_id" style="padding: 6px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 12px;">
                <option value="">Todos los Boxes</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= $filtroBox == $i ? 'selected' : '' ?>>Box <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-size: 11px; font-weight: bold; margin-bottom: 3px;">RUT Paciente:</label>
            <input type="text" name="rut" placeholder="Ej: 12345678-9" value="<?= htmlspecialchars($filtroRut) ?>" style="padding: 6px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 12px;">
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 7px 14px;">Filtrar</button>
            <a href="historial.php" class="btn btn-light" style="padding: 7px 14px;">Limpiar</a>
        </div>
    </form>

    <table style="width: 100%; border-collapse: collapse; background: #ffffff; font-size: 13px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead>
            <tr style="background-color: #1a365d; color: #ffffff; text-align: left;">
                <th style="padding: 10px; border: 1px solid #cbd5e0; width: 100px;">Fecha</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0; width: 90px;">Horario</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0; width: 70px;">Box</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0;">Paciente</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0;">Tipo Atención</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0;">Unidad / Previsión</th>
                <th style="padding: 10px; border: 1px solid #cbd5e0; text-align: center; width: 90px;">Condición</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($historial)): ?>
                <tr>
                    <td colspan="7" style="padding: 15px; text-align: center; color: #718096;">No se encontraron atenciones registradas con los filtros seleccionados.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($historial as $row): ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px; border: 1px solid #cbd5e0;"><?= date('d/m/Y', strtotime($row['fecha'])) ?></td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0; font-weight: bold;">
                            <?= substr($row['hora_inicio'], 0, 5) ?> - <?= substr($row['hora_fin'], 0, 5) ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0; text-align: center; font-weight: bold;">Box <?= $row['box_id'] ?></td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0;">
                            <strong><?= htmlspecialchars($row['nombre'] . ' ' . $row['apellidos']) ?></strong><br>
                            <small style="color: #718096;">RUT: <?= htmlspecialchars($row['rut']) ?> | Tel: <?= htmlspecialchars($row['telefono']) ?></small>
                        </td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0;">
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background-color: <?= $row['color_hex'] ?>; margin-right: 5px;"></span>
                            <?= htmlspecialchars($row['tipo_atencion']) ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0;">
                            <?= htmlspecialchars($row['seccion_unidad']) ?> (<?= htmlspecialchars($row['prevision']) ?>)
                        </td>
                        <td style="padding: 10px; border: 1px solid #cbd5e0; text-align: center;">
                            <?php if ($row['es_sobrecupo']): ?>
                                <span style="background: #fed7d7; color: #9b2c2c; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;">Sobrecupo</span>
                            <?php else: ?>
                                <span style="background: #c6f6d5; color: #22543d; padding: 2px 6px; border-radius: 3px; font-size: 11px;">Normal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>