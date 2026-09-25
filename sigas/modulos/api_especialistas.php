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
$inputJson = json_decode(file_get_contents('php://input'), true);

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

// =========================================================================
// ACCIÓN 2: ELIMINAR ESPECIALISTA CON CONFIRMACIÓN DE CLAVE (POST)
// =========================================================================
if ($accion === 'eliminar') {
    $esp_id = (int)($inputJson['especialista_id'] ?? ($_POST['especialista_id'] ?? 0));
    $password = trim($inputJson['password'] ?? ($_POST['password'] ?? ''));

    if (!$esp_id || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Debe ingresar su contraseña de administrador.']);
        exit;
    }

    // Reautenticar clave
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
// ACCIÓN 3: CREAR O EDITAR ESPECIALISTA (POST)
// =========================================================================
if ($accion === 'guardar' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : (!empty($inputJson['id']) ? (int)$inputJson['id'] : null);
    $nombre = trim($_POST['nombre'] ?? ($inputJson['nombre'] ?? ''));
    $rutVisual = trim($_POST['rut'] ?? ($inputJson['rut'] ?? ''));
    $cargo_titulo = trim($_POST['cargo_titulo'] ?? ($inputJson['cargo_titulo'] ?? ''));
    $atenciones = $_POST['atenciones'] ?? ($inputJson['atenciones'] ?? []);
    
    // Soporte para ambos nombres de base64
    $fotoBase64 = trim($_POST['foto_recortada'] ?? ($_POST['foto_base64'] ?? ($inputJson['foto_recortada'] ?? ($inputJson['foto_base64'] ?? ''))));

    // Normalizar RUT limpio
    $rut = strtoupper(str_replace(['.', '-', ' '], '', $rutVisual));

    if (empty($nombre) || empty($rut) || empty($cargo_titulo)) {
        echo json_encode(['success' => false, 'message' => 'Nombre, RUT y Cargo son campos obligatorios.']);
        exit;
    }

    if (empty($atenciones)) {
        echo json_encode(['success' => false, 'message' => 'Debe asignar al menos un tipo de consulta.']);
        exit;
    }

    // Procesar Foto de Perfil
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
            // ACTUALIZACIÓN / EDICIÓN
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
            // CREACIÓN
            $stmt = $pdo->prepare("INSERT INTO especialistas (nombre, rut, cargo_titulo, foto_perfil, activo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$nombre, $rut, $cargo_titulo, $rutaFoto]);
            $especialista_id = $pdo->lastInsertId();

            registrar_auditoria($pdo, $admin_id_sesion, 'CREAR_ESP', "Registró especialista: $nombre ($rut)");
        }

        // Registrar atenciones asignadas
        $stmtPivot = $pdo->prepare("INSERT INTO tipo_atencion_especialistas (tipo_atencion_id, especialista_id) VALUES (?, ?)");
        foreach ($atenciones as $tipo_id) {
            $stmtPivot->execute([(int)$tipo_id, $especialista_id]);
        }

        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => $id ? 'Especialista actualizado con éxito.' : 'Especialista registrado con éxito.'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no especificada.']);
exit;