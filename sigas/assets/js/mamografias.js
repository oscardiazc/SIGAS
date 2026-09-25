// CONTROLADOR DE ACTUALIZACION DE ESTADOS DE MAMOGRAFIA
document.addEventListener('DOMContentLoaded', () => {
    const selects = document.querySelectorAll('.select-estado-mamo');
    const mensajeEstado = document.getElementById('mensaje-estado-mamo');

    selects.forEach(select => {
        select.addEventListener('change', (e) => {
            const mamoId = e.target.getAttribute('data-id');
            const nuevoEstado = e.target.value;

            mensajeEstado.textContent = 'Actualizando estado...';

            fetch('/sigas/modulos/ajax_actualizar_mamografia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: mamoId, estado: nuevoEstado })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    mensajeEstado.textContent = 'Estado guardado correctamente.';
                    setTimeout(() => { mensajeEstado.textContent = ''; }, 3000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error al actualizar estado:', err);
                alert('No se pudo actualizar el estado por error de conexion.');
            });
        });
    });
});