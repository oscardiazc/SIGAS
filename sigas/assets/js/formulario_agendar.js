// CONTROLADOR REACTIVO PARA EL FORMULARIO DE AGENDAMIENTO
document.addEventListener('DOMContentLoaded', () => {
    configurarFichaRapida();
    configurarCambioTipoYFecha();
    configurarEnvio();
    configurarToggleMamografia();
    procesarParametrosURL();
});

// 1. AUTOCOMPLETADO DE PACIENTE POR RUT (FICHA RÁPIDA)
function configurarFichaRapida() {
    const inputRut = document.getElementById('buscar_rut');
    if (!inputRut) return;

    inputRut.addEventListener('blur', () => {
        const rut = inputRut.value.trim();
        if (rut.length < 3) return;

        fetch(`/sigas/modulos/ajax_buscar_paciente.php?rut=${encodeURIComponent(rut)}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.encontrado) {
                    const p = data.paciente;
                    if (document.getElementById('paciente_id')) document.getElementById('paciente_id').value = p.id;
                    if (document.getElementById('folio')) document.getElementById('folio').value = p.folio || '';
                    if (document.getElementById('nombre')) document.getElementById('nombre').value = p.nombre;
                    if (document.getElementById('apellidos')) document.getElementById('apellidos').value = p.apellidos;
                    if (document.getElementById('correo')) document.getElementById('correo').value = p.correo || '';
                    if (document.getElementById('telefono')) document.getElementById('telefono').value = p.telefono;
                    if (document.getElementById('seccion_unidad')) document.getElementById('seccion_unidad').value = p.seccion_unidad;
                    if (document.getElementById('prevision')) document.getElementById('prevision').value = p.prevision;
                }
            })
            .catch(err => console.error('Error buscando paciente:', err));
    });
}

// 2. TOGGLE DE MAMOGRAFÍA
function configurarToggleMamografia() {
    const chk = document.getElementById('es_mamografia');
    const seccion = document.getElementById('seccion-cita-box');
    if (!chk || !seccion) return;

    chk.addEventListener('change', () => {
        seccion.style.display = chk.checked ? 'none' : 'block';
    });
}

// 3. CONSULTA DE DISPONIBILIDAD CON SOPORTE DE SOBRECUPO
function configurarCambioTipoYFecha() {
    const selectTipo = document.getElementById('tipo_atencion_id');
    const inputFecha = document.getElementById('fecha');
    const chkSobrecupo = document.getElementById('es_sobrecupo');

    function actualizarDisponibilidad() {
        const tipoId = selectTipo.value;
        const fecha = inputFecha.value;
        const esSobrecupo = chkSobrecupo ? chkSobrecupo.checked : false;

        if (!tipoId || !fecha) return;

        const selectBloque = document.getElementById('bloque_hora');
        const selectEsp = document.getElementById('especialista_id');
        
        if (selectBloque) {
            selectBloque.innerHTML = '<option value="">Consultando disponibilidad...</option>';
        }

        fetch(`/sigas/modulos/ajax_obtener_disponibilidad.php?tipo_atencion_id=${tipoId}&fecha=${encodeURIComponent(fecha)}&es_sobrecupo=${esSobrecupo}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Error al obtener disponibilidad');
                    return;
                }

                // Fijar Box
                if (document.getElementById('box_id')) document.getElementById('box_id').value = data.box_id;
                if (document.getElementById('box_asignado_vista')) document.getElementById('box_asignado_vista').value = data.nombre_box;

                // Cargar especialistas
                if (selectEsp) {
                    selectEsp.innerHTML = '';
                    if (!data.especialistas || data.especialistas.length === 0) {
                        selectEsp.innerHTML = '<option value="">Sin especialistas asignados</option>';
                    } else {
                        data.especialistas.forEach(e => {
                            const opt = document.createElement('option');
                            opt.value = e.id;
                            opt.textContent = e.cargo_titulo ? `${e.nombre} (${e.cargo_titulo})` : e.nombre;
                            selectEsp.appendChild(opt);
                        });
                        selectEsp.selectedIndex = 0;
                    }
                }

                // Cargar bloques de horas
                if (selectBloque) {
                    selectBloque.innerHTML = '';
                    if (!data.bloques || data.bloques.length === 0) {
                        selectBloque.innerHTML = '<option value="">No hay bloques disponibles en este Box</option>';
                        document.getElementById('hora_inicio').value = '';
                        document.getElementById('hora_fin').value = '';
                    } else {
                        selectBloque.innerHTML = '<option value="">Seleccione una hora...</option>';
                        data.bloques.forEach(b => {
                            const opt = document.createElement('option');
                            opt.value = `${b.inicio}|${b.fin}`;
                            opt.textContent = `${b.inicio} a ${b.fin}${b.ocupado ? ' [Ocupado - SOBRECUPO]' : ''}`;
                            if (b.ocupado) opt.style.color = '#c53030';
                            selectBloque.appendChild(opt);
                        });

                        // Preselección si viene por URL
                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('hora')) {
                            const hParam = urlParams.get('hora');
                            for (let i = 0; i < selectBloque.options.length; i++) {
                                if (selectBloque.options[i].value.startsWith(hParam)) {
                                    selectBloque.selectedIndex = i;
                                    selectBloque.dispatchEvent(new Event('change'));
                                    break;
                                }
                            }
                        }
                    }
                }
            })
            .catch(err => console.error('Error al actualizar disponibilidad:', err));
    }

    if (selectTipo && inputFecha) {
        selectTipo.addEventListener('change', actualizarDisponibilidad);
        inputFecha.addEventListener('change', actualizarDisponibilidad);
        if (chkSobrecupo) chkSobrecupo.addEventListener('change', actualizarDisponibilidad);

        if (selectTipo.value) actualizarDisponibilidad();
    }

    const selectBloque = document.getElementById('bloque_hora');
    if (selectBloque) {
        selectBloque.addEventListener('change', (e) => {
            const val = e.target.value;
            if (val && val.includes('|')) {
                const [ini, fin] = val.split('|');
                document.getElementById('hora_inicio').value = ini;
                document.getElementById('hora_fin').value = fin;
            } else {
                document.getElementById('hora_inicio').value = '';
                document.getElementById('hora_fin').value = '';
            }
        });
    }
}

