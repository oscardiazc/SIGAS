<?php
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';
?>

<!-- Carga de Chart.js para los gráficos reactivos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div style="display: grid; grid-template-columns: 390px 1fr; gap: 20px; max-width: 1350px; margin: 20px auto; padding: 0 15px; height: calc(100vh - 120px);">
    
    <!-- COLUMNA IZQUIERDA: DIRECTORIO Y BUSCADOR -->
    <div style="background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; flex-direction: column; overflow: hidden;">
        <div style="padding: 15px; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #1a365d;">Directorio de Pacientes</h3>
            <input type="text" id="buscar-paciente-input" placeholder="Buscar por RUT, Folio, Nombre o Consulta..." 
                   style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px;">
        </div>
        <div id="lista-pacientes-contenedor" style="flex: 1; overflow-y: auto; padding: 10px;">
            <p style="text-align: center; color: #a0aec0; font-size: 13px;">Cargando pacientes...</p>
        </div>
    </div>

    <!-- COLUMNA DERECHA: FICHA HISTÓRICA, GRÁFICO Y ACCIONES -->
    <div id="panel-detalle-paciente" style="background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 25px; overflow-y: auto;">
        <div id="placeholder-seleccion" style="text-align: center; color: #a0aec0; margin-top: 120px;">
            <h3 style="font-weight: 500;">Seleccione un paciente de la lista para ver su ficha e historial de atenciones.</h3>
        </div>

        <div id="contenido-paciente" style="display: none;">
            <!-- CABECERA: DATOS PERSONALES Y BOTÓN ELIMINAR -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #edf2f7; padding-bottom: 15px; margin-bottom: 20px;">
                <div>
                    <h2 id="pac-nombre" style="margin: 0; color: #2d3748; font-size: 20px;"></h2>
                    <div style="margin-top: 4px;">
                        <span id="pac-rut" style="font-weight: bold; color: #4a5568; font-size: 14px;"></span>
                        <span id="pac-folio" style="margin-left: 10px; background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;"></span>
                    </div>
                </div>
                <div style="text-align: right; font-size: 13px; color: #4a5568; display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <div>
                        <div><strong>Contacto:</strong> <span id="pac-contacto"></span></div>
                        <div><strong>Unidad:</strong> <span id="pac-unidad"></span> | <strong>Previsión:</strong> <span id="pac-prevision"></span></div>
                    </div>
                    <!-- BOTÓN ELIMINAR PACIENTE -->
                    <button id="btn-abrir-eliminar-paciente" class="btn btn-danger" style="background: #e53e3e; color: #fff; border: none; padding: 6px 12px; border-radius: 5px; font-size: 12px; font-weight: bold; cursor: pointer;">
                        Eliminar Paciente
                    </button>
                </div>
            </div>

            <!-- GRÁFICO DE DISTRIBUCIÓN -->
            <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <div style="height: 180px; position: relative;">
                    <canvas id="graficoConsultas"></canvas>
                </div>
                <div>
                    <h4 style="margin: 0 0 6px 0; color: #2d3748; font-size: 14px;">Distribución de Prestaciones Médicas</h4>
                    <p style="font-size: 12px; color: #718096; margin: 0 0 10px 0;">Resumen histórico incluyendo atenciones en Box y derivaciones a Mamografía.</p>
                    <div id="metricas-totales" style="font-size: 13px; font-weight: bold; color: #1a365d;"></div>
                </div>
            </div>

            <!-- HISTORIAL DETALLADO -->
            <h3 style="font-size: 15px; color: #1a365d; margin-bottom: 12px;">Historial de Atenciones</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #1a365d; color: #ffffff; text-align: left;">
                        <th style="padding: 10px; border-radius: 4px 0 0 0;">Fecha y Hora</th>
                        <th style="padding: 10px;">Box</th>
                        <th style="padding: 10px;">Tipo de Atención</th>
                        <th style="padding: 10px;">Especialista</th>
                        <th style="padding: 10px; border-radius: 0 4px 0 0; text-align: center;">Modalidad</th>
                    </tr>
                </thead>
                <tbody id="tabla-historial-cuerpo"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL PROTEGIDO PARA ELIMINAR PACIENTE -->
