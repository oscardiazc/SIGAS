// BUSQUEDA EN TIEMPO REAL EN DIRECTORIO DE PACIENTES
document.addEventListener('DOMContentLoaded', () => {
    const inputBuscador = document.getElementById('buscador-pacientes');
    const filas = document.querySelectorAll('.fila-paciente');

    if (!inputBuscador) return;

    inputBuscador.addEventListener('input', () => {
        const termino = inputBuscador.value.toLowerCase().trim();

        filas.forEach(fila => {
            const textoFila = fila.textContent.toLowerCase();
            if (textoFila.includes(termino)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    });
});