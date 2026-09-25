<?php
// CONTROLADOR DE ACCESO Y AUTENTICACION
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAS - Iniciar Sesión</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>SIGAS</h2>
        <p class="subtitulo">Sistema de Gestión de Atención de Salud</p>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alerta-error">Credenciales inválidas. Verifique sus datos.</div>
        <?php endif; ?>

        <form action="modulos/procesar_login.php" method="POST">
            <div class="form-group">
                <label for="rut">RUT Administrador</label>
                <input 
                    type="text" 
                    id="rut" 
                    name="rut" 
                    placeholder="12345678-9" 
                    required 
                    maxlength="12" 
                    autocomplete="off"
                >
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
        </form>

    </div>

    <script>
    // FORMATEO VISUAL DINAMICO DEL RUT (SOLO NUMEROS)
    const inputRut = document.getElementById('rut');

    inputRut.addEventListener('input', (e) => {
        // 1. Filtrar cualquier caracter que no sea numero o K
        let valorLimpio = e.target.value.replace(/[^0-9kK]/g, '').toUpperCase();
        
        if (!valorLimpio) {
            e.target.value = '';
            return;
        }

        // 2. Si solo lleva un caracter (aun no hay verificador separado)
        if (valorLimpio.length === 1) {
            e.target.value = valorLimpio;
            return;
        }

        // 3. Separar cuerpo de digito verificador
        let dv = valorLimpio.slice(-1);
        let cuerpo = valorLimpio.slice(0, -1);

        // 4. Formatear cuerpo con puntos cada 3 digitos de derecha a izquierda
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

        // 5. Devolver valor visualmente formateado
        e.target.value = `${cuerpoFormateado}-${dv}`;
    });
    </script>
</body>
</html>