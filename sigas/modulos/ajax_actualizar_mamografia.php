<?php
// ACTUALIZACION ASINCRONA DEL ESTADO DE MAMOGRAFIA
require_once '../conexion.php';
require_once 'auditoria.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    echo json_encode(['success' => false, 'message' => 'Sesion expirada.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$estado = trim($input['estado'] ?? '');

$estadosValidos = ['lista de espera', 'solicitada', 'agendada'];
if (!in_array($estado, $estadosValidos)) {
    echo json_encode(['success' => false, 'message' => 'Estado no valido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE mamografias SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    registrar_auditoria($pdo, $admin_id, 'MAMOGRAFIA', "Cambio de estado a '$estado' en derivacion ID $id");

    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
}
?>