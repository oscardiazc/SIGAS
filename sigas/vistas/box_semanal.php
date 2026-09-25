<?php
// VISTA SEMANAL POR BOX ESPECIFICO
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

$box_id = (int)($_GET['box'] ?? 1);
if ($box_id < 1 || $box_id > 5) $box_id = 1;

$fechaReferencia = $_GET['fecha'] ?? date('Y-m-d');
$tiempoRef = strtotime($fechaReferencia);

$diaSemana = date('N', $tiempoRef);
$lunesTimestamp = strtotime("-" . ($diaSemana - 1) . " days", $tiempoRef);
$lunesFecha = date('Y-m-d', $lunesTimestamp);
$viernesFecha = date('Y-m-d', strtotime("+4 days", $lunesTimestamp));

$semanaAnterior = date('Y-m-d', strtotime("-7 days", $lunesTimestamp));
$semanaSiguiente = date('Y-m-d', strtotime("+7 days", $lunesTimestamp));

$diasSemana = [
    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes'
];
?>

<div class="panel-grilla" style="padding: 15px 20px; height: calc(100vh - 100px); display: flex; flex-direction: column;">
    <div class="grilla-header-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <h2>Agenda Semanal - Box <?= $box_id ?></h2>
            <span style="font-size: 14px; color: #4a5568;">
                Semana del <?= date('d/m/Y', strtotime($lunesFecha)) ?> al <?= date('d/m/Y', strtotime($viernesFecha)) ?>
            </span>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="box_semanal.php?box=<?= $box_id ?>&fecha=<?= $semanaAnterior ?>" class="btn btn-light">&larr; Semana Anterior</a>
            <a href="box_semanal.php?box=<?= $box_id ?>&fecha=<?= date('Y-m-d') ?>" class="btn btn-light">Hoy</a>
            <a href="box_semanal.php?box=<?= $box_id ?>&fecha=<?= $semanaSiguiente ?>" class="btn btn-light">Semana Siguiente &rarr;</a>
            <a href="box_mensual.php?box=<?= $box_id ?>" class="btn btn-secondary">Vista Mensual</a>
        </div>
    </div>

    <input type="hidden" id="box_id" value="<?= $box_id ?>">
    <input type="hidden" id="fecha_lunes" value="<?= $lunesFecha ?>">
    <input type="hidden" id="fecha_viernes" value="<?= $viernesFecha ?>">

    <!-- CONTENEDOR CON SCROLL Y BLOQUES HORARIOS -->
    <div class="contenedor-horario-scroll">
        <div class="grilla-boxes-dia" id="grilla-semanal">
            <!-- Columna lateral de horas -->
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

            <!-- Columnas de Lunes a Viernes -->
            <?php for ($i = 0; $i < 5; $i++): 
                $fechaCol = date('Y-m-d', strtotime("+$i days", $lunesTimestamp));
                $nombreDia = $diasSemana[$i + 1];
            ?>
                <div class="columna-box">
                    <div class="columna-box-header">
                        <?= $nombreDia ?><br>
                        <small style="font-weight: normal; font-size: 11px; opacity: 0.85;"><?= date('d/m', strtotime($fechaCol)) ?></small>
                    </div>
                    <div class="columna-box-cuerpo" id="col-dia-<?= $fechaCol ?>"></div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="leyenda-atenciones">
        <span class="leyenda-item"><span class="color-box" style="background:#FF9800;"></span> Certificado </span>
        <span class="leyenda-item"><span class="color-box" style="background:#2196F3;"></span> Morbilidad </span>
        <span class="leyenda-item"><span class="color-box" style="background:#4CAF50;"></span> EMP </span>
        <span class="leyenda-item"><span class="color-box" style="background:#9C27B0;"></span> Salud Mental USIT </span>
        <span class="leyenda-item"><span class="color-box" style="background:#4A5568;"></span> Reserva de Sala</span>
    </div>
</div>

<!-- MODAL DETALLE Y ELIMINAR -->
<div id="modal-cita-semanal" class="modal">
    <div class="modal-contenido">
        <h3>Detalle de la Atención</h3>
        <div id="modal-semanal-datos"></div>
        <div class="modal-acciones" id="modal-acciones-box">
            <button id="btn-eliminar-semanal" class="btn btn-danger">Eliminar Cita</button>
            <button id="btn-cerrar-semanal" class="btn btn-light">Cerrar</button>
        </div>
        <div id="area-confirmar-clave-semanal" style="display:none; margin-top: 15px; border-top: 1px solid #edf2f7; padding-top: 12px;">
            <p style="font-size: 12px; color: #c53030; margin-bottom: 8px;">
                <strong>Advertencia:</strong> Ingrese su contraseña de Administrador para confirmar la eliminación:
            </p>
            <div class="form-group">
                <input type="password" id="clave_admin_semanal" placeholder="Contraseña de Administrador">
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button id="btn-confirmar-baja-semanal" class="btn btn-danger">Confirmar</button>
                <button id="btn-cancelar-baja-semanal" class="btn btn-light">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script src="/sigas/assets/js/agenda_box.js"></script>
<?php include '../includes/footer.php'; ?>