-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-09-2026 a las 15:38:03
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sigas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `rut` varchar(12) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `token_recuperacion` varchar(100) DEFAULT NULL,
  `expiracion_token` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id`, `rut`, `nombre`, `correo`, `foto_perfil`, `password_hash`, `token_recuperacion`, `expiracion_token`, `created_at`) VALUES
(4, '203759355', 'Administrador SIGAS', 'admin@centrodesalud.cl', 'assets/img/perfiles/admin_1789657268_6aac00b42956e.png', '$2y$10$HsS/ovZq.lLCL8OW158YV.dCFIUKCrv12bP6U5Nq6tCtlpBkrpq/u', NULL, NULL, '2026-09-04 17:40:59'),
(8, '163859602', 'Paulina Araya', '', NULL, '$2y$10$UcehClHUtyanUt8GXmlJwOmbFhl/z/whEK.uokGo.Ek7xD.Pxrm.6', NULL, NULL, '2026-09-25 13:31:08'),
(9, '151304168', 'Edwin Benitez', '', NULL, '$2y$10$VF7bvkI0JPsM0mpgmuF0GuLPN/KZmBUD8am4bFQBkPPlT5y8T.Dsi', NULL, NULL, '2026-09-25 13:31:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_administradores`
--

CREATE TABLE `auditoria_administradores` (
  `id` int(11) NOT NULL,
  `administrador_id` int(11) NOT NULL,
  `accion` varchar(20) NOT NULL,
  `detalle` text NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `auditoria_administradores`
--

INSERT INTO `auditoria_administradores` (`id`, `administrador_id`, `accion`, `detalle`, `fecha_hora`) VALUES
(1, 4, 'AGENDAR', 'Cita ID 1 creada para Box 1 el dia 2026-09-04 de 08:00 a 08:30', '2026-09-04 18:12:44'),
(2, 4, 'AGENDAR', 'Cita ID 2 creada para Box 1 el dia 2026-09-04 de 08:00 a 08:20', '2026-09-04 18:14:21'),
(3, 4, 'ELIMINAR', 'Elimino cita ID 2 (Box 1 fecha 2026-09-04 a las 08:00:00)', '2026-09-04 18:14:35'),
(4, 4, 'DERIVACION', 'Paciente 20.740.081-5 derivado a Mamografia', '2026-09-04 18:15:08'),
(5, 4, 'AGENDAR', 'Cita ID 3 creada para Box 1 el dia 2026-09-04 de 08:40 a 08:50', '2026-09-04 18:26:37'),
(6, 4, 'AGENDAR', 'Cita ID 4 creada para Box 1 el dia 2026-09-04 de 08:40 a 09:00', '2026-09-04 18:27:27'),
(7, 4, 'AGENDAR', 'Agendó cita ID 5: Paciente RUT 20.740.081-5 en Box 4 el 2026-09-04 (10:00 a 11:00)', '2026-09-04 20:51:59'),
(8, 4, 'AGENDAR', 'Reserva de Box 3 por Edwin (2026-09-09 10:00 - 13:00)', '2026-09-09 12:15:03'),
(9, 4, 'AGENDAR', 'Agendó cita ID 7: Paciente RUT 20740081-5 en Box 2 el 2026-09-16 (12:00 a 13:00)', '2026-09-16 12:56:20'),
(10, 4, 'AGENDAR', 'Reserva de Box 2 por Prueba (2026-09-16 09:00 - 11:00)', '2026-09-16 12:57:56'),
(11, 4, 'EDITAR_ADMIN', 'Modificó al administrador ID 4: Administrador SIGAS (203759355)', '2026-09-16 12:59:11'),
(12, 4, 'AGENDAR', 'Agendó cita ID 9: Paciente RUT 215742245 en Box 4 el 2026-09-16 (15:00 a 16:00)', '2026-09-16 13:49:04'),
(13, 4, 'AGENDAR', 'Agendó cita ID 10: Paciente RUT 20740081-5 en Box 4 el 2026-09-16 (09:00 a 10:00)', '2026-09-16 19:40:25'),
(14, 4, 'AGENDAR', 'Agendó cita ID 11: Paciente RUT 22704698-8 en Box 4 el 2026-09-17 (14:00 a 15:00)', '2026-09-17 14:55:54'),
(15, 4, 'EDITAR_ADMIN', 'Modificó al administrador ID 4: Administrador SIGAS (203759355)', '2026-09-17 15:01:08'),
(16, 4, 'MAMOGRAFIA', 'Cambio de estado a \'solicitada\' en derivacion ID 1', '2026-09-24 17:50:39'),
(17, 4, 'MAMOGRAFIA', 'Cambio de estado a \'agendada\' en derivacion ID 1', '2026-09-24 17:50:41'),
(18, 4, 'MAMOGRAFIA', 'Cambio de estado a \'lista de espera\' en derivacion ID 1', '2026-09-24 17:50:46'),
(19, 4, 'AGENDAR_CITA', 'Cita agendada para RUT 20.740.081-5 en Box 1 el 2026-09-24 (10:00 a 10:30)', '2026-09-24 18:13:02'),
(20, 4, 'CITA_SOBRECUPO', 'Cita agendada para RUT 20.740.081-5 en Box 1 el 2026-09-24 (10:00 a 10:30)', '2026-09-24 18:21:18'),
(21, 4, 'AGENDAR_CITA', 'Cita agendada para RUT 20.740.081-5 en Box 1 el 2026-09-25 (14:00 a 14:30)', '2026-09-25 12:05:12'),
(22, 4, 'CITA_SOBRECUPO', 'Cita agendada para RUT 20.740.081-5 en Box 1 el 2026-09-25 (14:00 a 14:30)', '2026-09-25 12:05:47'),
(23, 4, 'ELIMINAR_PACIENTE', 'Eliminó la ficha del paciente: Martin Astorga (22704698-8)', '2026-09-25 12:24:02'),
(24, 4, 'ELIMINAR_PACIENTE', 'Eliminó la ficha del paciente: martina martinez (215742245)', '2026-09-25 12:24:12'),
(25, 4, 'AGENDAR_CITA', 'Cita agendada para RUT 20375935-5 en Box 1 el 2026-09-25 (08:40 a 08:50)', '2026-09-25 12:31:13'),
(26, 4, 'ELIMINAR_PACIENTE', 'Eliminó la ficha del paciente: OSCAR JOSÉ DIAZ CABRERA (20375935-5)', '2026-09-25 12:33:36'),
(27, 4, 'ELIMINAR_PACIENTE', 'Eliminó la ficha del paciente: Usuario Prueba a (20.740.081-5)', '2026-09-25 12:34:12'),
(28, 4, 'ELIMINAR_PACIENTE', 'Eliminó la ficha del paciente: Prueba Usuario (20740081-5)', '2026-09-25 12:34:24'),
(29, 4, 'CREAR_ESP', 'Registró especialista: Especialista Prueba (123456789)', '2026-09-25 12:50:21'),
(30, 4, 'CREAR_ADMIN', 'Creó al administrador: PRUEBA (123456789)', '2026-09-25 13:05:03'),
(31, 4, 'CREAR_ADMIN', 'Creó al administrador: Paulina Araya (163859602)', '2026-09-25 13:31:08'),
(32, 4, 'CREAR_ADMIN', 'Creó al administrador: Edwin (123123123)', '2026-09-25 13:31:26'),
(33, 4, 'ELIMINAR_ADMIN', 'Eliminó la cuenta de: PRUEBA (123456789)', '2026-09-25 13:31:51'),
(34, 4, 'EDITAR_ADMIN', 'Modificó al administrador ID 9: Edwin Benitez (151304168)', '2026-09-25 13:34:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `boxes`
--

CREATE TABLE `boxes` (
  `id` int(11) NOT NULL,
  `numero_box` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `boxes`
--

INSERT INTO `boxes` (`id`, `numero_box`, `nombre`, `activo`) VALUES
(1, 1, 'Box Procedimientos', 1),
(2, 2, 'Box Dental', 1),
(3, 3, 'Box Médico', 1),
(4, 4, 'Box Salud Mental', 1),
(5, 5, 'Box Rehabilitacion', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `box_id` int(11) NOT NULL,
  `especialista_id` int(11) DEFAULT NULL,
  `tipo_atencion_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `es_sobrecupo` tinyint(1) DEFAULT 0,
  `estado` varchar(20) DEFAULT 'confirmada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `paciente_id`, `box_id`, `especialista_id`, `tipo_atencion_id`, `fecha`, `hora_inicio`, `hora_fin`, `es_sobrecupo`, `estado`, `created_at`) VALUES
(6, 2, 3, NULL, 8, '2026-09-09', '10:00:00', '13:00:00', 0, 'confirmada', '2026-09-09 12:15:03'),
(8, 4, 2, NULL, 8, '2026-09-16', '09:00:00', '11:00:00', 0, 'confirmada', '2026-09-16 12:57:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialistas`
--

CREATE TABLE `especialistas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rut` varchar(12) NOT NULL,
  `cargo_titulo` varchar(100) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialistas`
--

INSERT INTO `especialistas` (`id`, `nombre`, `rut`, `cargo_titulo`, `foto_perfil`, `activo`) VALUES
(1, 'Paulina Araya', '11111111-1', 'Enfermera', NULL, 1),
(2, 'Lorena Grandón', '22222222-2', 'Enfermera', NULL, 1),
(3, 'Nydia Arias', '33333333-3', 'Dentista', NULL, 1),
(4, 'Milton Arias', '44444444-4', 'Médico', NULL, 1),
(5, 'Leonora', '55555555-5', 'Psiquiatra', NULL, 1),
(6, 'Marta Carvajal', '66666666-6', 'Psicóloga', NULL, 1),
(7, 'Especialista Kinesiología', '77777777-7', 'Kinesiólogo', NULL, 1),
(8, 'Especialista Prueba', '123456789', 'SI', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mamografias`
--

CREATE TABLE `mamografias` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `fecha_derivacion` date NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'lista de espera',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `rut` varchar(12) NOT NULL,
  `folio` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `seccion_unidad` varchar(100) NOT NULL,
  `prevision` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `rut`, `folio`, `nombre`, `apellidos`, `correo`, `telefono`, `seccion_unidad`, `prevision`, `created_at`) VALUES
(2, 'SALA-54bd891', NULL, 'Edwin', '(Uso de Sala)', 'sin_correo@centro.cl', '000000000', 'Uso Interno', 'PARTICULAR', '2026-09-09 12:15:03'),
(4, 'SALA-4c76821', NULL, 'Prueba', '(Uso de Sala)', 'sin_correo@centro.cl', '000000000', 'Uso Interno', 'PARTICULAR', '2026-09-16 12:57:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_atencion`
--

CREATE TABLE `tipos_atencion` (
  `id` int(11) NOT NULL,
  `box_id` int(11) NOT NULL DEFAULT 1,
  `nombre` varchar(150) NOT NULL,
  `duracion_minutos` int(11) NOT NULL,
  `color_hex` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_atencion`
--

INSERT INTO `tipos_atencion` (`id`, `box_id`, `nombre`, `duracion_minutos`, `color_hex`) VALUES
(1, 1, 'Toma de Muestras', 10, '#00BCD4'),
(2, 1, 'EMP', 30, '#4CAF50'),
(3, 2, 'Dental', 60, '#E91E63'),
(4, 3, 'Morbilidad', 20, '#2196F3'),
(5, 3, 'Certificados', 10, '#FF9800'),
(6, 4, 'Salud Mental USIT', 60, '#9C27B0'),
(7, 5, 'Kinesiología', 30, '#009688'),
(8, 1, 'Reserva de Box / Sala', 60, '#4A5568');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_atencion_especialistas`
--

CREATE TABLE `tipo_atencion_especialistas` (
  `tipo_atencion_id` int(11) NOT NULL,
  `especialista_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_atencion_especialistas`
--

INSERT INTO `tipo_atencion_especialistas` (`tipo_atencion_id`, `especialista_id`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 4),
(6, 5),
(6, 6),
(7, 7),
(7, 8);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rut` (`rut`);

--
-- Indices de la tabla `auditoria_administradores`
--
ALTER TABLE `auditoria_administradores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `administrador_id` (`administrador_id`);

--
-- Indices de la tabla `boxes`
--
ALTER TABLE `boxes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_box` (`numero_box`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `box_id` (`box_id`),
  ADD KEY `tipo_atencion_id` (`tipo_atencion_id`);

--
-- Indices de la tabla `especialistas`
--
ALTER TABLE `especialistas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mamografias`
--
ALTER TABLE `mamografias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rut` (`rut`);

--
-- Indices de la tabla `tipos_atencion`
--
ALTER TABLE `tipos_atencion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipo_atencion_especialistas`
--
ALTER TABLE `tipo_atencion_especialistas`
  ADD PRIMARY KEY (`tipo_atencion_id`,`especialista_id`),
  ADD KEY `especialista_id` (`especialista_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `auditoria_administradores`
--
ALTER TABLE `auditoria_administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `boxes`
--
ALTER TABLE `boxes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `especialistas`
--
ALTER TABLE `especialistas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `mamografias`
--
ALTER TABLE `mamografias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tipos_atencion`
--
ALTER TABLE `tipos_atencion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria_administradores`
--
ALTER TABLE `auditoria_administradores`
  ADD CONSTRAINT `auditoria_administradores_ibfk_1` FOREIGN KEY (`administrador_id`) REFERENCES `administradores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`box_id`) REFERENCES `boxes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `citas_ibfk_3` FOREIGN KEY (`tipo_atencion_id`) REFERENCES `tipos_atencion` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mamografias`
--
ALTER TABLE `mamografias`
  ADD CONSTRAINT `mamografias_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tipo_atencion_especialistas`
--
ALTER TABLE `tipo_atencion_especialistas`
  ADD CONSTRAINT `tipo_atencion_especialistas_ibfk_1` FOREIGN KEY (`tipo_atencion_id`) REFERENCES `tipos_atencion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tipo_atencion_especialistas_ibfk_2` FOREIGN KEY (`especialista_id`) REFERENCES `especialistas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
