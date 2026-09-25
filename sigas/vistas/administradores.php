<?php
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

$admins =$pdo->query("SELECT id, rut, nombre, foto_perfil, created_at FROM administradores ORDER BY nombre ASC")->fetchAll();
?>

<!-- CONTENEDOR PRINCIPAL CENTRADO -->
<div style="background-color: #f4f6f9; min-height: calc(100vh - 75px); padding: 30px 20px;">
    <div style="max-width: 980px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 25px 30px; border: 1px solid #e2e8f0;">
        
        <!-- CABECERA -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #edf2f7; padding-bottom: 16px; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 21px; color: #1a365d; margin: 0; font-weight: bold;">Gestión de Administradores</h2>
                <p style="font-size: 13px; color: #718096; margin: 4px 0 0 0;">Haga clic sobre un administrador para ver detalles y gestionar su cuenta.</p>
            </div>
            <button id="btn-nuevo-admin" class="btn btn-primary" style="font-size: 13.5px; padding: 9px 18px; font-weight: bold; border-radius: 5px;">+ Nuevo Administrador</button>
        </div>

        <!-- TABLA -->
        <div style="border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e0;">
            <table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background-color: #1a365d; color: #ffffff; text-align: left;">
                        <th style="width: 14%; padding: 12px 14px; text-align: center;">Perfil</th>
                        <th style="width: 22%; padding: 12px 14px;">RUT</th>
                        <th style="width: 44%; padding: 12px 14px;">Nombre Completo</th>
                        <th style="width: 20%; padding: 12px 14px; text-align: right;">Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 25px; color: #718096;">No hay administradores registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($admins as$adm): ?>
                            <tr class="fila-admin" onclick='abrirFichaAdmin(<?= htmlspecialchars(json_encode($adm), ENT_QUOTES, "UTF-8") ?>)' 
                                style="cursor: pointer; border-bottom: 1px solid #edf2f7; transition: background 0.15s ease;">
                                <td style="padding: 10px 14px; text-align: center; vertical-align: middle;">
                                    <?php if (!empty($adm['foto_perfil']) && file_exists(__DIR__ . '/../' .$adm['foto_perfil'])): ?>
                                        <img src="/sigas/<?= htmlspecialchars($adm['foto_perfil']) ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #cbd5e0; display: block; margin: 0 auto; box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #4a5568; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; margin: 0 auto; box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
                                            <?= strtoupper(substr($adm['nombre'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px 14px; font-weight: bold; color: #2d3748; vertical-align: middle;">
                                    <?= htmlspecialchars($adm['rut']) ?>
                                </td>
                                <td style="padding: 10px 14px; color: #1a202c; font-weight: 500; font-size: 14.5px; vertical-align: middle;">
                                    <?= htmlspecialchars($adm['nombre']) ?>
                                </td>
                                <td style="padding: 10px 14px; color: #718096; font-size: 13.5px; text-align: right; vertical-align: middle;">
                                    <?= htmlspecialchars(substr($adm['created_at'] ?? '', 0, 10)) ?>
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
.fila-admin:hover {
    background-color: #f7fafc !important;
}
</style>

<!-- POP-UP DETALLE ADMINISTRADOR -->
<div id="modal-detalle-admin" class="modal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
    <div class="modal-contenido" style="width: 100%; max-width: 420px; padding: 26px 24px; text-align: center; position: relative; border-radius: 8px; background: #fff;">
        <button type="button" onclick="cerrarModalDetalleAdmin()" style="position: absolute; top: 12px; right: 14px; background: none; border: none; font-size: 24px; color: #718096; cursor: pointer;">&times;</button>
        
        <div id="detalle-admin-avatar-box" style="margin-bottom: 16px;"></div>
        <h3 id="detalle-admin-nombre" style="margin: 0 0 4px 0; color: #1a365d; font-size: 19px; font-weight: bold;"></h3>
        <p style="margin: 0 0 16px 0; color: #718096; font-size: 13.5px;">Rol: Administrador del Sistema</p>
        
        <div style="background: #f8fafc; border: 1px solid #edf2f7; border-radius: 6px; padding: 14px 18px; text-align: left; font-size: 13.5px; margin-bottom: 20px;">
            <p style="margin: 5px 0;"><strong>RUT:</strong> <span id="detalle-admin-rut" style="color: #2d3748;"></span></p>
            <p style="margin: 5px 0;"><strong>Fecha de Registro:</strong> <span id="detalle-admin-creado" style="color: #4a5568;"></span></p>
        </div>

        <div style="display: flex; justify-content: space-between; gap: 10px;">
            <button type="button" class="btn btn-danger" id="btn-detalle-eliminar-admin" style="padding: 8px 16px; font-size: 13px;">Eliminar</button>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-light" onclick="cerrarModalDetalleAdmin()" style="padding: 8px 14px; font-size: 13px;">Cerrar</button>
                <button type="button" class="btn btn-secondary" id="btn-detalle-editar-admin" style="padding: 8px 16px; font-size: 13px;">Editar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FORMULARIO CREAR / EDITAR -->
<div id="modal-admin" class="modal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
    <div class="modal-contenido" style="width: 100%; max-width: 440px; padding: 25px 28px; position: relative; border-radius: 8px; background: #fff;">
        <button type="button" onclick="cerrarModalAdmin()" style="position: absolute; top: 14px; right: 16px; background: none; border: none; font-size: 24px; color: #718096; cursor: pointer;">&times;</button>
        <h3 id="modal-admin-titulo" style="margin: 0 0 16px 0; font-size: 18px; color: #1a365d; border-bottom: 1px solid #edf2f7; padding-bottom: 10px;">Nuevo Administrador</h3>

        <form id="form-admin">
            <input type="hidden" id="admin_id" name="id">
            <input type="hidden" id="foto_recortada_base64" name="foto_recortada">

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 4px; color: #2d3748; display: block;">Nombre Completo</label>
                <input type="text" id="admin_nombre" name="nombre" required style="height: 36px; font-size: 13.5px; width: 100%; border-radius: 5px; border: 1px solid #cbd5e0; padding: 6px 10px; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 4px; color: #2d3748; display: block;">RUT</label>
                <input type="text" id="admin_rut" name="rut" required placeholder="12.345.678-9" maxlength="12" autocomplete="off" style="height: 36px; font-size: 13.5px; width: 100%; border-radius: 5px; border: 1px solid #cbd5e0; padding: 6px 10px; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 4px; color: #2d3748; display: block;">Contraseña</label>
                <input type="password" id="admin_password" name="password" placeholder="••••••••" style="height: 36px; font-size: 13.5px; width: 100%; border-radius: 5px; border: 1px solid #cbd5e0; padding: 6px 10px; box-sizing: border-box;">
                <small id="help-password" style="font-size: 11.5px; color: #718096; display: none;">Deje en blanco para conservar la contraseña actual.</small>
            </div>

            <!-- PREVIEW CIRCULAR EN FORMULARIO -->
            <div class="form-group" style="margin-bottom: 18px;">
                <label style="font-size: 13px; font-weight: bold; margin-bottom: 6px; color: #2d3748; display: block;">Foto de Perfil</label>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div id="circulo-preview-admin" style="width: 75px; height: 75px; border-radius: 50%; background: #edf2f7; border: 2px dashed #cbd5e0; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <span style="font-size: 11px; color: #a0aec0; text-align: center;">Sin foto</span>
                    </div>
                    <div>
                        <input type="file" id="input_foto_admin" accept="image/*" style="font-size: 12.5px;">
                        <small style="font-size: 11px; color: #718096; display: block; margin-top: 4px;">Seleccione una imagen para ajustar el encuadre.</small>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #edf2f7; padding-top: 15px;">
                <button type="button" class="btn btn-light" onclick="cerrarModalAdmin()" style="padding: 8px 16px; font-size: 13px;">Cancelar</button>
                <button type="submit" id="btn-guardar-admin" class="btn btn-primary" style="padding: 8px 18px; font-size: 13px;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CROPPER CIRCULAR -->
<div id="modal-crop" class="modal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.7); z-index: 2000;">
    <div style="background: #ffffff; width: 100%; max-width: 420px; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <h4 style="margin: 0 0 10px 0; color: #1a365d; font-size: 16px;">Ajustar Foto de Perfil</h4>
        <p style="font-size: 12px; color: #718096; margin: 0 0 12px 0;">Arrastra con el ratón para centrar y mueve la barra para hacer zoom:</p>

        <div style="position: relative; width: 280px; height: 280px; margin: 0 auto; background: #2d3748; overflow: hidden; border-radius: 6px; cursor: grab;" id="crop-viewport">
            <canvas id="crop-canvas" width="280" height="280"></canvas>
            <div style="position: absolute; inset: 0; pointer-events: none; border-radius: 50%; box-shadow: 0 0 0 9999px rgba(0,0,0,0.55); border: 2px solid #3182ce;"></div>
        </div>

        <div style="margin: 15px auto 10px auto; width: 280px; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 12px; color: #718096;">Zoom:</span>
            <input type="range" id="crop-zoom" min="0.5" max="3" step="0.05" value="1" style="flex: 1;">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 15px;">
            <button type="button" class="btn btn-light" onclick="cancelarRecorte()" style="padding: 6px 12px; font-size: 12.5px;">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="confirmarRecorte()" style="padding: 6px 15px; font-size: 12.5px;">Aplicar Recorte</button>
        </div>
    </div>
</div>

<!-- MODAL PROTEGIDO POR CLAVE PARA ELIMINAR -->
<div id="modal-confirmar-eliminar" class="modal" style="display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
    <div class="modal-contenido" style="width: 100%; max-width: 390px; padding: 22px; background: #fff; border-radius: 8px;">
        <h3 style="margin-top: 0; color: #c53030; font-size: 17px;">Confirmar Eliminación</h3>
        <p id="txt-eliminar-mensaje" style="font-size: 13.5px; margin-bottom: 14px; color: #4a5568;"></p>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label style="font-size: 12.5px; font-weight: bold; display: block; margin-bottom: 4px;">Ingrese su contraseña de Administrador:</label>
            <input type="password" id="clave_autorizacion" placeholder="••••••••" style="height: 34px; font-size: 13px; width: 100%; border: 1px solid #cbd5e0; border-radius: 4px; padding: 4px 8px; box-sizing: border-box;">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="btn btn-light" onclick="cerrarModalesConfirmar()" style="padding: 6px 12px; font-size: 12.5px;">Cancelar</button>
            <button type="button" id="btn-ejecutar-baja" class="btn btn-danger" style="padding: 6px 14px; font-size: 12.5px;">Confirmar Baja</button>
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

const inputRutAdmin = document.getElementById('admin_rut');
aplicarMascaraRut(inputRutAdmin);

let adminActivo = null;

function cerrarModalDetalleAdmin() { document.getElementById('modal-detalle-admin').style.display = 'none'; }
function cerrarModalAdmin() { document.getElementById('modal-admin').style.display = 'none'; }
function cerrarModalesConfirmar() { document.getElementById('modal-confirmar-eliminar').style.display = 'none'; }

function abrirFichaAdmin(adm) {
    adminActivo = adm;
    document.getElementById('detalle-admin-nombre').textContent = adm.nombre;
    document.getElementById('detalle-admin-rut').textContent = adm.rut;
    document.getElementById('detalle-admin-creado').textContent = (adm.created_at || '').substring(0, 10);

    const avatarBox = document.getElementById('detalle-admin-avatar-box');
    if (adm.foto_perfil) {
        avatarBox.innerHTML = `<img src="/sigas/${adm.foto_perfil}" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #1a365d; margin: 0 auto; display: block; box-shadow: 0 4px 12px rgba(0,0,0,0.18);">`;
    } else {
        avatarBox.innerHTML = `<div style="width: 110px; height: 110px; border-radius: 50%; background: #4a5568; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 38px; font-weight: bold; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.18);">${adm.nombre.charAt(0).toUpperCase()}</div>`;
    }

    document.getElementById('modal-detalle-admin').style.display = 'flex';
}

document.getElementById('btn-detalle-editar-admin').addEventListener('click', () => {
    cerrarModalDetalleAdmin();
    editarAdmin(adminActivo);
});

document.getElementById('btn-detalle-eliminar-admin').addEventListener('click', () => {
    if (!adminActivo) return;
    cerrarModalDetalleAdmin();
    abrirModalEliminar(adminActivo.id, adminActivo.nombre);
});

document.getElementById('btn-nuevo-admin').addEventListener('click', () => {
    document.getElementById('form-admin').reset();
    document.getElementById('admin_id').value = '';
    document.getElementById('foto_recortada_base64').value = '';
    document.getElementById('modal-admin-titulo').textContent = 'Nuevo Administrador';
    document.getElementById('admin_password').required = true;
    document.getElementById('help-password').style.display = 'none';
    document.getElementById('circulo-preview-admin').innerHTML = '<span style="font-size: 11px; color: #a0aec0; text-align: center;">Sin foto</span>';
    document.getElementById('modal-admin').style.display = 'flex';
});

function editarAdmin(adm) {
    document.getElementById('form-admin').reset();
    document.getElementById('admin_id').value = adm.id;
    document.getElementById('foto_recortada_base64').value = '';
    document.getElementById('modal-admin-titulo').textContent = 'Editar Administrador';
    document.getElementById('admin_nombre').value = adm.nombre;
    document.getElementById('admin_rut').value = adm.rut;
    inputRutAdmin.dispatchEvent(new Event('input'));
    document.getElementById('admin_password').required = false;
    document.getElementById('help-password').style.display = 'block';

    const pBox = document.getElementById('circulo-preview-admin');
    if (adm.foto_perfil) {
        pBox.innerHTML = `<img src="/sigas/${adm.foto_perfil}" style="width: 100%; height: 100%; object-fit: cover;">`;
    } else {
        pBox.innerHTML = `<div style="width: 100%; height: 100%; background: #4a5568; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold;">${adm.nombre.charAt(0).toUpperCase()}</div>`;
    }
    document.getElementById('modal-admin').style.display = 'flex';
}

// CROPPER CIRCULAR
let imgOriginal = new Image();
let posX = 0, posY = 0, scale = 1, isDragging = false, startX = 0, startY = 0;
const canvas = document.getElementById('crop-canvas');
const ctx = canvas.getContext('2d');
const inputFoto = document.getElementById('input_foto_admin');
const zoomSlider = document.getElementById('crop-zoom');
const viewport = document.getElementById('crop-viewport');

inputFoto.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        imgOriginal = new Image();
        imgOriginal.onload = () => {
            scale = Math.max(canvas.width / imgOriginal.width, canvas.height / imgOriginal.height);
            zoomSlider.value = scale;
            zoomSlider.min = scale * 0.7;
            zoomSlider.max = scale * 3.5;
            posX = (canvas.width - imgOriginal.width * scale) / 2;
            posY = (canvas.height - imgOriginal.height * scale) / 2;
            dibujarCanvas();
            document.getElementById('modal-crop').style.display = 'flex';
        };
        imgOriginal.src = event.target.result;
    };
    reader.readAsDataURL(file);
});

function dibujarCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(imgOriginal, posX, posY, imgOriginal.width * scale, imgOriginal.height * scale);
}

viewport.addEventListener('mousedown', (e) => {
    isDragging = true;
    startX = e.clientX - posX;
    startY = e.clientY - posY;
    viewport.style.cursor = 'grabbing';
});
window.addEventListener('mousemove', (e) => {
    if (!isDragging) return;
    posX = e.clientX - startX;
    posY = e.clientY - startY;
    dibujarCanvas();
});
window.addEventListener('mouseup', () => {
    isDragging = false;
    viewport.style.cursor = 'grab';
});

zoomSlider.addEventListener('input', (e) => {
    const centroX = canvas.width / 2;
    const centroY = canvas.height / 2;
    const nuevoScale = parseFloat(e.target.value);
    posX = centroX - (centroX - posX) * (nuevoScale / scale);
    posY = centroY - (centroY - posY) * (nuevoScale / scale);
    scale = nuevoScale;
    dibujarCanvas();
});

function cancelarRecorte() {
    document.getElementById('modal-crop').style.display = 'none';
    inputFoto.value = '';
}

function confirmarRecorte() {
    const finalCanvas = document.createElement('canvas');
    finalCanvas.width = 300;
    finalCanvas.height = 300;
    const finalCtx = finalCanvas.getContext('2d');

    finalCtx.beginPath();
    finalCtx.arc(150, 150, 150, 0, Math.PI * 2);
    finalCtx.closePath();
    finalCtx.clip();

    finalCtx.drawImage(canvas, 0, 0, 300, 300);

    const base64Final = finalCanvas.toDataURL('image/png');
    document.getElementById('foto_recortada_base64').value = base64Final;
    document.getElementById('circulo-preview-admin').innerHTML = `<img src="${base64Final}" style="width: 100%; height: 100%; object-fit: cover;">`;
    document.getElementById('modal-crop').style.display = 'none';
}

