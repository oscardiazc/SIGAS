<?php
// CONTROLADOR UNIFICADO: PACIENTES, HISTORIAL CLINICO Y ELIMINACIÓN
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once 'auditoria.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$admin_id_sesion = $_SESSION['admin_id'] ?? null;
$paciente_id = (int)($_GET['paciente_id'] ?? 0);
$accion = $_GET['accion'] ?? ($paciente_id > 0 ? 'detalle' : 'listar');

// Procesar entrada POST/JSON si existe
$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['accion'])) {
    $accion = $input['accion'];
}

try {
    // -------------------------------------------------------------
    // ACCIÓN 1: ELIMINAR PACIENTE CON CONFIRMACIÓN DE CLAVE (POST)
    // -------------------------------------------------------------
    if ($accion === 'eliminar_paciente') {
        if (!$admin_id_sesion) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesión expirada o no autorizada.']);
            exit;
        }

        $idPacienteBorrar = (int)($input['paciente_id'] ?? 0);
        $passwordConfirmar = trim($input['password'] ?? '');

        if (!$idPacienteBorrar || empty($passwordConfirmar)) {
            echo json_encode(['success' => false, 'message' => 'Debe ingresar su contraseña de Administrador.']);
            exit;
        }

        // 1. Reautenticar contraseña del administrador en sesión
        $stmtAuth = $pdo->prepare("SELECT password_hash FROM administradores WHERE id = ?");
        $stmtAuth->execute([$admin_id_sesion]);
        $adminActual = $stmtAuth->fetch();

        if (!$adminActual || !password_verify($passwordConfirmar, $adminActual['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta. Operación cancelada.']);
            exit;
        }

        // 2. Verificar existencia del paciente
        $stmtTarget = $pdo->prepare("SELECT nombre, apellidos, rut FROM pacientes WHERE id = ?");
        $stmtTarget->execute([$idPacienteBorrar]);
        $target = $stmtTarget->fetch();

        if (!$target) {
            echo json_encode(['success' => false, 'message' => 'El paciente no existe o ya fue eliminado.']);
            exit;
        }

        // 3. Eliminar paciente (ON DELETE CASCADE eliminará citas y mamografías vinculadas)
        $stmtDel = $pdo->prepare("DELETE FROM pacientes WHERE id = ?");
        $stmtDel->execute([$idPacienteBorrar]);

        registrar_auditoria($pdo, $admin_id_sesion, 'ELIMINAR_PACIENTE', "Eliminó la ficha del paciente: {$target['nombre']} {$target['apellidos']} ({$target['rut']})");

        echo json_encode(['success' => true, 'message' => 'Paciente y su historial han sido eliminados correctamente.']);
        exit;
    }

    // -------------------------------------------------------------
    // ACCIÓN 2: DETALLE E HISTORIAL COMPLETO DE UN PACIENTE ESPECÍFICO
    // -------------------------------------------------------------
    if ($accion === 'detalle' || $paciente_id > 0) {
        if (!$paciente_id) {
            echo json_encode(['paciente' => null, 'citas' => []]);
            exit;
        }

        $stmtP = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
        $stmtP->execute([$paciente_id]);
        $paciente = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$paciente) {
            echo json_encode(['paciente' => null, 'citas' => []]);
            exit;
        }

        if (!array_key_exists('folio', $paciente)) {
            $paciente['folio'] = null;
        }

        $sqlHistorial = "
            SELECT 
                c.id, 
                c.fecha, 
                c.hora_inicio, 
                c.hora_fin, 
                c.es_sobrecupo,
                COALESCE(b.nombre, 'Box General') AS nombre_box,
                COALESCE(t.nombre, 'Atención General') AS tipo_atencion,
                COALESCE(t.color_hex, '#4A5568') AS color_hex,
                COALESCE(e.nombre, 'No asignado') AS nombre_especialista,
                'cita' AS origen
            FROM citas c
            LEFT JOIN boxes b ON c.box_id = b.id
            LEFT JOIN tipos_atencion t ON c.tipo_atencion_id = t.id
            LEFT JOIN especialistas e ON c.especialista_id = e.id
            WHERE c.paciente_id = ?

            UNION ALL

            SELECT 
                m.id,
                m.fecha_derivacion AS fecha,
                '00:00:00' AS hora_inicio,
                '00:00:00' AS hora_fin,
                0 AS es_sobrecupo,
                'Derivación Externa' AS nombre_box,
                'Mamografía' AS tipo_atencion,
                '#D53F8C' AS color_hex,
                CONCAT('Estado: ', m.estado) AS nombre_especialista,
                'mamografia' AS origen
            FROM mamografias m
            WHERE m.paciente_id = ?

            ORDER BY fecha DESC, hora_inicio DESC
        ";

        $stmtH = $pdo->prepare($sqlHistorial);
        $stmtH->execute([$paciente_id, $paciente_id]);
        $atenciones = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'paciente' => $paciente,
            'citas' => $atenciones
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -------------------------------------------------------------
    // ACCIÓN 3: LISTADO GENERAL DE PACIENTES PARA EL DIRECTORIO
    // -------------------------------------------------------------
    $stmtCol = $pdo->query("SHOW COLUMNS FROM pacientes LIKE 'folio'");
    $campoFolio = ($stmtCol && $stmtCol->fetch()) ? "p.folio" : "NULL AS folio";

    $sqlLista = "SELECT p.id, 
                        p.rut, 
                        $campoFolio, 
                        p.nombre, 
                        p.apellidos, 
                        COALESCE(p.telefono, '') AS telefono,
                        COALESCE(p.correo, '') AS correo,
                        COALESCE(p.seccion_unidad, '') AS seccion_unidad,
                        COALESCE(p.prevision, '') AS prevision,
                        TRIM(CONCAT(
                            COALESCE(GROUP_CONCAT(DISTINCT t.nombre SEPARATOR ' '), ''),
                            ' ',
                            IF(COUNT(DISTINCT m.id) > 0, 'Mamografía Mamografias Derivacion', '')
                        )) AS atenciones_texto
                 FROM pacientes p
                 LEFT JOIN citas c ON p.id = c.paciente_id
                 LEFT JOIN tipos_atencion t ON c.tipo_atencion_id = t.id
                 LEFT JOIN mamografias m ON p.id = m.paciente_id
                 WHERE p.rut NOT LIKE 'SALA-%' AND (p.seccion_unidad IS NULL OR p.seccion_unidad != 'Uso Interno')
                 GROUP BY p.id, p.rut, p.nombre, p.apellidos, p.telefono, p.correo, p.seccion_unidad, p.prevision
                 ORDER BY p.apellidos ASC, p.nombre ASC";

    $stmtL = $pdo->query($sqlLista);
    $pacientes = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pacientes, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
exit;