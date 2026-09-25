<?php
require_once '../conexion.php';
header('Content-Type: application/json');

$tipo_atencion_id = (int)($_GET['tipo_atencion_id'] ?? 0);
$fecha = $_GET['fecha'] ?? '';
$es_sobrecupo = (!empty($_GET['es_sobrecupo']) && ($_GET['es_sobrecupo'] === 'true' || $_GET['es_sobrecupo'] === '1'));

if (!$tipo_atencion_id || empty($fecha)) {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos.']);
    exit;
}

// 1. Obtener datos del tipo de atención y su Box
$stmtTipo = $pdo->prepare("SELECT t.*, b.nombre as nombre_box FROM tipos_atencion t 
                           INNER JOIN boxes b ON t.box_id = b.id 
                           WHERE t.id = ?");
$stmtTipo->execute([$tipo_atencion_id]);
$tipo = $stmtTipo->fetch();

if (!$tipo) {
    echo json_encode(['success' => false, 'message' => 'Tipo de atención no encontrado.']);
    exit;
}

$box_id = (int)$tipo['box_id'];
$duracion = (int)$tipo['duracion_minutos'];

// 2. Obtener especialistas habilitados
$stmtEsp = $pdo->prepare("SELECT e.id, e.nombre, e.cargo_titulo FROM especialistas e 
                          INNER JOIN tipo_atencion_especialistas te ON e.id = te.especialista_id 
                          WHERE te.tipo_atencion_id = ? AND e.activo = 1");
$stmtEsp->execute([$tipo_atencion_id]);
$especialistas = $stmtEsp->fetchAll();

// 3. Consultar citas ocupadas en este Box en la fecha elegida
$stmtCitas = $pdo->prepare("SELECT hora_inicio, hora_fin FROM citas 
                           WHERE box_id = ? AND fecha = ? AND estado = 'confirmada' AND es_sobrecupo = 0 
                           ORDER BY hora_inicio ASC");
$stmtCitas->execute([$box_id, $fecha]);
$ocupadas = $stmtCitas->fetchAll();

// 4. Franjas horarias: Mañana (08:00 a 13:00) y Tarde (14:00 a 18:00)
$bloquesDisponibles = [];
$jornadas = [
    ['inicio' => strtotime("$fecha 08:00:00"), 'fin' => strtotime("$fecha 13:00:00")],
    ['inicio' => strtotime("$fecha 14:00:00"), 'fin' => strtotime("$fecha 18:00:00")]
];

foreach ($jornadas as $jornada) {
    $cursor = $jornada['inicio'];
    while ($cursor + ($duracion * 60) <= $jornada['fin']) {
        $iniStr = date('H:i:s', $cursor);
        $finStr = date('H:i:s', $cursor + ($duracion * 60));

        $colision = false;
        foreach ($ocupadas as $c) {
            if ($iniStr < $c['hora_fin'] && $finStr > $c['hora_inicio']) {
                $colision = true;
                break;
            }
        }

        // Si NO colisiona, se entrega normalmente
        if (!$colision) {
            $bloquesDisponibles[] = [
                'inicio' => substr($iniStr, 0, 5),
                'fin' => substr($finStr, 0, 5),
                'ocupado' => false
            ];
        } elseif ($es_sobrecupo) {
            // Si colisiona pero es sobrecupo, SE MUESTRA IGUALMENTE marcado como ocupado
            $bloquesDisponibles[] = [
                'inicio' => substr($iniStr, 0, 5),
                'fin' => substr($finStr, 0, 5),
                'ocupado' => true
            ];
        }

        $cursor += ($duracion * 60);
    }
}

echo json_encode([
    'success' => true,
    'box_id' => $box_id,
    'nombre_box' => $tipo['nombre_box'],
    'duracion' => $duracion,
    'especialistas' => $especialistas,
    'bloques' => $bloquesDisponibles
]);