<div id="modal-eliminar-paciente" class="modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 9999;">
    <div style="background: #ffffff; padding: 25px; border-radius: 8px; width: 100%; max-width: 440px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0; color: #c53030; font-size: 17px; border-bottom: 1px solid #edf2f7; padding-bottom: 10px;">
            Eliminar Paciente
        </h3>
        <p style="font-size: 13px; color: #4a5568; line-height: 1.4;">
            ¿Está seguro de eliminar a <strong id="nombre-paciente-eliminar"></strong>? 
            <br><br>
            <span style="color: #c53030; font-weight: bold;">Advertencia:</span> Esta acción es irreversible y eliminará también todo su historial de citas y derivaciones médicas.
        </p>

        <div style="margin-top: 15px;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #2d3748;">
                Ingrese su Contraseña de Administrador:
            </label>
            <input type="password" id="clave-admin-eliminar-paciente" placeholder="••••••••" 
                   style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 13px; box-sizing: border-box;">
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
            <button id="btn-cancelar-eliminar-paciente" type="button" 
                    style="padding: 8px 14px; background: #e2e8f0; border: none; border-radius: 5px; font-size: 12px; font-weight: bold; color: #4a5568; cursor: pointer;">
                Cancelar
            </button>
            <button id="btn-confirmar-eliminar-paciente" type="button" 
                    style="padding: 8px 16px; background: #e53e3e; border: none; border-radius: 5px; font-size: 12px; font-weight: bold; color: #ffffff; cursor: pointer;">
                Confirmar Eliminación
            </button>
        </div>
    </div>
</div>

<script>
let pacientesGlobal = [];
let chartInstance = null;
let pacienteActualSeleccionado = null;

document.addEventListener('DOMContentLoaded', () => {
    cargarPacientes();
    document.getElementById('buscar-paciente-input').addEventListener('input', filtrarPacientes);
    configurarModalEliminarPaciente();
});

function cargarPacientes() {
    const contenedor = document.getElementById('lista-pacientes-contenedor');
    contenedor.innerHTML = '<p style="text-align: center; color: #a0aec0; font-size: 13px;">Cargando pacientes...</p>';

    fetch('/sigas/modulos/ajax_pacientes_historial.php?accion=listar')
        .then(async res => {
            const data = await res.json();
            if (!res.ok || data.error) {
                throw new Error(data.mensaje || 'Error al obtener pacientes');
            }
            return data;
        })
        .then(data => {
            pacientesGlobal = Array.isArray(data) ? data : [];
            renderizarLista(pacientesGlobal);
        })
        .catch(err => {
            console.error('Error cargando lista de pacientes:', err);
            contenedor.innerHTML = `
                <div style="padding: 15px; text-align: center; color: #c53030; font-size: 12px;">
                    <strong>Error al cargar pacientes:</strong><br>
                    ${err.message}
                </div>
            `;
        });
}

function renderizarLista(lista) {
    const contenedor = document.getElementById('lista-pacientes-contenedor');
    contenedor.innerHTML = '';
    if (lista.length === 0) {
        contenedor.innerHTML = '<p style="text-align: center; color: #a0aec0; font-size: 13px;">No se encontraron pacientes.</p>';
        return;
    }
    lista.forEach(p => {
        const item = document.createElement('div');
        item.style.cssText = 'padding: 10px; border-bottom: 1px solid #edf2f7; cursor: pointer; border-radius: 5px; transition: background 0.15s; margin-bottom: 4px;';
        item.innerHTML = `
            <div style="font-weight: bold; color: #2b6cb0; font-size: 13px;">${p.nombre} ${p.apellidos}</div>
            <div style="font-size: 11px; color: #718096; display: flex; justify-content: space-between; margin-top: 2px;">
                <span>RUT: ${p.rut}</span>
                ${p.folio ? `<span style="font-weight: 600; color: #4a5568;">Folio: ${p.folio}</span>` : ''}
            </div>
        `;
        item.onmouseover = () => item.style.background = '#edf2f7';
        item.onmouseout = () => item.style.background = 'transparent';
        item.onclick = () => verDetallePaciente(p.id);
        contenedor.appendChild(item);
    });
}

function filtrarPacientes() {
    const q = document.getElementById('buscar-paciente-input').value.toLowerCase().trim();
    const filtrados = pacientesGlobal.filter(p => {
        const matchRut = p.rut.toLowerCase().includes(q);
        const matchFolio = p.folio ? p.folio.toLowerCase().includes(q) : false;
        const matchNombre = `${p.nombre} ${p.apellidos}`.toLowerCase().includes(q);
        const matchConsulta = p.atenciones_texto ? p.atenciones_texto.toLowerCase().includes(q) : false;
        return matchRut || matchFolio || matchNombre || matchConsulta;
    });
    renderizarLista(filtrados);
}

