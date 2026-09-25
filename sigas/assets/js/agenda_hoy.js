// CONTROLADOR DE LA VISTA PRINCIPAL DEL DÍA CON SELECCIÓN DE SEGMENTO
let citaSeleccionadaId = null;
const ALTURA_HORA_PX = 70; // 60 minutos = 70px

document.addEventListener('DOMContentLoaded', () => {
    cargarCitasHoy();
    configurarModalAcciones();
    configurarSeleccionSegmentoBox();
});

// CALCULA LA POSICIÓN VERTICAL SEGÚN LA HORA (08:00 a 13:00 y 14:00 a 18:00)
function calcularTopEnPixeles(horaStr) {
    const [h, m] = horaStr.split(':').map(Number);
    let indiceBloque = -1;

    if (h >= 8 && h < 13) {
        indiceBloque = h - 8;
    } else if (h >= 14 && h <= 18) {
        indiceBloque = (h - 14) + 5; // Salta el almuerzo de 13 a 14
    }

    if (indiceBloque === -1) return 0;
    const pixelesPorMinuto = ALTURA_HORA_PX / 60;
    return (indiceBloque * ALTURA_HORA_PX) + (m * pixelesPorMinuto);
}

// CALCULA LA ALTURA SEGÚN LA DURACIÓN
function calcularAltoEnPixeles(inicioStr, finStr) {
    const [h1, m1] = inicioStr.split(':').map(Number);
    const [h2, m2] = finStr.split(':').map(Number);

    const minutosTotal = (h2 * 60 + m2) - (h1 * 60 + m1);
    const pixelesPorMinuto = ALTURA_HORA_PX / 60;
    return Math.max(minutosTotal * pixelesPorMinuto, 20);
}

