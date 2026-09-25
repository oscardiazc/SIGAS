// CONTROLADOR DE AGENDA SEMANAL POR BOX CON POSICIONAMIENTO HORARIO
let citaBoxSeleccionadaId = null;
const ALTURA_HORA_PX = 70; // 60 minutos = 70px

document.addEventListener('DOMContentLoaded', () => {
    cargarCitasSemana();
    configurarModalSemanal();
    configurarSeleccionSegmentoSemanal();
});

// CALCULA LA POSICION VERTICAL (TOP) SEGUN LA HORA (08:00 a 13:00 y 14:00 a 18:00)
function calcularTopEnPixeles(horaStr) {
    if (!horaStr) return 0;
    const [h, m] = horaStr.split(':').map(Number);
    let indiceBloque = -1;

    if (h >= 8 && h < 13) {
        indiceBloque = h - 8;
    } else if (h >= 14 && h <= 18) {
        indiceBloque = (h - 14) + 5; // Salta colación de 13:00 a 14:00
    }

    if (indiceBloque === -1) return 0;
    const pixelesPorMinuto = ALTURA_HORA_PX / 60;
    return (indiceBloque * ALTURA_HORA_PX) + (m * pixelesPorMinuto);
}

// CALCULA LA ALTURA (HEIGHT) SEGUN LA DURACION
function calcularAltoEnPixeles(inicioStr, finStr) {
    if (!inicioStr || !finStr) return 20;
    const [h1, m1] = inicioStr.split(':').map(Number);
    const [h2, m2] = finStr.split(':').map(Number);

    const minutosTotal = (h2 * 60 + m2) - (h1 * 60 + m1);
    const pixelesPorMinuto = ALTURA_HORA_PX / 60;
    return Math.max(minutosTotal * pixelesPorMinuto, 20);
}

