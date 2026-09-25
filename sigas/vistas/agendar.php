<?php
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

// Consultar tipos de atención activos
$tipos = $pdo->query("SELECT id, nombre, duracion_minutos, color_hex FROM tipos_atencion ORDER BY id ASC")->fetchAll();
?>

<div style="max-width: 780px; margin: 25px auto; background: #ffffff; padding: 25px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
    <h2 style="margin-top: 0; color: #1a365d; font-size: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">
        Agendar Cita / Derivación Médica
    </h2>

    <form id="form-nueva-agenda">
        <!-- DATOS DEL PACIENTE -->
        <h4 style="color: #2b6cb0; margin: 15px 0 10px 0; font-size: 14px;">1. Datos del Paciente</h4>
        <input type="hidden" id="paciente_id" name="paciente_id">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">RUT Paciente *</label>
                <input type="text" id="buscar_rut" name="rut" placeholder="Ej: 12345678-9" required
                       style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
                <small style="color: #718096; font-size: 11px;">Al salir del campo busca datos automáticamente.</small>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">N° Folio (Opcional)</label>
                <input type="text" id="folio" name="folio" placeholder="Ej: F-1024"
                       style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Nombres *</label>
                <input type="text" id="nombre" name="nombre" required
                       style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Apellidos *</label>
                <input type="text" id="apellidos" name="apellidos" required
                       style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Teléfono *</label>
                <input type="text" id="telefono" name="telefono" required
                       style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Correo Electrónico</label>
                <input type="email" id="correo" name="correo"
                       style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Sección / Unidad *</label>
                <input type="text" id="seccion_unidad" name="seccion_unidad" required
                       style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Previsión *</label>
                <select id="prevision" name="prevision" required
                        style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
                    <option value="">Seleccione previsión...</option>
                    <option value="FONASA A">FONASA A</option>
                    <option value="FONASA B">FONASA B</option>
                    <option value="FONASA C">FONASA C</option>
                    <option value="FONASA D">FONASA D</option>
                    <option value="ISAPRE">ISAPRE</option>
                    <option value="CAPREDENA">CAPREDENA</option>
                    <option value="DIPRECA">DIPRECA</option>
                    <option value="PARTICULAR">PARTICULAR</option>
                </select>
            </div>
        </div>

        <!-- DERIVACIÓN MAMOGRAFÍA -->
        <div style="background: #f7fafc; padding: 12px 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: bold; font-size: 13px; color: #2d3748; cursor: pointer;">
                <input type="checkbox" id="es_mamografia" name="es_mamografia" value="1" style="width: 16px; height: 16px;">
                ¿Es derivación externa a Mamografía? (No ocupa Box)
            </label>
        </div>

        <!-- DETALLE DE LA CITA EN BOX -->
        <div id="seccion-cita-box">
            <h4 style="color: #2b6cb0; margin: 15px 0 10px 0; font-size: 14px;">2. Configuración de Atención en Box</h4>

            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 15px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Tipo de Atención *</label>
                    <select id="tipo_atencion_id" name="tipo_atencion_id" required
                            style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
                        <option value="">Seleccione tipo de consulta...</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?> (<?= $t['duracion_minutos'] ?> min)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Box Asignado</label>
                    <input type="text" id="box_asignado_vista" readonly placeholder="Asignado automáticamente"
                           style="width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0; background: #edf2f7; border-radius: 5px; font-size: 13px; font-weight: bold;">
                    <input type="hidden" id="box_id" name="box_id">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Especialista *</label>
                    <select id="especialista_id" name="especialista_id" required
                            style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
                        <option value="">Seleccione tipo de atención primero...</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Fecha *</label>
                    <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required
                           style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
                </div>
            </div>

            <!-- CHECKBOX SOBRECUPO -->
            <div style="background: #fff5f5; border: 1px solid #feb2b2; padding: 10px 14px; border-radius: 6px; margin-bottom: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: bold; color: #c53030; cursor: pointer;">
                    <input type="checkbox" id="es_sobrecupo" name="es_sobrecupo" value="1" style="width: 16px; height: 16px;">
                    Habilitar Sobrecupo (Permite seleccionar y agendar en horarios ya ocupados)
                </label>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 4px;">Bloque Horario Disponible *</label>
                <select id="bloque_hora" required
                        style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
                    <option value="">Seleccione tipo de atención y fecha...</option>
                </select>
                <input type="hidden" id="hora_inicio" name="hora_inicio">
                <input type="hidden" id="hora_fin" name="hora_fin">
            </div>
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
            <a href="/sigas/dashboard.php" class="btn" style="padding: 9px 18px; border: 1px solid #cbd5e0; text-decoration: none; border-radius: 5px; color: #4a5568; font-size: 13px;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding: 9px 22px; background: #1a365d; color: #ffffff; border: none; border-radius: 5px; font-size: 13px; font-weight: bold; cursor: pointer;">
                Guardar Cita
            </button>
        </div>
    </form>
</div>

<script src="/sigas/assets/js/formulario_agendar.js"></script>
<?php include '../includes/footer.php'; ?>