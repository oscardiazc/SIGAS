<?php
// CONTROLADOR INTEGRAL DE CITAS (CONSULTAS, RESERVAS DE BOX, SEMANAL Y ELIMINACIÓN)
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../conexion.php';
require_once 'auditoria.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$admin_id_sesion = $_SESSION['admin_id'] ?? 1;
$accion = $_GET['accion'] ?? '';

// =========================================================================
// ACCIÓN 1: OBTENER CITAS DE UN DÍA (MONITOR PRINCIPAL / DASHBOARD)
// =========================================================================
if ($accion === 'dia') {
    $fecha = $_GET['fecha'] ?? date('Y-m-d');

    try {
        $sql = "SELECT c.id, c.box_id, c.fecha, c.hora_inicio, c.hora_fin, c.es_sobrecupo,
                       p.nombre, p.apellidos, p.rut, p.correo, p.telefono, p.seccion_unidad, p.prevision,
                       COALESCE(t.nombre, 'Reserva de Box / Sala') AS tipo_atencion,
                       COALESCE(t.color_hex, '#4A5568') AS color_hex
                FROM citas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN tipos_atencion t ON c.tipo_atencion_id = t.id
                WHERE c.fecha = ? AND c.estado = 'confirmada'
                ORDER BY c.hora_inicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha]);
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Garantiza SIEMPRE un array [] para que JS no lance TypeError en forEach
        echo json_encode($citas ? $citas : [], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        // En caso de fallo en BD, responde array vacío para evitar romper el frontend
        echo json_encode([]);
    }
    exit;
}

// =========================================================================
// ACCIÓN 2: OBTENER CITAS DE UN BOX EN RANGO SEMANAL
// =========================================================================
if ($accion === 'box_rango') {
    $boxId = (int)($_GET['box_id'] ?? 0);
    $fechaInicio = $_GET['fecha_inicio'] ?? '';
    $fechaFin = $_GET['fecha_fin'] ?? '';

    if (!$boxId || empty($fechaInicio) || empty($fechaFin)) {
        echo json_encode([]);
        exit;
    }

    try {
        $sql = "SELECT c.id, c.box_id, c.fecha, c.hora_inicio, c.hora_fin, c.es_sobrecupo,
                       p.nombre, p.apellidos, p.rut, p.correo, p.telefono, p.seccion_unidad, p.prevision,
                       COALESCE(t.nombre, 'Reserva de Box') AS tipo_atencion,
                       COALESCE(t.color_hex, '#4A5568') AS color_hex
                FROM citas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                LEFT JOIN tipos_atencion t ON c.tipo_atencion_id = t.id
                WHERE c.box_id = ? AND c.fecha BETWEEN ? AND ? AND c.estado = 'confirmada'
                ORDER BY c.fecha ASC, c.hora_inicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$boxId, $fechaInicio, $fechaFin]);
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($citas ? $citas : [], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit;
}

// Lectura de cuerpo JSON para acciones POST
$input = json_decode(file_get_contents('php://input'), true);