// CARGA Y POSICIONA LAS CITAS DEL DÍA
function cargarCitasHoy() {
    const inputFecha = document.getElementById('fecha');
    const fecha = inputFecha ? inputFecha.value : new Date().toISOString().split('T')[0];

    fetch(`/sigas/modulos/api_citas.php?accion=dia&fecha=${encodeURIComponent(fecha)}`)
        .then(async res => {
            if (!res.ok) {
                throw new Error(`Error HTTP: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            // Limpia los cuerpos de los boxes antes de dibujar
            document.querySelectorAll('.columna-box-cuerpo').forEach(c => c.innerHTML = '');

            // VALIDACIÓN DE SEGURIDAD:
            // Si data no es un Array (ej: vino un objeto de error), lo convertimos a array vacío o mostramos el error
            if (!Array.isArray(data)) {
                if (data && data.message) {
                    console.warn('Aviso del servidor:', data.message);
                }
                return; // Evita ejecutar .forEach() sobre un objeto
            }

            const citas = data;

            citas.forEach(cita => {
                const cuerpoBox = document.getElementById(`box-cuerpo-${cita.box_id}`);
                if (!cuerpoBox) return;

                const topPx = calcularTopEnPixeles(cita.hora_inicio);
                const heightPx = calcularAltoEnPixeles(cita.hora_inicio, cita.hora_fin);

                const bloque = document.createElement('div');
                bloque.className = 'bloque-cita';
                bloque.style.backgroundColor = cita.color_hex || '#4A5568';
                bloque.style.top = `${topPx}px`;
                bloque.style.height = `${heightPx}px`;

                // Detectar sobrecupo en el mismo horario y box
                const haySobrecupoCoincidente = citas.some(otra => 
                    otra.box_id === cita.box_id &&
                    otra.id !== cita.id &&
                    (otra.es_sobrecupo == 1 || cita.es_sobrecupo == 1) &&
                    (otra.hora_inicio < cita.hora_fin && otra.hora_fin > cita.hora_inicio)
                );

                if (cita.es_sobrecupo == 1) {
                    bloque.classList.add('sobrecupo');
                } else if (haySobrecupoCoincidente) {
                    bloque.classList.add('con-sobrecupo');
                }

                const etiquetaSobrecupo = cita.es_sobrecupo == 1 ? ' [S]' : '';
                bloque.textContent = `${cita.nombre} ${cita.apellidos}${etiquetaSobrecupo}`;
                bloque.title = `${cita.hora_inicio.substring(0, 5)} - ${cita.hora_fin.substring(0, 5)}: ${cita.nombre} ${cita.apellidos} [${cita.tipo_atencion}]`;

                bloque.addEventListener('click', (e) => {
                    e.stopPropagation();
                    abrirModalCita(cita);
                });

                cuerpoBox.appendChild(bloque);
            });
        })
        .catch(err => console.error('Error cargando citas de hoy:', err));
}

// SELECCIONAR UN SEGMENTO HORARIO EN LA COLUMNA DE BOX Y AGENDAR EN ESE HORARIO
function configurarSeleccionSegmentoBox() {
    document.querySelectorAll('.columna-box-cuerpo').forEach(cuerpo => {
        cuerpo.style.cursor = 'pointer';
        cuerpo.title = 'Haga clic en un segmento libre para agendar una hora';

        cuerpo.addEventListener('click', (e) => {
            if (e.target.classList.contains('bloque-cita')) return;

            const rect = cuerpo.getBoundingClientRect();
            const yClick = e.clientY - rect.top;

            const totalMinutos = Math.floor((yClick / ALTURA_HORA_PX) * 60);
            let horas = 8 + Math.floor(totalMinutos / 60);
            let minutos = totalMinutos % 60;

            // Salto de colación (13:00 a 14:00)
            if (horas >= 13) {
                horas += 1;
            }

            // Redondeo a bloques de 10 minutos
            minutos = Math.floor(minutos / 10) * 10;
            const horaStr = `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}`;
            
            const boxId = cuerpo.id.replace('box-cuerpo-', '');
            const fecha = document.getElementById('fecha') ? document.getElementById('fecha').value : new Date().toISOString().split('T')[0];

            // Redirección directa al formulario con los parámetros listos
            window.location.href = `/sigas/vistas/agendar.php?box_id=${boxId}&fecha=${encodeURIComponent(fecha)}&hora=${encodeURIComponent(horaStr)}`;
        });
    });
}

// MODAL DE DETALLE Y BAJA CON CLAVE
function abrirModalCita(cita) {
    citaSeleccionadaId = cita.id;
    const modal = document.getElementById('modal-cita');
    const datosContenedor = document.getElementById('modal-datos-paciente');
    const areaClave = document.getElementById('area-confirmar-clave');

    areaClave.style.display = 'none';
    document.getElementById('clave_admin_confirmar').value = '';

    datosContenedor.innerHTML = `
        <p><strong>Paciente / Ocupante:</strong> ${cita.nombre} ${cita.apellidos}</p>
        <p><strong>RUT:</strong> ${cita.rut}</p>
        <p><strong>Contacto:</strong> ${cita.telefono} | ${cita.correo}</p>
        <p><strong>Unidad / Previsión:</strong> ${cita.seccion_unidad} (${cita.prevision})</p>
        <p><strong>Atención / Motivo:</strong> ${cita.tipo_atencion}</p>
        <p><strong>Horario:</strong> ${cita.hora_inicio.substring(0, 5)} a ${cita.hora_fin.substring(0, 5)}</p>
        ${cita.es_sobrecupo == 1 ? '<p style="color:#c53030; font-weight:bold;">Atención en Sobrecupo</p>' : ''}
    `;

    modal.style.display = 'flex';
}

function configurarModalAcciones() {
    const modal = document.getElementById('modal-cita');
    const btnCerrar = document.getElementById('btn-cerrar-modal');
    const btnAbrirEliminar = document.getElementById('btn-abrir-eliminar');
    const btnCancelarBaja = document.getElementById('btn-cancelar-baja');
    const btnConfirmarBaja = document.getElementById('btn-confirmar-baja');
    const areaClave = document.getElementById('area-confirmar-clave');

    if (!modal) return;

    btnCerrar.addEventListener('click', () => modal.style.display = 'none');
    btnAbrirEliminar.addEventListener('click', () => areaClave.style.display = 'block');
    btnCancelarBaja.addEventListener('click', () => areaClave.style.display = 'none');

    btnConfirmarBaja.addEventListener('click', () => {
        const password = document.getElementById('clave_admin_confirmar').value;
        if (!password) {
            alert('Debe ingresar su clave de Administrador.');
            return;
        }

        fetch('/sigas/modulos/api_citas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cita_id: citaSeleccionadaId, password: password })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert(res.message);
                modal.style.display = 'none';
                cargarCitasHoy();
            } else {
                alert(res.message);
            }
        })
        .catch(err => alert('Error al eliminar la cita.'));
    });
}