function verDetallePaciente(pacienteId) {
    fetch(`/sigas/modulos/ajax_pacientes_historial.php?paciente_id=${pacienteId}`)
        .then(res => res.json())
        .then(data => {
            const p = data.paciente;
            const citas = data.citas;
            pacienteActualSeleccionado = p;

            document.getElementById('placeholder-seleccion').style.display = 'none';
            document.getElementById('contenido-paciente').style.display = 'block';

            document.getElementById('pac-nombre').textContent = `${p.nombre} ${p.apellidos}`;
            document.getElementById('pac-rut').textContent = `RUT: ${p.rut}`;
            document.getElementById('pac-folio').textContent = p.folio ? `Folio: ${p.folio}` : 'Sin Folio';
            document.getElementById('pac-contacto').textContent = `${p.telefono} | ${p.correo || 'Sin correo'}`;
            document.getElementById('pac-unidad').textContent = p.seccion_unidad;
            document.getElementById('pac-prevision').textContent = p.prevision;

            const tbody = document.getElementById('tabla-historial-cuerpo');
            tbody.innerHTML = '';
            
            const conteoTipos = {};

            if (citas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px; color:#a0aec0;">No registra atenciones ni derivaciones activas.</td></tr>';
            } else {
                citas.forEach(c => {
                    conteoTipos[c.tipo_atencion] = (conteoTipos[c.tipo_atencion] || 0) + 1;

                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid #e2e8f0';

                    let horaTexto = '';
                    if (c.origen === 'mamografia') {
                        horaTexto = `<small style="color:#718096;">Derivación externa</small>`;
                    } else {
                        horaTexto = `<small style="color:#718096;">${c.hora_inicio.substring(0,5)} a ${c.hora_fin.substring(0,5)} hrs</small>`;
                    }

                    let modalidadBadge = '';
                    if (c.origen === 'mamografia') {
                        modalidadBadge = `<span style="background:#fed7e2; color:#b83280; font-weight:bold; font-size:11px; padding:2px 6px; border-radius:3px;">Derivación</span>`;
                    } else if (c.es_sobrecupo == 1) {
                        modalidadBadge = `<span style="color:#e53e3e; font-weight:bold; font-size:11px;">SOBRECUPO</span>`;
                    } else {
                        modalidadBadge = `<span style="color:#38a169; font-size:11px;">Normal</span>`;
                    }

                    tr.innerHTML = `
                        <td style="padding: 10px;"><strong>${c.fecha}</strong><br>${horaTexto}</td>
                        <td style="padding: 10px; font-weight: 600; color: #2d3748;">${c.nombre_box}</td>
                        <td style="padding: 10px;"><span style="background:${c.color_hex}; color:#ffffff; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight:bold;">${c.tipo_atencion}</span></td>
                        <td style="padding: 10px;">${c.nombre_especialista}</td>
                        <td style="padding: 10px; text-align: center;">${modalidadBadge}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            document.getElementById('metricas-totales').textContent = `Total de atenciones y derivaciones: ${citas.length}`;
            actualizarGrafico(conteoTipos);
        });
}

function actualizarGrafico(conteoTipos) {
    const labels = Object.keys(conteoTipos);
    const valores = Object.values(conteoTipos);

    if (chartInstance) {
        chartInstance.destroy();
    }

    const ctx = document.getElementById('graficoConsultas').getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: ['#D53F8C', '#2196F3', '#4CAF50', '#FF9800', '#9C27B0', '#00BCD4', '#009688', '#E91E63']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// CONTROLADOR DE CONFIRMACIÓN Y ELIMINACIÓN DE PACIENTE
function configurarModalEliminarPaciente() {
    const modal = document.getElementById('modal-eliminar-paciente');
    const btnAbrir = document.getElementById('btn-abrir-eliminar-paciente');
    const btnCancelar = document.getElementById('btn-cancelar-eliminar-paciente');
    const btnConfirmar = document.getElementById('btn-confirmar-eliminar-paciente');
    const inputClave = document.getElementById('clave-admin-eliminar-paciente');
    const textoNombre = document.getElementById('nombre-paciente-eliminar');

    btnAbrir.addEventListener('click', () => {
        if (!pacienteActualSeleccionado) return;
        textoNombre.textContent = `${pacienteActualSeleccionado.nombre} ${pacienteActualSeleccionado.apellidos} (${pacienteActualSeleccionado.rut})`;
        inputClave.value = '';
        modal.style.display = 'flex';
        inputClave.focus();
    });

    btnCancelar.addEventListener('click', () => {
        modal.style.display = 'none';
        inputClave.value = '';
    });

    btnConfirmar.addEventListener('click', () => {
        const password = inputClave.value.trim();
        if (!password) {
            alert('Debe ingresar su contraseña de Administrador.');
            inputClave.focus();
            return;
        }

        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Eliminando...';

        fetch('/sigas/modulos/ajax_pacientes_historial.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'eliminar_paciente',
                paciente_id: pacienteActualSeleccionado.id,
                password: password
            })
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Error al procesar la eliminación');
            }
            return data;
        })
        .then(data => {
            alert(data.message);
            modal.style.display = 'none';
            pacienteActualSeleccionado = null;

            // Restablecer el panel derecho
            document.getElementById('contenido-paciente').style.display = 'none';
            document.getElementById('placeholder-seleccion').style.display = 'block';

            // Recargar la lista de pacientes
            cargarPacientes();
        })
        .catch(err => {
            alert('Aviso: ' + err.message);
        })
        .finally(() => {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'Confirmar Eliminación';
        });
    });
}
</script>
<?php include '../includes/footer.php'; ?>