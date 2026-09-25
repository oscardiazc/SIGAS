<?php
require_once 'includes/auth.php';
require_once 'conexion.php';
include 'includes/header.php';

// Consultar los boxes directamente desde la base de datos
$boxes = $pdo->query("SELECT id, nombre FROM boxes ORDER BY id ASC")->fetchAll();
?>

<div class="panel-grilla" style="padding: 15px 20px; height: calc(100vh - 85px); display: flex; flex-direction: column;">
    <div class="grilla-header-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2>Ocupación Diaria de Boxes - <?= date('d/m/Y') ?></h2>
        <div style="display: flex; gap: 10px;">
            <a href="/sigas/vistas/agendar.php" class="btn btn-primary" style="font-weight: bold; padding: 8px 16px;">+ Agendar Paciente</a>
            <a href="/sigas/vistas/agendar_box.php" class="btn btn-secondary" style="font-weight: bold; padding: 8px 16px;">+ Agendar Box</a>
        </div>
    </div>

    <div class="contenedor-horario-scroll">
        <div class="grilla-boxes-dia" id="grilla-dia">
            <!-- Columna lateral de tiempo -->
            <div class="columna-horas-lateral">
                <div class="header-columna-tiempo">Horario</div>
                <div class="bloque-etiqueta-hora">08:00</div>
                <div class="bloque-etiqueta-hora">09:00</div>
                <div class="bloque-etiqueta-hora">10:00</div>
                <div class="bloque-etiqueta-hora">11:00</div>
                <div class="bloque-etiqueta-hora">12:00</div>
                <div class="bloque-etiqueta-hora">14:00</div>
                <div class="bloque-etiqueta-hora">15:00</div>
                <div class="bloque-etiqueta-hora">16:00</div>
                <div class="bloque-etiqueta-hora">17:00</div>
            </div>

            <!-- Columnas con nombres reales desde la tabla boxes -->
            <?php foreach ($boxes as $box): ?>
                <div class="columna-box">
                    <div class="columna-box-header"><?= htmlspecialchars($box['nombre']) ?></div>
                    <div class="columna-box-cuerpo" id="box-cuerpo-<?= $box['id'] ?>"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <input type="hidden" id="fecha" value="<?= date('Y-m-d') ?>">

    <!-- LEYENDA INFERIOR -->
    <div class="leyenda-atenciones">
        <span class="leyenda-item"><span class="color-box" style="background:#00BCD4;"></span> Toma de Muestras</span>
        <span class="leyenda-item"><span class="color-box" style="background:#4CAF50;"></span> EMP</span>
        <span class="leyenda-item"><span class="color-box" style="background:#E91E63;"></span> Dental</span>
        <span class="leyenda-item"><span class="color-box" style="background:#2196F3;"></span> Morbilidad</span>
        <span class="leyenda-item"><span class="color-box" style="background:#FF9800;"></span> Certificados</span>
        <span class="leyenda-item"><span class="color-box" style="background:#9C27B0;"></span> Salud Mental USIT</span>
        <span class="leyenda-item"><span class="color-box" style="background:#009688;"></span> Kinesiología</span>
        <span class="leyenda-item"><span class="color-box" style="background:#4A5568;"></span> Reserva de Sala</span>
    </div>
</div>

<!-- MODAL DETALLE Y ELIMINACION -->
<div id="modal-cita" class="modal">
    <div class="modal-contenido" style="width: 440px;">
        <h3>Detalle de la Atención</h3>
        <div id="modal-datos-paciente"></div>
        <div class="modal-acciones" id="modal-botones-principales">
            <button id="btn-abrir-eliminar" class="btn btn-danger">Eliminar Cita</button>
            <button id="btn-cerrar-modal" class="btn btn-light">Cerrar</button>
        </div>

        <div id="area-confirmar-clave" style="display:none; margin-top: 15px; border-top: 1px solid #edf2f7; padding-top: 12px;">
            <p style="font-size: 12px; color: #c53030; margin-bottom: 8px;">
                <strong>Advertencia:</strong> Ingrese su contraseña de Administrador para confirmar:
            </p>
            <div class="form-group">
                <input type="password" id="clave_admin_confirmar" placeholder="Contraseña de Administrador">
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button id="btn-confirmar-baja" class="btn btn-danger">Confirmar</button>
                <button id="btn-cancelar-baja" class="btn btn-light">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script src="/sigas/assets/js/agenda_hoy.js"></script>
<?php include 'includes/footer.php'; ?>