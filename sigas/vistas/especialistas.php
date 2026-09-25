<?php
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

$especialistas = $pdo->query("SELECT * FROM especialistas ORDER BY nombre ASC")->fetchAll();
$tipos = $pdo->query("SELECT t.*, b.nombre as nombre_box FROM tipos_atencion t 
                      INNER JOIN boxes b ON t.box_id = b.id 
                      ORDER BY t.box_id ASC, t.nombre ASC")->fetchAll();
?>

<!-- CONTENEDOR PRINCIPAL CENTRADO -->
<div style="background-color: #f4f6f9; min-height: calc(100vh - 75px); padding: 30px 20px;">
    <div style="max-width: 980px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 25px 30px; border: 1px solid #e2e8f0;">
        
        <!-- CABECERA -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #edf2f7; padding-bottom: 16px; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 21px; color: #1a365d; margin: 0; font-weight: bold;">Directorio de Especialistas</h2>
                <p style="font-size: 13px; color: #718096; margin: 4px 0 0 0;">Haga clic sobre un especialista para ver su ficha completa y opciones.</p>
            </div>
            <button id="btn-nuevo-esp" class="btn btn-primary" style="font-size: 13.5px; padding: 9px 18px; font-weight: bold; border-radius: 5px;">+ Nuevo Especialista</button>
        </div>

        <!-- TABLA CON DISTRIBUCIÓN MATEMÁTICA Y FOTOS AMPLIADAS (60px) -->
        <div style="border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e0;">
            <table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background-color: #1a365d; color: #ffffff; text-align: left;">
                        <th style="width: 14%; padding: 12px 14px; text-align: center;">Perfil</th>
                        <th style="width: 22%; padding: 12px 14px;">RUT</th>
                        <th style="width: 38%; padding: 12px 14px;">Nombre Completo</th>
                        <th style="width: 26%; padding: 12px 14px;">Cargo / Especialidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($especialistas)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 25px; color: #718096;">No hay especialistas registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($especialistas as $esp): ?>
                            <tr class="fila-esp" onclick='abrirFichaEspecialista(<?= htmlspecialchars(json_encode($esp), ENT_QUOTES, "UTF-8") ?>)' 
                                style="cursor: pointer; border-bottom: 1px solid #edf2f7; transition: background 0.15s ease;">
                                <td style="padding: 10px 14px; text-align: center; vertical-align: middle;">
                                    <?php if (!empty($esp['foto_perfil']) && file_exists(__DIR__ . '/../' . $esp['foto_perfil'])): ?>
                                        <img src="/sigas/<?= htmlspecialchars($esp['foto_perfil']) ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2.5px solid #cbd5e0; display: block; margin: 0 auto; box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; border-radius: 50%; background: #2b6cb0; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold; margin: 0 auto; box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
                                            <?= strtoupper(substr($esp['nombre'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px 14px; font-weight: bold; color: #2d3748; vertical-align: middle;">
                                    <?= htmlspecialchars($esp['rut']) ?>
                                </td>
                                <td style="padding: 10px 14px; color: #1a202c; font-weight: 500; font-size: 15px; vertical-align: middle;">
                                    <?= htmlspecialchars($esp['nombre']) ?>
                                </td>
                                <td style="padding: 10px 14px; color: #4a5568; font-size: 13.5px; vertical-align: middle;">
                                    <?= htmlspecialchars($esp['cargo_titulo']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<style>
.fila-esp:hover {
    background-color: #f7fafc !important;
}
</style>

<!-- POP-UP DETALLE ESPECIALISTA: FOTO GRANDE (130px) -->
<div id="modal-detalle-esp" class="modal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
    <div class="modal-contenido" style="width: 100%; max-width: 430px; padding: 26px 24px; text-align: center; position: relative; border-radius: 8px; background: #fff;">
        <button type="button" onclick="cerrarModalDetalleEsp()" style="position: absolute; top: 12px; right: 14px; background: none; border: none; font-size: 24px; color: #718096; cursor: pointer;">&times;</button>
        
        <div id="detalle-esp-avatar-box" style="margin-bottom: 16px;"></div>
        <h3 id="detalle-esp-nombre" style="margin: 0 0 4px 0; color: #1a365d; font-size: 19px; font-weight: bold;"></h3>
        <p id="detalle-esp-cargo" style="margin: 0 0 16px 0; color: #718096; font-size: 13.5px; font-weight: 500;"></p>
        
        <div style="background: #f8fafc; border: 1px solid #edf2f7; border-radius: 6px; padding: 14px 18px; text-align: left; font-size: 13.5px; margin-bottom: 20px;">
            <p style="margin: 5px 0;"><strong>RUT:</strong> <span id="detalle-esp-rut" style="color: #2d3748;"></span></p>
            <p style="margin: 8px 0 5px 0;"><strong>Consultas que Atiende:</strong></p>
            <div id="detalle-esp-consultas" style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 6px;"></div>
        </div>

        <div style="display: flex; justify-content: space-between; gap: 10px;">
            <button type="button" class="btn btn-danger" id="btn-detalle-eliminar-esp" style="padding: 8px 16px; font-size: 13px;">Eliminar</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-light" onclick="cerrarModalDetalleEsp()" style="padding: 8px 14px; font-size: 13px;">Cerrar</button>
                <button type="button" class="btn btn-secondary" id="btn-detalle-editar-esp" style="padding: 8px 16px; font-size: 13px;">Editar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FORMULARIO CREAR / EDITAR ESPECIALISTA -->
<div id="modal-esp" class="modal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
    <div class="modal-contenido" style="width: 100%; max-width: 440px; padding: 25px 28px; position: relative; border-radius: 8px; background: #fff; max-height: 90vh; overflow-y: auto;">
        <button type="button" onclick="cerrarModalFormularioEsp()" style="position: absolute; top: 14px; right: 16px; background: none; border: none; font-size: 24px; color: #718096; cursor: pointer;">&times;</button>
        <h3 id="modal-esp-titulo" style="margin: 0 0 16px 0; font-size: 18px; color: #1a365d; border-bottom: 1px solid #edf2f7; padding-bottom: 10px;">Nuevo Especialista</h3>

        <form id="form-esp">
            <input type="hidden" id="esp_id" name="id">
            <input type="hidden" id="esp_foto_recortada_base64" name="foto_recortada">

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 4px; color: #2d3748; display: block;">Nombre Completo</label>
                <input type="text" id="esp_nombre" name="nombre" required style="height: 36px; font-size: 13.5px; width: 100%; border-radius: 5px; border: 1px solid #cbd5e0; padding: 6px 10px; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 4px; color: #2d3748; display: block;">RUT</label>
                <input type="text" id="esp_rut" name="rut" required placeholder="12.345.678-9" maxlength="12" autocomplete="off" style="height: 36px; font-size: 13.5px; width: 100%; border-radius: 5px; border: 1px solid #cbd5e0; padding: 6px 10px; box-sizing: border-box;">
                <small style="font-size: 11.5px; color: #718096; display: block; margin-top: 3px;">* Ingrese solo números (máximo 9 dígitos).</small>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 4px; color: #2d3748; display: block;">Cargo / Especialidad / Título</label>
                <input type="text" id="esp_cargo" name="cargo_titulo" required placeholder="Ej: Médico Cirujano" style="height: 36px; font-size: 13.5px; width: 100%; border-radius: 5px; border: 1px solid #cbd5e0; padding: 6px 10px; box-sizing: border-box;">
            </div>

            <!-- PREVIEW CIRCULAR EN FORMULARIO (75px) -->
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 6px; color: #2d3748; display: block;">Foto de Perfil</label>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div id="circulo-preview-esp" style="width: 75px; height: 75px; border-radius: 50%; background: #edf2f7; border: 2px dashed #cbd5e0; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <span style="font-size: 11px; color: #a0aec0; text-align: center;">Sin foto</span>
                    </div>
                    <div>
                        <input type="file" id="input_foto_esp" accept="image/*" style="font-size: 12.5px;">
                        <small style="font-size: 11px; color: #718096; display: block; margin-top: 4px;">Seleccione una imagen para ajustar el encuadre.</small>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 18px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 6px; color: #2d3748; display: block;">Tipos de Consulta que Atiende</label>
                <div style="max-height: 140px; overflow-y: auto; border: 1px solid #cbd5e0; border-radius: 4px; padding: 6px 10px; background: #fff;">
                    <?php foreach ($tipos as $t): ?>
                        <div style="display: flex; align-items: center; gap: 8px; padding: 4px 0; border-bottom: 1px solid #f1f5f9;">
                            <input type="checkbox" name="atenciones[]" value="<?= $t['id'] ?>" id="tipo_<?= $t['id'] ?>"
                                   style="width: 16px !important; height: 16px !important; margin: 0; cursor: pointer;">
                            <label for="tipo_<?= $t['id'] ?>" style="margin: 0; cursor: pointer; font-size: 12px; color: #2d3748; display: flex; justify-content: space-between; width: 100%;">
                                <span><?= htmlspecialchars($t['nombre']) ?></span>
                                <span style="color: #718096; font-size: 11px;">&rarr; <strong><?= htmlspecialchars($t['nombre_box']) ?></strong></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #edf2f7; padding-top: 15px;">
                <button type="button" class="btn btn-light" onclick="cerrarModalFormularioEsp()" style="padding: 8px 16px; font-size: 13px;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 13px;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CROPPER CIRCULAR -->
<div id="modal-crop-esp" class="modal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.7); z-index: 2000;">
    <div style="background: #ffffff; width: 100%; max-width: 420px; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <h4 style="margin: 0 0 10px 0; color: #1a365d; font-size: 16px;">Ajustar Foto del Especialista</h4>
        <p style="font-size: 12px; color: #718096; margin: 0 0 12px 0;">Arrastra con el ratón para centrar y mueve la barra para hacer zoom:</p>

        <div style="position: relative; width: 280px; height: 280px; margin: 0 auto; background: #2d3748; overflow: hidden; border-radius: 6px; cursor: grab;" id="crop-viewport-esp">
            <canvas id="crop-canvas-esp" width="280" height="280"></canvas>
            <div style="position: absolute; inset: 0; pointer-events: none; border-radius: 50%; box-shadow: 0 0 0 9999px rgba(0,0,0,0.55); border: 2px solid #3182ce;"></div>
        </div>

        <div style="margin: 15px auto 10px auto; width: 280px; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 12px; color: #718096;">Zoom:</span>
            <input type="range" id="crop-zoom-esp" min="0.5" max="3" step="0.05" value="1" style="flex: 1;">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 15px;">
            <button type="button" class="btn btn-light" onclick="cancelarRecorteEsp()" style="padding: 6px 12px; font-size: 12.5px;">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="confirmarRecorteEsp()" style="padding: 6px 15px; font-size: 12.5px;">Aplicar Recorte</button>
        </div>
    </div>
</div>

<!-- MODAL PROTEGIDO POR CLAVE PARA ELIMINAR -->
<div id="modal-confirmar-eliminar-esp" class="modal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
    <div class="modal-contenido" style="width: 100%; max-width: 390px; padding: 22px; background: #fff; border-radius: 8px;">
        <h3 style="margin-top: 0; color: #c53030; font-size: 17px;">Confirmar Eliminación</h3>
        <p id="txt-eliminar-mensaje-esp" style="font-size: 13.5px; margin-bottom: 14px; color: #4a5568;"></p>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label style="font-size: 12.5px; font-weight: bold; display: block; margin-bottom: 4px;">Ingrese su contraseña de Administrador:</label>
            <input type="password" id="clave_autorizacion_esp" placeholder="••••••••" style="height: 34px; font-size: 13px; width: 100%; border: 1px solid #cbd5e0; border-radius: 4px; padding: 4px 8px; box-sizing: border-box;">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="btn btn-light" onclick="cerrarModalesConfirmarEsp()" style="padding: 6px 12px; font-size: 12.5px;">Cancelar</button>
            <button type="button" id="btn-ejecutar-baja-esp" class="btn btn-danger" style="padding: 6px 14px; font-size: 12.5px;">Confirmar Baja</button>
        </div>
    </div>
</div>

<script>
function aplicarMascaraRut(input) {
    if (!input) return;
    input.addEventListener('input', (e) => {
        let valorLimpio = e.target.value.replace(/[^0-9kK]/g, '').toUpperCase();
        if (valorLimpio.length > 9) valorLimpio = valorLimpio.substring(0, 9);
        if (!valorLimpio) { e.target.value = ''; return; }
        if (valorLimpio.length === 1) { e.target.value = valorLimpio; return; }

        let dv = valorLimpio.slice(-1);
        let cuerpo = valorLimpio.slice(0, -1);
        let cuerpoFormateado = '';
        let contador = 0;
        for (let i = cuerpo.length - 1; i >= 0; i--) {
            cuerpoFormateado = cuerpo.charAt(i) + cuerpoFormateado;
            contador++;
            if (contador === 3 && i > 0) {
                cuerpoFormateado = '.' + cuerpoFormateado;
                contador = 0;
            }
        }
        e.target.value = `${cuerpoFormateado}-${dv}`;
    });
}

const inputRutEsp = document.getElementById('esp_rut');
aplicarMascaraRut(inputRutEsp);

let espActivo = null;

function cerrarModalDetalleEsp() { document.getElementById('modal-detalle-esp').style.display = 'none'; }
function cerrarModalFormularioEsp() { document.getElementById('modal-esp').style.display = 'none'; }
function cerrarModalesConfirmarEsp() { document.getElementById('modal-confirmar-eliminar-esp').style.display = 'none'; }

// RENDERIZAR FOTO EN POP-UP (130px)
function abrirFichaEspecialista(esp) {
    espActivo = esp;
    document.getElementById('detalle-esp-nombre').textContent = esp.nombre;
    document.getElementById('detalle-esp-cargo').textContent = esp.cargo_titulo;
    document.getElementById('detalle-esp-rut').textContent = esp.rut;

    const avatarBox = document.getElementById('detalle-esp-avatar-box');
    if (esp.foto_perfil) {
        avatarBox.innerHTML = `<img src="/sigas/${esp.foto_perfil}" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid #1a365d; margin: 0 auto; display: block; box-shadow: 0 4px 12px rgba(0,0,0,0.18);">`;
    } else {
        avatarBox.innerHTML = `<div style="width: 130px; height: 130px; border-radius: 50%; background: #2b6cb0; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 46px; font-weight: bold; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.18);">${esp.nombre.charAt(0).toUpperCase()}</div>`;
    }

    const contConsultas = document.getElementById('detalle-esp-consultas');
    contConsultas.innerHTML = '<span style="color:#a0aec0; font-size:12px;">Cargando...</span>';

    fetch(`/sigas/modulos/api_especialistas.php?especialista_id=${esp.id}`)
        .then(res => res.json())
        .then(ids => {
            contConsultas.innerHTML = '';
            if (ids.length === 0) {
                contConsultas.innerHTML = '<span style="color:#a0aec0; font-size:12px;">Sin atenciones asignadas.</span>';
                return;
            }
            ids.forEach(id => {
                const labelRef = document.querySelector(`label[for="tipo_${id}"]`);
                if (labelRef) {
                    const tag = document.createElement('span');
                    tag.style.cssText = 'background: #edf2f7; color: #2d3748; padding: 4px 9px; border-radius: 4px; font-size: 12px; border: 1px solid #e2e8f0;';
                    tag.textContent = labelRef.querySelector('span').textContent;
                    contConsultas.appendChild(tag);
                }
            });
        });

    document.getElementById('modal-detalle-esp').style.display = 'flex';
}

document.getElementById('btn-detalle-editar-esp').addEventListener('click', () => {
    cerrarModalDetalleEsp();
    editarEspecialista(espActivo);
});

document.getElementById('btn-detalle-eliminar-esp').addEventListener('click', () => {
    if (!espActivo) return;
    cerrarModalDetalleEsp();
    abrirModalEliminarEsp(espActivo.id, espActivo.nombre);
});

document.getElementById('btn-nuevo-esp').addEventListener('click', () => {
    document.getElementById('form-esp').reset();
    document.getElementById('esp_id').value = '';
    document.getElementById('esp_foto_recortada_base64').value = '';
    document.getElementById('modal-esp-titulo').textContent = 'Nuevo Especialista';
    document.querySelectorAll('input[name="atenciones[]"]').forEach(chk => chk.checked = false);
    document.getElementById('circulo-preview-esp').innerHTML = '<span style="font-size: 11px; color: #a0aec0; text-align: center;">Sin foto</span>';
    document.getElementById('modal-esp').style.display = 'flex';
});

function editarEspecialista(esp) {
    document.getElementById('form-esp').reset();
    document.getElementById('esp_id').value = esp.id;
    document.getElementById('esp_foto_recortada_base64').value = '';
    document.getElementById('modal-esp-titulo').textContent = 'Editar Especialista';
    document.getElementById('esp_nombre').value = esp.nombre;
    document.getElementById('esp_rut').value = esp.rut;
    inputRutEsp.dispatchEvent(new Event('input'));
    document.getElementById('esp_cargo').value = esp.cargo_titulo;

    const pBox = document.getElementById('circulo-preview-esp');
    if (esp.foto_perfil) {
        pBox.innerHTML = `<img src="/sigas/${esp.foto_perfil}" style="width: 100%; height: 100%; object-fit: cover;">`;
    } else {
        pBox.innerHTML = `<div style="width: 100%; height: 100%; background: #2b6cb0; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold;">${esp.nombre.charAt(0).toUpperCase()}</div>`;
    }

    fetch(`/sigas/modulos/api_especialistas.php?especialista_id=${esp.id}`)
        .then(res => res.json())
        .then(ids => {
            document.querySelectorAll('input[name="atenciones[]"]').forEach(chk => {
                chk.checked = ids.includes(parseInt(chk.value)) || ids.includes(String(chk.value));
            });
            document.getElementById('modal-esp').style.display = 'flex';
        });
}

// CROPPER CIRCULAR
let imgOriginalEsp = new Image();
let posXEsp = 0, posYEsp = 0, scaleEsp = 1, isDraggingEsp = false, startXEsp = 0, startYEsp = 0;
const canvasEsp = document.getElementById('crop-canvas-esp');
const ctxEsp = canvasEsp.getContext('2d');
const inputFotoEsp = document.getElementById('input_foto_esp');
const zoomSliderEsp = document.getElementById('crop-zoom-esp');
const viewportEsp = document.getElementById('crop-viewport-esp');

inputFotoEsp.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        imgOriginalEsp = new Image();
        imgOriginalEsp.onload = () => {
            scaleEsp = Math.max(canvasEsp.width / imgOriginalEsp.width, canvasEsp.height / imgOriginalEsp.height);
            zoomSliderEsp.value = scaleEsp;
            zoomSliderEsp.min = scaleEsp * 0.7;
            zoomSliderEsp.max = scaleEsp * 3.5;
            posXEsp = (canvasEsp.width - imgOriginalEsp.width * scaleEsp) / 2;
            posYEsp = (canvasEsp.height - imgOriginalEsp.height * scaleEsp) / 2;
            dibujarCanvasEsp();
            document.getElementById('modal-crop-esp').style.display = 'flex';
        };
        imgOriginalEsp.src = event.target.result;
    };
    reader.readAsDataURL(file);
});

function dibujarCanvasEsp() {
    ctxEsp.clearRect(0, 0, canvasEsp.width, canvasEsp.height);
    ctxEsp.drawImage(imgOriginalEsp, posXEsp, posYEsp, imgOriginalEsp.width * scaleEsp, imgOriginalEsp.height * scaleEsp);
}

viewportEsp.addEventListener('mousedown', (e) => {
    isDraggingEsp = true;
    startXEsp = e.clientX - posXEsp;
    startYEsp = e.clientY - posYEsp;
    viewportEsp.style.cursor = 'grabbing';
});
window.addEventListener('mousemove', (e) => {
    if (!isDraggingEsp) return;
    posXEsp = e.clientX - startXEsp;
    posYEsp = e.clientY - startYEsp;
    dibujarCanvasEsp();
});
window.addEventListener('mouseup', () => {
    isDraggingEsp = false;
    viewportEsp.style.cursor = 'grab';
});

zoomSliderEsp.addEventListener('input', (e) => {
    const centroX = canvasEsp.width / 2;
    const centroY = canvasEsp.height / 2;
    const nuevoScale = parseFloat(e.target.value);
    posXEsp = centroX - (centroX - posXEsp) * (nuevoScale / scaleEsp);
    posYEsp = centroY - (centroY - posYEsp) * (nuevoScale / scaleEsp);
    scaleEsp = nuevoScale;
    dibujarCanvasEsp();
});

function cancelarRecorteEsp() {
    document.getElementById('modal-crop-esp').style.display = 'none';
    inputFotoEsp.value = '';
}

function confirmarRecorteEsp() {
    const finalCanvas = document.createElement('canvas');
    finalCanvas.width = 300;
    finalCanvas.height = 300;
    const finalCtx = finalCanvas.getContext('2d');

    finalCtx.beginPath();
    finalCtx.arc(150, 150, 150, 0, Math.PI * 2);
    finalCtx.closePath();
    finalCtx.clip();

    finalCtx.drawImage(canvasEsp, 0, 0, 300, 300);

    const base64Final = finalCanvas.toDataURL('image/png');
    document.getElementById('esp_foto_recortada_base64').value = base64Final;
    document.getElementById('circulo-preview-esp').innerHTML = `<img src="${base64Final}" style="width: 100%; height: 100%; object-fit: cover;">`;
    document.getElementById('modal-crop-esp').style.display = 'none';
}

document.getElementById('form-esp').addEventListener('submit', (e) => {
    e.preventDefault();
    const chks = Array.from(document.querySelectorAll('input[name="atenciones[]"]:checked')).map(c => c.value);
    if (chks.length === 0) {
        alert('Debe seleccionar al menos un tipo de consulta.');
        return;
    }

    const formData = new FormData(document.getElementById('form-esp'));

    fetch('/sigas/modulos/api_especialistas.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    })
    .catch(() => alert('Error al guardar especialista.'));
});

let idEspEliminar = null;
function abrirModalEliminarEsp(id, nombre) {
    idEspEliminar = id;
    document.getElementById('clave_autorizacion_esp').value = '';
    document.getElementById('txt-eliminar-mensaje-esp').textContent = `¿Está seguro de eliminar al especialista "${nombre}"?`;
    document.getElementById('modal-confirmar-eliminar-esp').style.display = 'flex';
}

document.getElementById('btn-ejecutar-baja-esp').addEventListener('click', () => {
    const pass = document.getElementById('clave_autorizacion_esp').value.trim();
    if (!pass) {
        alert('Debe ingresar su contraseña para confirmar.');
        return;
    }

    fetch('/sigas/modulos/api_especialistas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ especialista_id: idEspEliminar, password: pass })
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);
        if (res.success) location.reload();
    })
    .catch(() => alert('Error al eliminar especialista.'));
});
</script>

<?php include '../includes/footer.php'; ?>