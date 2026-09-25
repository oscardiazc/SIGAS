<?php
// REGISTRO CENTRALIZADO DE AUDITORIA DE ADMINISTRADORES

function registrar_auditoria($pdo, $admin_id, $accion, $detalle) {
    try {
        $stmt = $pdo->prepare("INSERT INTO auditoria_administradores (administrador_id, accion, detalle) VALUES (?, ?, ?)");
        $stmt->execute([$admin_id, $accion, $detalle]);
    } catch (PDOException $e) {
        error_log("Error en auditoria: " . $e->getMessage());
    }
}
?>