<?php
// CONTROLADOR INTEGRAL DE ESPECIALISTAS (GUARDAR, EDITAR, ELIMINAR, ATENCIONES)
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once 'auditoria.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$admin_id_sesion = $_SESSION['admin_id'] ?? null;
if (!$admin_id_sesion) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada o no autorizada.']);
    exit;
}

$accion = $_GET['accion'] ?? '';

// =========================================================================
// ACCIÓN 1: OBTENER ATENCIONES ASIGNADAS AL ESPECIALISTA (GET)
// =========================================================================
if ($accion === 'atenciones') {
    $esp_id = (int)($_GET['especialista_id'] ?? 0);
    if (!$esp_id) {
        echo json_encode([]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT tipo_atencion_id FROM tipo_atencion_especialistas WHERE especialista_id = ?");
    $stmt->execute([$esp_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;
}

// Para guardar y eliminar recibimos JSON o POST
$input = json_decode(file_get_contents('php://input'), true);

// =========================================================================
// ACCIÓN 2: ELIMINAR ESPECIALISTA CON CONFIRMACIÓN DE CLAVE (POST)
// =========================================================================
if ($accion === 'eliminar') {
    $esp_id = (int)($input['especialista_id'] ?? 0);
    $password = trim($input['password'] ?? '');

    if (!$esp_id || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Debe ingresar su contraseña de administrador.']);
        exit;
    }

    // 1. Reautenticar clave del administrador en sesión
    $stmtAuth = $pdo->prepare("SELECT password_hash FROM administradores WHERE id = ?");
    $stmtAuth->execute([$admin_id_sesion]);
    $admin = $stmtAuth->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta. Operación cancelada.']);
        exit;
    }

    try {
        $stmtEsp = $pdo->prepare("SELECT nombre, rut FROM especialistas WHERE id = ?");
        $stmtEsp->execute([$esp_id]);
        $esp = $stmtEsp->fetch();

        if (!$esp) {
            echo json_encode(['success' => false, 'message' => 'Especialista no encontrado.']);
            exit;
        }

        // Eliminación física (las llaves en cascada limpian las atenciones asociadas)
        $stmtDel = $pdo->prepare("DELETE FROM especialistas WHERE id = ?");
        $stmtDel->execute([$esp_id]);

        registrar_auditoria($pdo, $admin_id_sesion, 'ELIMINAR_ESP', "Eliminó al especialista: {$esp['nombre']} ({$esp['rut']})");

        echo json_encode(['success' => true, 'message' => 'Especialista eliminado con éxito.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// ACCIÓN 3: CREAR O EDITAR ESPECIALISTA CON FOTO / CANVAS BASE64 (POST)
// =========================================================================
if ($accion === 'guardar') {
    // Si viene por FormData tradicional o JSON
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : (!empty($input['id']) ? (int)$input['id'] : null);
    $nombre = trim($_POST['nombre'] ?? ($input['nombre'] ?? ''));
    $rutVisual = trim($_POST['rut'] ?? ($input['rut'] ?? ''));
    $cargo_titulo = trim($_POST['cargo_titulo'] ?? ($input['cargo_titulo'] ?? ''));
    $atenciones = $_POST['atenciones'] ?? ($input['atenciones'] ?? []);
    $fotoBase64 = $_POST['foto_base64'] ?? ($input['foto_base64'] ?? null);

    // Normalizar RUT limpio
    $rut = strtoupper(str_replace(['.', '-', ' '], '', $rutVisual));

    if (empty($nombre) || empty($rut) || empty($cargo_titulo) || empty($atenciones)) {
        echo json_encode(['success' => false, 'message' => 'Complete todos los campos obligatorios y asigne al menos una consulta.']);
        exit;
    }

    // Procesar imagen (por Base64 de lienzo circular o por archivo $_FILES)
    $rutaFoto = null;
    $carpetaDestino = '../assets/img/perfiles/';
    if (!is_dir($carpetaDestino)) {
        @mkdir($carpetaDestino, 0777, true);
    }

    if (!empty($fotoBase64) && strpos($fotoBase64, 'data:image') === 0) {
        $partes = explode(',', $fotoBase64);
        $dataImg = base64_decode($partes[1] ?? '');
        if ($dataImg) {
            $nombreImg = 'esp_' . time() . '_' . uniqid() . '.png';
            if (file_put_contents($carpetaDestino . $nombreImg, $dataImg)) {
                $rutaFoto = 'assets/img/perfiles/' . $nombreImg;
            }
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nombreImg = 'esp_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $carpetaDestino . $nombreImg)) {
                $rutaFoto = 'assets/img/perfiles/' . $nombreImg;
            }
        }
    }

    try {
        $pdo->beginTransaction();

        if ($id) {
            // Actualización
            if ($rutaFoto) {
                $stmt = $pdo->prepare("UPDATE especialistas SET nombre = ?, rut = ?, cargo_titulo = ?, foto_perfil = ? WHERE id = ?");
                $stmt->execute([$nombre, $rut, $cargo_titulo, $rutaFoto, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE especialistas SET nombre = ?, rut = ?, cargo_titulo = ? WHERE id = ?");
                $stmt->execute([$nombre, $rut, $cargo_titulo, $id]);
            }

            $stmtDel = $pdo->prepare("DELETE FROM tipo_atencion_especialistas WHERE especialista_id = ?");
            $stmtDel->execute([$id]);

            $especialista_id = $id;
            registrar_auditoria($pdo, $admin_id_sesion, 'EDITAR_ESP', "Modificó especialista ID $id: $nombre ($rut)");
        } else {
            // Inserción
            $stmt = $pdo->prepare("INSERT INTO especialistas (nombre, rut, cargo_titulo, foto_perfil, activo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nombre, $rut, $cargo_titulo, $rutaFoto]);
            $especialista_id = $pdo->lastInsertId();

            registrar_auditoria($pdo, $admin_id_sesion, 'CREAR_ESP', "Creó especialista: $nombre ($rut)");
        }

        // Asociar las consultas asignadas
        $stmtPivot = $pdo->prepare("INSERT INTO tipo_atencion_especialistas (tipo_atencion_id, especialista_id) VALUES (?, ?)");
        foreach ($atenciones as $tipo_id) {
            $stmtPivot->execute([(int)$tipo_id, $especialista_id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $id ? 'Especialista actualizado correctamente.' : 'Especialista registrado exitosamente.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no especificada o inválida.']);
exit;