// CARGA LAS CITAS DE LA SEMANA DESDE EL CONTROLADOR UNIFICADO api_citas.php
function cargarCitasSemana() {
    const inputBox = document.getElementById('box_id');
    const inputLunes = document.getElementById('fecha_lunes');
    const inputViernes = document.getElementById('fecha_viernes');

    if (!inputBox || !inputLunes || !inputViernes) return;

    const boxId = inputBox.value;
    const fechaInicio = inputLunes.value;
    const fechaFin = inputViernes.value;

    fetch(`/sigas/modulos/api_citas.php?accion=box_rango&box_id=${encodeURIComponent(boxId)}&fecha_inicio=${encodeURIComponent(fechaInicio)}&fecha_fin=${encodeURIComponent(fechaFin)}`)
        .then(async res => {
            if (!res.ok) {
                throw new Error(`Error HTTP: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            // Limpiar columnas de la semana
            document.querySelectorAll('.columna-box-cuerpo').forEach(c => c.innerHTML = '');

            // Validación de seguridad para que data sea un array iterable
            if (!Array.isArray(data)) {
                console.warn('Respuesta inesperada en vista semanal:', data);
                return;
            }

            const citas = data;

            citas.forEach(cita => {
                // Normalizar formato YYYY-MM-DD para el ID del contenedor
                const fechaLimpia = (cita.fecha || '').split(' ')[0].split('T')[0];
                const contenedorDia = document.getElementById(`col-dia-${fechaLimpia}`);
                if (!contenedorDia) return;

                const topPx = calcularTopEnPixeles(cita.hora_inicio);
                const heightPx = calcularAltoEnPixeles(cita.hora_inicio, cita.hora_fin);

                const bloque = document.createElement('div');
                bloque.className = 'bloque-cita';
                bloque.style.backgroundColor = cita.color_hex || '#4A5568';
                bloque.style.top = `${topPx}px`;
                bloque.style.height = `${heightPx}px`;

                // Detectar si comparte horario con un sobrecupo en el mismo día
                const haySobrecupoCoincidente = citas.some(otra => 
                    otra.fecha === cita.fecha &&
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
                    abrirModalSemanal(cita);
                });

                contenedorDia.appendChild(bloque);
            });
        })
        .catch(err => console.error('Error cargando citas de la semana:', err));
}

// SELECCIONAR UN SEGMENTO DEL HORARIO Y AGENDAR EN ESE DIA Y HORA
function configurarSeleccionSegmentoSemanal() {
    document.querySelectorAll('.columna-box-cuerpo').forEach(cuerpo => {
        cuerpo.style.cursor = 'pointer';
        cuerpo.title = 'Haga clic en un segmento libre para agendar';

        cuerpo.addEventListener('click', (e) => {
            if (e.target.classList.contains('bloque-cita')) return;

            const rect = cuerpo.getBoundingClientRect();
            const yClick = e.clientY - rect.top;

            const totalMinutos = Math.floor((yClick / ALTURA_HORA_PX) * 60);
            let horas = 8 + Math.floor(totalMinutos / 60);
            let minutos = totalMinutos % 60;

            if (horas >= 13) horas += 1; // Salto de almuerzo

            minutos = Math.floor(minutos / 10) * 10;
            const horaStr = `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}`;

            const boxId = document.getElementById('box_id').value;
            const fechaCol = cuerpo.id.replace('col-dia-', '');

            window.location.href = `/sigas/vistas/agendar.php?box_id=${boxId}&fecha=${encodeURIComponent(fechaCol)}&hora=${encodeURIComponent(horaStr)}`;
        });
    });
}

// MODAL DE DETALLE Y ELIMINACIÓN
function abrirModalSemanal(cita) {
    citaBoxSeleccionadaId = cita.id;
    const modal = document.getElementById('modal-cita-semanal');
    const contenedor = document.getElementById('modal-semanal-datos');
    const areaClave = document.getElementById('area-confirmar-clave-semanal');

    areaClave.style.display = 'none';
    document.getElementById('clave_admin_semanal').value = '';

    contenedor.innerHTML = `
        <p><strong>Paciente / Ocupante:</strong> ${cita.nombre} ${cita.apellidos}</p>
        <p><strong>RUT:</strong> ${cita.rut}</p>
        <p><strong>Contacto:</strong> ${cita.telefono || 'Sin teléfono'} | ${cita.correo || 'Sin correo'}</p>
        <p><strong>Unidad / Previsión:</strong> ${cita.seccion_unidad} (${cita.prevision})</p>
        <p><strong>Atención / Motivo:</strong> ${cita.tipo_atencion}</p>
        <p><strong>Fecha y Horario:</strong> ${cita.fecha} de ${cita.hora_inicio.substring(0, 5)} a ${cita.hora_fin.substring(0, 5)}</p>
        ${cita.es_sobrecupo == 1 ? '<p style="color:#c53030; font-weight:bold;">Atención en Sobrecupo</p>' : ''}
    `;

    modal.style.display = 'flex';
}

function configurarModalSemanal() {
    const modal = document.getElementById('modal-cita-semanal');
    const btnCerrar = document.getElementById('btn-cerrar-semanal');
    const btnEliminar = document.getElementById('btn-eliminar-semanal');
    const btnCancelar = document.getElementById('btn-cancelar-baja-semanal');
    const btnConfirmar = document.getElementById('btn-confirmar-baja-semanal');
    const areaClave = document.getElementById('area-confirmar-clave-semanal');

    if (!modal) return;

    btnCerrar.addEventListener('click', () => modal.style.display = 'none');
    btnEliminar.addEventListener('click', () => areaClave.style.display = 'block');
    btnCancelar.addEventListener('click', () => areaClave.style.display = 'none');

    btnConfirmar.addEventListener('click', () => {
        const password = document.getElementById('clave_admin_semanal').value;
        if (!password) {
            alert('Debe ingresar su clave de Administrador.');
            return;
        }

        fetch('/sigas/modulos/api_citas.php?accion=eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cita_id: citaBoxSeleccionadaId, password: password })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                modal.style.display = 'none';
                cargarCitasSemana();
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert('Error al procesar la eliminación.'));
    });
}