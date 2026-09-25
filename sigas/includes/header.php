<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAS - Sistema de Gestión de Atención de Salud</title>
    <link rel="stylesheet" href="/sigas/assets/css/estilos.css">
    <link rel="stylesheet" href="/sigas/assets/css/calendario.css">
</head>
<body>
    <header class="header-principal">
        <div class="header-logo">
            <a href="/sigas/dashboard.php" style="color: #ffffff; text-decoration: none;">
                <h1>SIGAS</h1>
            </a>
        </div>
        <nav class="header-nav">
            <a href="/sigas/dashboard.php" class="nav-link">Inicio</a>
            <a href="/sigas/vistas/mamografias.php" class="nav-link">Mamografías</a>

            <!-- MENU DESPLEGABLE: AGENDAR -->
            <div class="dropdown">
                <button class="dropdown-btn">Agendar</button>
                <div class="dropdown-content">
                    <a href="/sigas/vistas/agendar.php">Agendar Pacientes</a>
                    <a href="/sigas/vistas/agendar_box.php">Agendar Box</a>
                </div>
            </div>

            <!-- MENU DESPLEGABLE: HORARIOS BOXES -->
            <div class="dropdown">
                <button class="dropdown-btn">Horarios Boxes</button>
                <div class="dropdown-content">
                    <a href="/sigas/vistas/box_semanal.php?box=1">Box Procedimientos</a>
                    <a href="/sigas/vistas/box_semanal.php?box=2">Box Dental</a>
                    <a href="/sigas/vistas/box_semanal.php?box=3">Box Médico</a>
                    <a href="/sigas/vistas/box_semanal.php?box=4">Box Salud Mental</a>
                    <a href="/sigas/vistas/box_semanal.php?box=5">Box Rehabilitación</a>
                </div>
            </div>

            <!-- MENU DESPLEGABLE: PANEL ADMIN CON ADMINISTRADORES -->
            <div class="dropdown">
                <button class="dropdown-btn">Panel Admin</button>
                <div class="dropdown-content">
                    <a href="/sigas/vistas/administradores.php">Administradores</a>
                    <a href="/sigas/vistas/pacientes.php">Pacientes</a>
                    <a href="/sigas/vistas/especialistas.php">Especialistas</a>
                    <a href="/sigas/vistas/historial.php">Historial Horas</a>
                    <a href="/sigas/vistas/auditoria.php">Auditoría</a>
                    <a href="/sigas/logout.php" class="item-logout">Cerrar Sesión</a>
                </div>
            </div>
        </nav>
    </header>