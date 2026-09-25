<?php
require_once '../conexion.php';
header('Content-Type: application/json; charset=utf-8');

$rutIngresado = trim($_GET['rut'] ?? '');

if (empty($rutIngresado)) {
    echo json_encode(['encontrado' => false]);
    exit;
}

// Limpiar formato para comparar el RUT en limpio (ej: 123456789 o 12345678K)
$rutLimpio = strtoupper(str_replace(['.', '-', ' '], '', $rutIngresado));

// Buscar comparando campos limpios y excluyendo reservas de sala internas
$stmt = $pdo->prepare("SELECT * FROM pacientes 
                       WHERE REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ? 
                         AND rut NOT LIKE 'SALA-%'");
$stmt->execute([$rutLimpio]);
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($paciente) {
    echo json_encode(['encontrado' => true, 'paciente' => $paciente], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['encontrado' => false]);
}
exit;