// GUARDAR: LLAMADA CORREGIDA A api_administradores.php?accion=guardar
document.getElementById('form-admin').addEventListener('submit', (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar-admin');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const formData = new FormData(document.getElementById('form-admin'));

    fetch('/sigas/modulos/api_administradores.php?accion=guardar', {
        method: 'POST',
        body: formData
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Error al procesar administrador');
        }
        return data;
    })
    .then(data => {
        alert(data.message);
        location.reload();
    })
    .catch(err => {
        alert('Aviso: ' + err.message);
        btn.disabled = false;
        btn.textContent = 'Guardar';
    });
});

// ELIMINAR: LLAMADA CORREGIDA A api_administradores.php?accion=eliminar
let idAdminEliminar = null;
function abrirModalEliminar(id, nombre) {
    idAdminEliminar = id;
    document.getElementById('clave_autorizacion').value = '';
    document.getElementById('txt-eliminar-mensaje').textContent = `¿Está seguro de eliminar al administrador "${nombre}"?`;
    document.getElementById('modal-confirmar-eliminar').style.display = 'flex';
}

document.getElementById('btn-ejecutar-baja').addEventListener('click', () => {
    const pass = document.getElementById('clave_autorizacion').value.trim();
    if (!pass) {
        alert('Debe ingresar su contraseña para confirmar.');
        return;
    }

    fetch('/sigas/modulos/api_administradores.php?accion=eliminar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ admin_id: idAdminEliminar, password: pass })
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);
        if (res.success) location.reload();
    })
    .catch(() => alert('Error al eliminar administrador.'));
});
</script>

<?php include '../includes/footer.php'; ?>