// =========================================================================
// ACCIÓN 3: ELIMINAR CITA CON CLAVE DE ADMINISTRADOR
// =========================================================================
if ($accion === 'eliminar') {
    $cita_id = (int)($input['cita_id'] ?? 0);
    $password = trim($input['password'] ?? '');

    if (!$cita_id || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Debe ingresar su contraseña de administrador.']);
        exit;
    }

    // Reautenticar clave
    $stmtAuth = $pdo->prepare("SELECT password_hash FROM administradores WHERE id = ?");
    $stmtAuth->execute([$admin_id_sesion]);
    $admin = $stmtAuth->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta. Operación denegada.']);
        exit;
    }

    try {
        $stmtCita = $pdo->prepare("SELECT c.fecha, c.hora_inicio, c.hora_fin, c.box_id, p.rut, p.nombre, p.apellidos 
                                   FROM citas c 
                                   INNER JOIN pacientes p ON c.paciente_id = p.id 
                                   WHERE c.id = ?");
        $stmtCita->execute([$cita_id]);
        $cita = $stmtCita->fetch();

        if (!$cita) {
            echo json_encode(['success' => false, 'message' => 'La cita no existe o ya fue eliminada.']);
            exit;
        }

        $stmtDel = $pdo->prepare("DELETE FROM citas WHERE id = ?");
        $stmtDel->execute([$cita_id]);

        registrar_auditoria($pdo, $admin_id_sesion, 'ELIMINAR_CITA', "Eliminó cita ID $cita_id de {$cita['nombre']} {$cita['apellidos']} ({$cita['rut']}) en Box {$cita['box_id']} el {$cita['fecha']}");

        echo json_encode(['success' => true, 'message' => 'Cita eliminada correctamente.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// ACCIÓN 4: GUARDAR RESERVA DE BOX DIRECTA (USO DE SALAS INTERNAS)
// =========================================================================
if ($accion === 'guardar_reserva_box') {
    $box_id = (int)($input['box_id'] ?? 0);
    $responsable = trim($input['responsable'] ?? '');
    $motivo = trim($input['motivo'] ?? 'Reunión / Uso Interno');
    $fecha = $input['fecha'] ?? '';
    $hora_inicio = $input['hora_inicio'] ?? '';
    $hora_fin = $input['hora_fin'] ?? '';

    if (!$box_id || empty($responsable) || empty($fecha) || empty($hora_inicio) || empty($hora_fin)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos de la reserva son obligatorios.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $rutSala = 'SALA-' . substr(md5(uniqid(rand(), true)), 0, 7);
        $stmtInsP = $pdo->prepare("INSERT INTO pacientes (rut, nombre, apellidos, correo, telefono, seccion_unidad, prevision) 
                                  VALUES (?, ?, '(Uso de Sala)', 'sin_correo@centro.cl', '000000000', 'Uso Interno', 'PARTICULAR')");
        $stmtInsP->execute([$rutSala, $responsable]);
        $paciente_id = $pdo->lastInsertId();

        // Validar cruce de horario
        $stmtCheck = $pdo->prepare("SELECT id FROM citas 
                                    WHERE box_id = ? AND fecha = ? AND estado = 'confirmada' AND es_sobrecupo = 0 
                                      AND (hora_inicio < ? AND hora_fin > ?)");
        $stmtCheck->execute([$box_id, $fecha, $hora_fin, $hora_inicio]);
        if ($stmtCheck->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'El horario solicitado para el box ya está ocupado.']);
            exit;
        }

        // Insertar reserva de sala con tipo_atencion_id = 1 (base)
        $stmtC = $pdo->prepare("INSERT INTO citas (paciente_id, box_id, tipo_atencion_id, fecha, hora_inicio, hora_fin, es_sobrecupo, estado) 
                                VALUES (?, ?, 1, ?, ?, ?, 0, 'confirmada')");
        $stmtC->execute([$paciente_id, $box_id, $fecha, $hora_inicio, $hora_fin]);

        registrar_auditoria($pdo, $admin_id_sesion, 'RESERVA_SALA', "Reserva de Box $box_id por $responsable el $fecha ($hora_inicio a $hora_fin)");

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Sala reservada exitosamente.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// ACCIÓN 5: GUARDAR CITA MÉDICA REGULAR / MAMOGRAFÍA / SOBRECUPO
// =========================================================================
if ($accion === 'guardar_cita' || empty($accion)) {
    $rut = trim($input['rut'] ?? '');
    $folio = !empty($input['folio']) ? trim($input['folio']) : null;
    $nombre = trim($input['nombre'] ?? '');
    $apellidos = trim($input['apellidos'] ?? '');
    $telefono = trim($input['telefono'] ?? '');
    $correo = trim($input['correo'] ?? '');
    $seccion_unidad = trim($input['seccion_unidad'] ?? '');
    $prevision = trim($input['prevision'] ?? '');
    $es_mamografia = !empty($input['es_mamografia']);

    if (empty($rut) || empty($nombre) || empty($apellidos)) {
        echo json_encode(['success' => false, 'message' => 'Complete los datos obligatorios del paciente.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Guardar o actualizar paciente soportando campo folio si existe
        $stmtCol = $pdo->query("SHOW COLUMNS FROM pacientes LIKE 'folio'");
        $tieneFolio = ($stmtCol && $stmtCol->fetch());

        $stmtP = $pdo->prepare("SELECT id FROM pacientes WHERE rut = ?");
        $stmtP->execute([$rut]);
        $paciente = $stmtP->fetch();

        if ($paciente) {
            $paciente_id = $paciente['id'];
            if ($tieneFolio) {
                $stmtUpd = $pdo->prepare("UPDATE pacientes SET folio = ?, nombre = ?, apellidos = ?, correo = ?, telefono = ?, seccion_unidad = ?, prevision = ? WHERE id = ?");
                $stmtUpd->execute([$folio, $nombre, $apellidos, $correo, $telefono, $seccion_unidad, $prevision, $paciente_id]);
            } else {
                $stmtUpd = $pdo->prepare("UPDATE pacientes SET nombre = ?, apellidos = ?, correo = ?, telefono = ?, seccion_unidad = ?, prevision = ? WHERE id = ?");
                $stmtUpd->execute([$nombre, $apellidos, $correo, $telefono, $seccion_unidad, $prevision, $paciente_id]);
            }
        } else {
            if ($tieneFolio) {
                $stmtIns = $pdo->prepare("INSERT INTO pacientes (rut, folio, nombre, apellidos, correo, telefono, seccion_unidad, prevision) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtIns->execute([$rut, $folio, $nombre, $apellidos, $correo, $telefono, $seccion_unidad, $prevision]);
            } else {
                $stmtIns = $pdo->prepare("INSERT INTO pacientes (rut, nombre, apellidos, correo, telefono, seccion_unidad, prevision) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtIns->execute([$rut, $nombre, $apellidos, $correo, $telefono, $seccion_unidad, $prevision]);
            }
            $paciente_id = $pdo->lastInsertId();
        }

        // 2. Si es derivación a Mamografía
        if ($es_mamografia) {
            $stmtMamo = $pdo->prepare("INSERT INTO mamografias (paciente_id, fecha_derivacion, estado) VALUES (?, CURRENT_DATE, 'lista de espera')");
            $stmtMamo->execute([$paciente_id]);

            registrar_auditoria($pdo, $admin_id_sesion, 'DERIVACION', "Derivación a mamografía para RUT: $rut");
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Derivación a mamografía registrada exitosamente.']);
            exit;
        }

        // 3. Si es Cita en Box clínico
        $box_id = (int)($input['box_id'] ?? 0);
        $especialista_id = !empty($input['especialista_id']) ? (int)$input['especialista_id'] : null;
        $tipo_atencion_id = (int)($input['tipo_atencion_id'] ?? 0);
        $fecha = $input['fecha'] ?? '';
        $hora_inicio = $input['hora_inicio'] ?? '';
        $hora_fin = $input['hora_fin'] ?? '';
        $es_sobrecupo = !empty($input['es_sobrecupo']) ? 1 : 0;

        if (!$box_id || !$tipo_atencion_id || empty($fecha) || empty($hora_inicio) || empty($hora_fin)) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros de fecha y horario para la cita.']);
            exit;
        }

        // Validar choque de horarios si no es sobrecupo
        if ($es_sobrecupo === 0) {
            $stmtCheck = $pdo->prepare("SELECT id FROM citas 
                                        WHERE box_id = ? AND fecha = ? AND estado = 'confirmada' AND es_sobrecupo = 0
                                          AND hora_inicio < ? AND hora_fin > ?");
            $stmtCheck->execute([$box_id, $fecha, $hora_fin, $hora_inicio]);
            if ($stmtCheck->fetch()) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'El horario ya está ocupado. Marque Sobrecupo si desea forzar la atención.']);
                exit;
            }
        }

        // Insertar cita
        $stmtCita = $pdo->prepare("INSERT INTO citas (paciente_id, box_id, especialista_id, tipo_atencion_id, fecha, hora_inicio, hora_fin, es_sobrecupo, estado) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmada')");
        $stmtCita->execute([$paciente_id, $box_id, $especialista_id, $tipo_atencion_id, $fecha, $hora_inicio, $hora_fin, $es_sobrecupo]);

        $tipoAccion = $es_sobrecupo ? 'CITA_SOBRECUPO' : 'AGENDAR_CITA';
        registrar_auditoria($pdo, $admin_id_sesion, $tipoAccion, "Cita agendada para RUT $rut en Box $box_id el $fecha ($hora_inicio a $hora_fin)");

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Cita médica guardada con éxito.']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
exit;