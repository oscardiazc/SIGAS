<?php
require_once '../conexion.php';
header('Content-Type: application/json');

$rut = trim($_GET['rut'] ?? '');

if (empty($rut)) {
    echo json_encode(['encontrado' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pacientes WHERE rut = ? LIMIT 1");
$stmt->execute([$rut]);
$paciente = $stmt->fetch();

if ($paciente) {
    echo json_encode([
        'encontrado' => true,
        'paciente' => $paciente
    ]);
} else {
    echo json_encode(['encontrado' => false]);
}
?>