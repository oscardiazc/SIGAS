<?php
// CONTROLADOR INTEGRAL DE ADMINISTRADORES (GUARDAR, EDITAR, ELIMINAR CON CLAVE)
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
// ACCIÓN 1: ELIMINAR ADMINISTRADOR CON PROTECCIÓN DE CLAVE
// =========================================================================
if ($accion === 'eliminar') {
    $admin_a_borrar_id = (int)($inputJson['admin_id'] ?? ($_POST['admin_id'] ?? 0));
    $password_confirmar = trim($inputJson['password'] ?? ($_POST['password'] ?? ''));

    if (!$admin_a_borrar_id || empty($password_confirmar)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos. Debe ingresar su contraseña.']);
        exit;
    }

    if ($admin_a_borrar_id === (int)$admin_id_sesion) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta en sesión activa.']);
        exit;
    }

    // Reautenticar al admin en sesión
    $stmtAuth = $pdo->prepare("SELECT password_hash FROM administradores WHERE id = ?");
    $stmtAuth->execute([$admin_id_sesion]);
    $adminActual = $stmtAuth->fetch();

    if (!$adminActual || !password_verify($password_confirmar, $adminActual['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta. Operación cancelada.']);
        exit;
    }

    try {
        $stmtTarget = $pdo->prepare("SELECT nombre, rut FROM administradores WHERE id = ?");
        $stmtTarget->execute([$admin_a_borrar_id]);
        $target = $stmtTarget->fetch();

        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'El administrador seleccionado no existe.']);
            exit;
        }

        $stmtDel = $pdo->prepare("DELETE FROM administradores WHERE id = ?");
        $stmtDel->execute([$admin_a_borrar_id]);

        registrar_auditoria($pdo, $admin_id_sesion, 'ELIMINAR_ADMIN', "Eliminó la cuenta de: {$target['nombre']} ({$target['rut']})");

        echo json_encode(['success' => true, 'message' => 'Administrador eliminado correctamente.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// ACCIÓN 2: GUARDAR O EDITAR ADMINISTRADOR (CREACIÓN / EDICIÓN)
// =========================================================================
if ($accion === 'guardar' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : (!empty($inputJson['id']) ? (int)$inputJson['id'] : null);
    $nombre = trim($_POST['nombre'] ?? ($inputJson['nombre'] ?? ''));
    $rutVisual = trim($_POST['rut'] ?? ($inputJson['rut'] ?? ''));
    $correo = trim($_POST['correo'] ?? ($inputJson['correo'] ?? ''));
    $password = trim($_POST['password'] ?? ($inputJson['password'] ?? ''));
    
    // Soporte para ambos nombres de campo de imagen recortada
    $fotoBase64 = trim($_POST['foto_recortada'] ?? ($_POST['foto_base64'] ?? ($inputJson['foto_recortada'] ?? ($inputJson['foto_base64'] ?? ''))));

    // Normalizar RUT limpio
    $rut = strtoupper(str_replace(['.', '-', ' '], '', $rutVisual));

    if (empty($nombre) || empty($rut)) {
        echo json_encode(['success' => false, 'message' => 'Nombre y RUT son campos requeridos.']);
        exit;
    }

    if (!$id && empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Debe asignar una contraseña para el nuevo administrador.']);
        exit;
    }

    // Procesar foto de perfil
    $rutaFoto = null;
    $carpetaDestino = '../assets/img/perfiles/';
    if (!is_dir($carpetaDestino)) {
        @mkdir($carpetaDestino, 0777, true);
    }

    if (!empty($fotoBase64) && strpos($fotoBase64, 'data:image') === 0) {
        $partes = explode(',', $fotoBase64);
        $dataImg = base64_decode($partes[1] ?? '');
        if ($dataImg) {
            $nombreImg = 'adm_' . time() . '_' . uniqid() . '.png';
            if (file_put_contents($carpetaDestino . $nombreImg, $dataImg)) {
                $rutaFoto = 'assets/img/perfiles/' . $nombreImg;
            }
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nombreImg = 'adm_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $carpetaDestino . $nombreImg)) {
                $rutaFoto = 'assets/img/perfiles/' . $nombreImg;
            }
        }
    }

    try {
        // Verificar si la columna correo existe en la tabla administradores
        $colCheck = $pdo->query("SHOW COLUMNS FROM administradores LIKE 'correo'");
        $tieneCorreo = ($colCheck && $colCheck->fetch());

        if ($id) {
            // MODO EDICIÓN
            $campos = ["nombre = ?", "rut = ?"];
            $params = [$nombre, $rut];

            if ($tieneCorreo) {
                $campos[] = "correo = ?";
                $params[] = $correo;
            }
            if (!empty($password)) {
                $campos[] = "password_hash = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($rutaFoto) {
                $campos[] = "foto_perfil = ?";
                $params[] = $rutaFoto;
            }

            $params[] = $id;
            $sql = "UPDATE administradores SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            registrar_auditoria($pdo, $admin_id_sesion, 'EDITAR_ADMIN', "Modificó al administrador ID $id: $nombre ($rut)");
            echo json_encode(['success' => true, 'message' => 'Administrador actualizado correctamente.']);
        } else {
            // MODO CREACIÓN
            $stmtCheck = $pdo->prepare("SELECT id FROM administradores WHERE rut = ?");
            $stmtCheck->execute([$rut]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ya existe un administrador con ese RUT.']);
                exit;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($tieneCorreo) {
                $stmt = $pdo->prepare("INSERT INTO administradores (rut, nombre, correo, password_hash, foto_perfil) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$rut, $nombre, $correo, $hash, $rutaFoto]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO administradores (rut, nombre, password_hash, foto_perfil) VALUES (?, ?, ?, ?)");
                $stmt->execute([$rut, $nombre, $hash, $rutaFoto]);
            }

            registrar_auditoria($pdo, $admin_id_sesion, 'CREAR_ADMIN', "Creó al administrador: $nombre ($rut)");
            echo json_encode(['success' => true, 'message' => 'Administrador creado con éxito.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
exit;