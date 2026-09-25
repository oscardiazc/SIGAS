<?php
// VISTA MENSUAL POR BOX ESPECIFICO
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

$box_id = (int)($_GET['box'] ?? 1);
if ($box_id < 1 || $box_id > 5) $box_id = 1;

// CONTROL DE MES Y ANIO
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// CALCULO DE DIAS Y LIMITES
$primerDiaMes = mktime(0, 0, 0, $mes, 1, $anio);
$totalDiasMes = (int)date('t', $primerDiaMes);
$diaInicioSemana = (int)date('N', $primerDiaMes); // 1 = Lunes, 7 = Domingo

// NAVEGACION ANTERIOR / SIGUIENTE
$mesAnterior = $mes - 1;
$anioAnterior = $anio;
if ($mesAnterior < 1) {
    $mesAnterior = 12;
    $anioAnterior--;
}

$mesSiguiente = $mes + 1;
$anioSiguiente = $anio;
if ($mesSiguiente > 12) {
    $mesSiguiente = 1;
    $anioSiguiente++;
}

// OBTENER TODAS LAS CITAS DEL MES PARA ESTE BOX
$fechaInicioBusqueda = sprintf('%04d-%02d-01', $anio, $mes);
$fechaFinBusqueda = sprintf('%04d-%02d-%02d', $anio, $mes, $totalDiasMes);

$sql = "SELECT c.id, c.fecha, c.hora_inicio, c.es_sobrecupo,
               p.nombre, p.apellidos, t.color_hex, t.nombre AS tipo_atencion
        FROM citas c
        INNER JOIN pacientes p ON c.paciente_id = p.id
        INNER JOIN tipos_atencion t ON c.tipo_atencion_id = t.id
        WHERE c.box_id = ? 
          AND c.fecha BETWEEN ? AND ?
          AND c.estado = 'confirmada'
        ORDER BY c.hora_inicio ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$box_id, $fechaInicioBusqueda, $fechaFinBusqueda]);
$todasCitas = $stmt->fetchAll();

// AGRUPAR CITAS POR DIA
$citasPorDia = [];
foreach ($todasCitas as $c) {
    $diaNum = (int)date('j', strtotime($c['fecha']));
    $citasPorDia[$diaNum][] = $c;
}

$mesesNombres = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>

<div class="panel-grilla" style="padding: 15px 20px; height: calc(100vh - 100px); display: flex; flex-direction: column;">
    <div class="grilla-header-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <h2>Agenda Mensual - Box <?= $box_id ?></h2>
            <span style="font-size: 15px; font-weight: bold; color: #2d3748;">
                <?= $mesesNombres[$mes] ?> <?= $anio ?>
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="box_mensual.php?box=<?= $box_id ?>&mes=<?= $mesAnterior ?>&anio=<?= $anioAnterior ?>" class="btn btn-light">&larr; Mes Anterior</a>
            <a href="box_mensual.php?box=<?= $box_id ?>&mes=<?= date('m') ?>&anio=<?= date('Y') ?>" class="btn btn-light">Mes Actual</a>
            <a href="box_mensual.php?box=<?= $box_id ?>&mes=<?= $mesSiguiente ?>&anio=<?= $anioSiguiente ?>" class="btn btn-light">Mes Siguiente &rarr;</a>
            <a href="box_semanal.php?box=<?= $box_id ?>" class="btn btn-secondary">Vista Semanal</a>
        </div>
    </div>

    <!-- GRILLA CALENDARIO MENSUAL -->
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); background: #2d3748; color: #fff; text-align: center; font-size: 12px; font-weight: bold; border-radius: 4px 4px 0 0; padding: 6px 0;">
        <div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div><div>Dom</div>
    </div>

    <div style="flex: 1; display: grid; grid-template-columns: repeat(7, 1fr); grid-auto-rows: 1fr; gap: 4px; background: #cbd5e0; padding: 4px; overflow-y: auto;">
        <?php
        // Celdas vacias antes del inicio del mes
        for ($v = 1; $v < $diaInicioSemana; $v++): ?>
            <div style="background: #edf2f7; opacity: 0.5;"></div>
        <?php endfor; ?>

        <?php for ($dia = 1; $dia <= $totalDiasMes; $dia++): 
            $esHoy = ($dia == (int)date('d') && $mes == (int)date('m') && $anio == (int)date('Y'));
            $citasDelDia = $citasPorDia[$dia] ?? [];
        ?>
            <div style="background: #fff; padding: 5px; border-radius: 3px; display: flex; flex-direction: column; overflow: hidden; <?= $esHoy ? 'border: 2px solid #2b6cb0;' : '' ?>">
                <div style="font-weight: bold; font-size: 11px; margin-bottom: 4px; color: <?= $esHoy ? '#2b6cb0' : '#4a5568' ?>;">
                    <?= $dia ?> <?= $esHoy ? '(Hoy)' : '' ?>
                </div>
                <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 3px;">
                    <?php foreach ($citasDelDia as $cita): ?>
                        <div style="background-color: <?= $cita['color_hex'] ?>; color: #fff; font-size: 10px; padding: 2px 4px; border-radius: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; <?= $cita['es_sobrecupo'] ? 'border: 1px dashed red;' : '' ?>" 
                             title="<?= $cita['hora_inicio'] ?> - <?= htmlspecialchars($cita['nombre'] . ' ' . $cita['apellidos']) ?> (<?= $cita['tipo_atencion'] ?>)">
                            <?= substr($cita['hora_inicio'], 0, 5) ?> <?= htmlspecialchars($cita['nombre']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <div class="leyenda-atenciones">
        <span class="leyenda-item"><span class="color-box" style="background:#FF9800;"></span> Certificado</span>
        <span class="leyenda-item"><span class="color-box" style="background:#2196F3;"></span> Morbilidad</span>
        <span class="leyenda-item"><span class="color-box" style="background:#4CAF50;"></span> EMP</span>
        <span class="leyenda-item"><span class="color-box" style="background:#9C27B0;"></span> Salud Mental USIT</span>
    </div>
</div>

<?php include '../includes/footer.php'; ?>