<?php
// PROCESAMIENTO DE LOGIN ADMINISTRADOR
require_once '../conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rutIngresado = trim($_POST['rut'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Despojar cualquier formato visual: 20.375.935-5 -> 203759355
    $rutLimpio = strtoupper(str_replace(['.', '-', ' '], '', $rutIngresado));

    if (empty($rutLimpio) || empty($password)) {
        header('Location: ../index.php?error=1');
        exit;
    }

    // Busqueda flexible: limpia en tiempo de ejecucion el campo de la BD para comparar RUT puro
    $stmt = $pdo->prepare("SELECT id, rut, nombre, password_hash FROM administradores 
                           WHERE REPLACE(REPLACE(REPLACE(rut, '.', ''), '-', ''), ' ', '') = ?");
    $stmt->execute([$rutLimpio]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        $_SESSION['admin_rut'] = $admin['rut'];
        header('Location: ../dashboard.php');
        exit;
    } else {
        header('Location: ../index.php?error=1');
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
?>