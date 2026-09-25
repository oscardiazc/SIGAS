<?php
require_once '../conexion.php';
header('Content-Type: application/json');

$box_id = (int)($_GET['box_id'] ?? 0);
$fecha = $_GET['fecha'] ?? '';
$duracion_horas = (int)($_GET['horas'] ?? 1);

if (!$box_id || empty($fecha) || $duracion_horas < 1) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

$duracion_minutos = $duracion_horas * 60;

// Citas existentes en ese box para la fecha dada
$stmt = $pdo->prepare("SELECT hora_inicio, hora_fin FROM citas 
                       WHERE box_id = ? AND fecha = ? AND estado = 'confirmada' AND es_sobrecupo = 0 
                       ORDER BY hora_inicio ASC");
$stmt->execute([$box_id, $fecha]);
$ocupadas = $stmt->fetchAll();

// Franjas de operación: 08:00 a 13:00 y 14:00 a 18:00
$jornadas = [
    ['inicio' => strtotime("$fecha 08:00:00"), 'fin' => strtotime("$fecha 13:00:00")],
    ['inicio' => strtotime("$fecha 14:00:00"), 'fin' => strtotime("$fecha 18:00:00")]
];

$bloquesLibres = [];

foreach ($jornadas as $jornada) {
    $cursor = $jornada['inicio'];
    // Salta de hora en hora buscando huecos que abarquen la duración total solicitada
    while ($cursor + ($duracion_minutos * 60) <= $jornada['fin']) {
        $iniStr = date('H:i:s', $cursor);
        $finStr = date('H:i:s', $cursor + ($duracion_minutos * 60));

        $colision = false;
        foreach ($ocupadas as $c) {
            if ($iniStr < $c['hora_fin'] && $finStr > $c['hora_inicio']) {
                $colision = true;
                break;
            }
        }

        if (!$colision) {
            $bloquesLibres[] = [
                'inicio' => substr($iniStr, 0, 5),
                'fin' => substr($finStr, 0, 5)
            ];
        }
        $cursor += 3600; // Paso de 1 hora
    }
}

echo json_encode([
    'success' => true,
    'bloques' => $bloquesLibres
]);