// 4. PARÁMETROS GET DESDE LA GRILLA DE HORARIOS
function procesarParametrosURL() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('fecha') && document.getElementById('fecha')) {
        document.getElementById('fecha').value = urlParams.get('fecha');
    }
    if (urlParams.has('tipo_atencion_id') && document.getElementById('tipo_atencion_id')) {
        document.getElementById('tipo_atencion_id').value = urlParams.get('tipo_atencion_id');
        document.getElementById('tipo_atencion_id').dispatchEvent(new Event('change'));
    }
}

// 5. ENVÍO DE FORMULARIO
function configurarEnvio() {
    const form = document.getElementById('form-nueva-agenda');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const esMamografia = document.getElementById('es_mamografia') ? document.getElementById('es_mamografia').checked : false;

        if (!esMamografia) {
            const horaInicio = document.getElementById('hora_inicio') ? document.getElementById('hora_inicio').value : '';
            if (!horaInicio) {
                alert('Debe seleccionar un bloque de hora para continuar.');
                return;
            }
        }

        const data = {
            rut: document.getElementById('buscar_rut').value,
            folio: document.getElementById('folio') ? document.getElementById('folio').value : null,
            nombre: document.getElementById('nombre').value,
            apellidos: document.getElementById('apellidos').value,
            correo: document.getElementById('correo').value,
            telefono: document.getElementById('telefono').value,
            seccion_unidad: document.getElementById('seccion_unidad').value,
            prevision: document.getElementById('prevision').value,
            es_mamografia: esMamografia,
            box_id: document.getElementById('box_id').value,
            especialista_id: document.getElementById('especialista_id') ? document.getElementById('especialista_id').value : null,
            tipo_atencion_id: document.getElementById('tipo_atencion_id').value,
            fecha: document.getElementById('fecha').value,
            hora_inicio: document.getElementById('hora_inicio').value,
            hora_fin: document.getElementById('hora_fin').value,
            es_sobrecupo: document.getElementById('es_sobrecupo') ? document.getElementById('es_sobrecupo').checked : false
        };

        fetch('/sigas/modulos/api_citas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                throw new Error("Respuesta no válida del servidor: " + text);
            }
        })
        .then(res => {
            if (res.success) {
                alert(res.message);
                window.location.href = '/sigas/dashboard.php';
            } else {
                alert('Aviso: ' + res.message);
            }
        })
        .catch(err => {
            alert('Error al agendar: ' + err.message);
        });
    });
}