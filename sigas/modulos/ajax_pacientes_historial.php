<?php
// CONTROLADOR UNIFICADO: PACIENTES E HISTORIAL CLINICO
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../conexion.php';
header('Content-Type: application/json; charset=utf-8');

$paciente_id = (int)($_GET['paciente_id'] ?? 0);
$accion = $_GET['accion'] ?? ($paciente_id > 0 ? 'detalle' : 'listar');

try {
    // -------------------------------------------------------------
    // ACCIÓN 1: DETALLE E HISTORIAL COMPLETO DE UN PACIENTE ESPECÍFICO
    // -------------------------------------------------------------
    if ($accion === 'detalle' || $paciente_id > 0) {
        if (!$paciente_id) {
            echo json_encode(['paciente' => null, 'citas' => []]);
            exit;
        }

        // 1. Datos personales del paciente
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

        // 2. Citas en Box + Derivaciones a Mamografía combinadas cronológicamente
        $sqlHistorial = "
            -- Citas regulares en box clínico
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

            -- Derivaciones a Mamografía
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
    // ACCIÓN 2: LISTADO GENERAL DE PACIENTES PARA EL DIRECTORIO
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