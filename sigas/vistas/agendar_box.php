<?php
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

$boxes = $pdo->query("SELECT id, nombre FROM boxes ORDER BY id ASC")->fetchAll();
?>

<div style="max-width: 600px; margin: 40px auto; padding: 25px 30px; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
    <h2 style="font-size: 18px; color: #1a365d; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; margin-bottom: 20px;">
        Agendar Box / Sala
    </h2>

    <form id="form-agendar-box">
        <div class="form-group" style="margin-bottom: 15px;">
            <label for="responsable" style="font-weight: bold; font-size: 13px;">Nombre Completo</label>
            <input type="text" id="responsable" name="responsable" placeholder="" required style="width: 100%; height: 38px; padding: 8px;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
            <div class="form-group">
                <label for="box_id" style="font-weight: bold; font-size: 13px;">Box Clínico / Sala</label>
                <select id="box_id" name="box_id" required style="width: 100%; height: 38px;">
                    <option value="">Seleccione sala...</option>
                    <?php foreach ($boxes as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha" style="font-weight: bold; font-size: 13px;">Fecha</label>
                <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required style="width: 100%; height: 38px; padding: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 20px;">
            <div class="form-group">
                <label for="horas_uso" style="font-weight: bold; font-size: 13px;">Horas de Uso</label>
                <select id="horas_uso" name="horas_uso" style="width: 100%; height: 38px;">
                    <option value="1">1 Hora</option>
                    <option value="2">2 Horas</option>
                    <option value="3">3 Horas</option>
                    <option value="4">4 Horas</option>
                </select>
            </div>

            <div class="form-group">
                <label for="bloque_horario" style="font-weight: bold; font-size: 13px;">Horarios Libres Disponibles</label>
                <select id="bloque_horario" required style="width: 100%; height: 38px;">
                    <option value="">Seleccione sala, fecha y horas...</option>
                </select>
                <input type="hidden" id="hora_inicio" name="hora_inicio">
                <input type="hidden" id="hora_fin" name="hora_fin">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="height: 42px; width: 100%; font-weight: bold;">
            Confirmar Reserva de Sala
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectBox = document.getElementById('box_id');
    const inputFecha = document.getElementById('fecha');
    const selectHoras = document.getElementById('horas_uso');
    const selectBloque = document.getElementById('bloque_horario');

    function consultarHorasLibres() {
        const boxId = selectBox.value;
        const fecha = inputFecha.value;
        const horas = selectHoras.value;

        if (!boxId || !fecha || !horas) return;

        selectBloque.innerHTML = '<option value="">Consultando disponibilidad...</option>';

        fetch(`/sigas/modulos/ajax_obtener_disponibilidad_box.php?box_id=${boxId}&fecha=${encodeURIComponent(fecha)}&horas=${horas}`)
            .then(res => res.json())
            .then(data => {
                selectBloque.innerHTML = '';
                if (!data.bloques || data.bloques.length === 0) {
                    selectBloque.innerHTML = '<option value="">No hay bloques contiguos libres</option>';
                    document.getElementById('hora_inicio').value = '';
                    document.getElementById('hora_fin').value = '';
                    return;
                }

                selectBloque.innerHTML = '<option value="">Seleccione un horario disponible...</option>';
                data.bloques.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = `${b.inicio}|${b.fin}`;
                    opt.textContent = `${b.inicio} a ${b.fin} hrs`;
                    selectBloque.appendChild(opt);
                });
            })
            .catch(err => console.error('Error:', err));
    }

    selectBox.addEventListener('change', consultarHorasLibres);
    inputFecha.addEventListener('change', consultarHorasLibres);
    selectHoras.addEventListener('change', consultarHorasLibres);

    selectBloque.addEventListener('change', (e) => {
        const val = e.target.value;
        if (val && val.includes('|')) {
            const [ini, fin] = val.split('|');
            document.getElementById('hora_inicio').value = ini;
            document.getElementById('hora_fin').value = fin;
        }
    });

    document.getElementById('form-agendar-box').addEventListener('submit', (e) => {
        e.preventDefault();
        const ini = document.getElementById('hora_inicio').value;
        const fin = document.getElementById('hora_fin').value;

        if (!ini || !fin) {
            alert('Debe elegir un horario disponible de la lista desplegable.');
            return;
        }

        const payload = {
            responsable: document.getElementById('responsable').value,
            box_id: document.getElementById('box_id').value,
            fecha: document.getElementById('fecha').value,
            hora_inicio: ini,
            hora_fin: fin
        };

        fetch('/sigas/modulos/api_citas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert(res.message);
                window.location.href = '/sigas/dashboard.php';
            } else {
                alert('Aviso: ' + res.message);
            }
        })
        .catch(err => alert('Error de conexión al procesar la reserva.'));
    });
});
</script>

<?php include '../includes/footer.php'; ?>