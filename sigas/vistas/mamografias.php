<?php
require_once '../includes/auth.php';
require_once '../conexion.php';
include '../includes/header.php';

// Consultar derivaciones
$sql = "SELECT m.id, m.fecha_derivacion, m.estado,
               p.rut, p.folio, p.nombre, p.apellidos, p.telefono, p.correo, p.seccion_unidad, p.prevision
        FROM mamografias m
        INNER JOIN pacientes p ON m.paciente_id = p.id
        ORDER BY m.fecha_derivacion DESC, m.id DESC";
$derivaciones = $pdo->query($sql)->fetchAll();

$totalEspera = 0; $totalSolicitada = 0; $totalAgendada = 0;
foreach ($derivaciones as $d) {
    if ($d['estado'] === 'lista de espera') $totalEspera++;
    elseif ($d['estado'] === 'solicitada') $totalSolicitada++;
    elseif ($d['estado'] === 'agendada') $totalAgendada++;
}
?>

<div style="max-width: 1150px; margin: 25px auto; padding: 0 15px;">
    <!-- TARJETAS RESUMEN DE ESTADOS -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
        <div style="background: #ffffff; padding: 15px 20px; border-radius: 8px; border-left: 5px solid #d69e2e; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <span style="font-size: 12px; color: #718096; font-weight: bold; text-transform: uppercase;">Lista de Espera</span>
            <div style="font-size: 26px; font-weight: bold; color: #2d3748; margin-top: 4px;"><?= $totalEspera ?></div>
        </div>
        <div style="background: #ffffff; padding: 15px 20px; border-radius: 8px; border-left: 5px solid #3182ce; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <span style="font-size: 12px; color: #718096; font-weight: bold; text-transform: uppercase;">Solicitadas</span>
            <div style="font-size: 26px; font-weight: bold; color: #2d3748; margin-top: 4px;"><?= $totalSolicitada ?></div>
        </div>
        <div style="background: #ffffff; padding: 15px 20px; border-radius: 8px; border-left: 5px solid #38a169; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <span style="font-size: 12px; color: #718096; font-weight: bold; text-transform: uppercase;">Agendadas</span>
            <div style="font-size: 26px; font-weight: bold; color: #2d3748; margin-top: 4px;"><?= $totalAgendada ?></div>
        </div>
    </div>

    <!-- TABLA DE DERIVACIONES -->
    <div style="background: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h2 style="font-size: 18px; color: #1a365d; margin: 0;">Gestión de Derivaciones a Mamografía</h2>
                <span id="mensaje-estado-mamo" style="font-size: 12px; font-weight: bold; color: #2b6cb0;"></span>
            </div>
            <input type="text" id="filtro-mamografia" placeholder="Buscar por RUT, Folio o Paciente..." 
                   style="padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 13px; width: 280px;">
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #edf2f7; color: #4a5568; text-align: left; border-bottom: 2px solid #cbd5e0;">
                    <th style="padding: 10px;">Fecha</th>
                    <th style="padding: 10px;">RUT / Folio</th>
                    <th style="padding: 10px;">Paciente</th>
                    <th style="padding: 10px;">Contacto</th>
                    <th style="padding: 10px;">Unidad / Previsión</th>
                    <th style="padding: 10px; width: 170px;">Estado</th>
                </tr>
            </thead>
            <tbody id="tabla-mamo-cuerpo">
                <?php if (empty($derivaciones)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 20px; color: #a0aec0;">No hay derivaciones registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($derivaciones as $row): ?>
                        <tr class="fila-mamo" style="border-bottom: 1px solid #edf2f7;">
                            <td style="padding: 10px;"><?= date('d/m/Y', strtotime($row['fecha_derivacion'])) ?></td>
                            <td style="padding: 10px;">
                                <strong><?= htmlspecialchars($row['rut']) ?></strong><br>
                                <?php if (!empty($row['folio'])): ?>
                                    <small style="color: #4a5568; font-weight: 600;">Folio: <?= htmlspecialchars($row['folio']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px;"><?= htmlspecialchars($row['nombre'] . ' ' . $row['apellidos']) ?></td>
                            <td style="padding: 10px;">
                                <div><?= htmlspecialchars($row['telefono']) ?></div>
                                <small style="color: #718096;"><?= htmlspecialchars($row['correo']) ?></small>
                            </td>
                            <td style="padding: 10px;">
                                <div><?= htmlspecialchars($row['seccion_unidad']) ?></div>
                                <small style="font-weight: 600; color: #4a5568;"><?= htmlspecialchars($row['prevision']) ?></small>
                            </td>
                            <td style="padding: 10px;">
                                <select class="select-estado-mamo" data-id="<?= $row['id'] ?>" 
                                        style="width: 100%; padding: 6px 8px; border-radius: 5px; border: 1px solid #cbd5e0; font-weight: 600; font-size: 12px; cursor: pointer;">
                                    <option value="lista de espera" <?= $row['estado'] === 'lista de espera' ? 'selected' : '' ?>>⏳ Lista de espera</option>
                                    <option value="solicitada" <?= $row['estado'] === 'solicitada' ? 'selected' : '' ?>>📩 Solicitada</option>
                                    <option value="agendada" <?= $row['estado'] === 'agendada' ? 'selected' : '' ?>>✅ Agendada</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buscador = document.getElementById('filtro-mamografia');
    const filas = document.querySelectorAll('.fila-mamo');
    buscador.addEventListener('input', () => {
        const q = buscador.value.toLowerCase().trim();
        filas.forEach(f => {
            f.style.display = f.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    const selects = document.querySelectorAll('.select-estado-mamo');
    const msg = document.getElementById('mensaje-estado-mamo');
    selects.forEach(s => {
        s.addEventListener('change', (e) => {
            msg.textContent = 'Actualizando estado...';
            fetch('/sigas/modulos/ajax_actualizar_mamografia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: e.target.getAttribute('data-id'), estado: e.target.value })
            })
            .then(res => res.json())
            .then(d => {
                msg.textContent = d.success ? 'Estado guardado.' : 'Error al guardar.';
                setTimeout(() => { msg.textContent = ''; }, 2500);
            });
        });
    });
});
</script>
<?php include '../includes/footer.php'; ?>