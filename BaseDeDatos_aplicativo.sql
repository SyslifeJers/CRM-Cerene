-- SQL filtrado para el aplicativo CRM-Cerene
-- Generado desde BaseDeDatos.sql; incluye solo tablas referenciadas por el codigo y sus dependencias directas.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Rol`
--

CREATE TABLE `Rol` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `activo` int(11) NOT NULL,
  `Fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colores`
--

CREATE TABLE `colores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `codigo_hex` char(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Usuarios`
--

CREATE TABLE `Usuarios` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `user` varchar(50) NOT NULL,
  `pass` varchar(50) NOT NULL,
  `token` text NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiration` datetime DEFAULT NULL,
  `activo` int(11) NOT NULL,
  `registro` datetime NOT NULL,
  `telefono` varchar(150) NOT NULL,
  `correo` text NOT NULL,
  `IdRol` int(11) NOT NULL,
  `color_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `frecuencia_pago`
--

CREATE TABLE `frecuencia_pago` (
  `id_frecuencia` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `dias` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `opciones_pago`
--

CREATE TABLE `opciones_pago` (
  `id_opcion` int(11) NOT NULL,
  `numero_pagos` int(11) NOT NULL,
  `id_frecuencia` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `costo_adicional` decimal(12,2) DEFAULT NULL,
  `nota` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id_curso` int(11) NOT NULL,
  `nombre_curso` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `link_inscripcion` varchar(255) DEFAULT NULL,
  `clave_curso` varchar(20) DEFAULT NULL,
  `requiere_pago` tinyint(1) DEFAULT 0,
  `cupo_maximo` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participantes`
--

CREATE TABLE `participantes` (
  `id_participante` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `cedula` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `documento` varchar(255) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `pass` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id_inscripcion` int(11) NOT NULL,
  `id_curso` int(11) DEFAULT NULL,
  `id_participante` int(11) DEFAULT NULL,
  `fecha_inscripcion` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('registrado','comprobante_enviado','pago_validado','rechazado','pagos programados','completado','Revision de pago') DEFAULT 'registrado',
  `estatus_certificado` enum('pendiente','enviado','problema','reenviado') NOT NULL DEFAULT 'pendiente',
  `fecha_cambio_estado` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `metodo_pago` varchar(50) DEFAULT NULL,
  `referencia_pago` varchar(100) DEFAULT NULL,
  `monto_pagado` decimal(10,2) DEFAULT NULL,
  `comprobante_path` varchar(255) DEFAULT NULL,
  `nota` text DEFAULT NULL,
  `IdOpcionPago` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobantes_inscripcion`
--

CREATE TABLE `comprobantes_inscripcion` (
  `id_comprobante` int(11) NOT NULL,
  `id_inscripcion` int(11) NOT NULL,
  `numero_pago` int(11) NOT NULL,
  `metodo_pago` varchar(100) NOT NULL,
  `referencia_pago` varchar(255) NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL,
  `comprobante_path` varchar(255) NOT NULL,
  `fecha_carga` timestamp NOT NULL DEFAULT current_timestamp(),
  `validado` int(11) NOT NULL DEFAULT 0,
  `nota` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenido_curso`
--

CREATE TABLE `contenido_curso` (
  `id_contenido` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo_contenido` enum('documento','video','enlace','presentacion','tarea') NOT NULL,
  `archivo_ruta` varchar(255) DEFAULT NULL,
  `enlace_url` varchar(255) DEFAULT NULL,
  `fecha_publicacion` timestamp NULL DEFAULT current_timestamp(),
  `orden` int(11) DEFAULT 0,
  `PagoPorce` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reuniones_zoom`
--

CREATE TABLE `reuniones_zoom` (
  `id_reunion` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `duracion_minutos` int(11) DEFAULT 60,
  `url_zoom` varchar(255) NOT NULL,
  `codigo_acceso` varchar(50) DEFAULT NULL,
  `grabacion_url` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `PagoPorce` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rechazos_inscripciones`
--

CREATE TABLE `rechazos_inscripciones` (
  `id_rechazo` int(11) NOT NULL,
  `id_inscripcion` int(11) NOT NULL,
  `motivo` enum('documento_no_legible','informacion_incompleta','monto_incorrecto','documento_falsificado','otro') NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha_rechazo` timestamp NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Indices, AUTO_INCREMENT y restricciones
--

ALTER TABLE `Rol`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `Rol`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `colores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `colores_nombre_unique` (`nombre`),
  ADD UNIQUE KEY `colores_codigo_hex_unique` (`codigo_hex`);

ALTER TABLE `colores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_rol` (`IdRol`),
  ADD KEY `fk_usuarios_color` (`color_id`);

ALTER TABLE `Usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Usuarios`
  ADD CONSTRAINT `fk_user_rol` FOREIGN KEY (`IdRol`) REFERENCES `Rol` (`id`),
  ADD CONSTRAINT `fk_usuarios_color` FOREIGN KEY (`color_id`) REFERENCES `colores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `frecuencia_pago`
  ADD PRIMARY KEY (`id_frecuencia`);

ALTER TABLE `frecuencia_pago`
  MODIFY `id_frecuencia` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `opciones_pago`
  ADD PRIMARY KEY (`id_opcion`),
  ADD KEY `fk_opciones_pago_frecuencia` (`id_frecuencia`);

ALTER TABLE `opciones_pago`
  MODIFY `id_opcion` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `opciones_pago`
  ADD CONSTRAINT `fk_opciones_pago_frecuencia` FOREIGN KEY (`id_frecuencia`) REFERENCES `frecuencia_pago` (`id_frecuencia`) ON UPDATE CASCADE;

ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`),
  ADD UNIQUE KEY `clave_curso` (`clave_curso`);

ALTER TABLE `cursos`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `participantes`
  ADD PRIMARY KEY (`id_participante`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `participantes`
  MODIFY `id_participante` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_participante` (`id_participante`),
  ADD KEY `inscripciones_ibfk_3` (`IdOpcionPago`);

ALTER TABLE `inscripciones`
  MODIFY `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`id_participante`) REFERENCES `participantes` (`id_participante`),
  ADD CONSTRAINT `inscripciones_ibfk_3` FOREIGN KEY (`IdOpcionPago`) REFERENCES `opciones_pago` (`id_opcion`);

ALTER TABLE `comprobantes_inscripcion`
  ADD PRIMARY KEY (`id_comprobante`),
  ADD KEY `id_inscripcion` (`id_inscripcion`);

ALTER TABLE `comprobantes_inscripcion`
  MODIFY `id_comprobante` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `comprobantes_inscripcion`
  ADD CONSTRAINT `comprobantes_inscripcion_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`);

ALTER TABLE `contenido_curso`
  ADD PRIMARY KEY (`id_contenido`),
  ADD KEY `id_curso` (`id_curso`);

ALTER TABLE `contenido_curso`
  MODIFY `id_contenido` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `contenido_curso`
  ADD CONSTRAINT `contenido_curso_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

ALTER TABLE `reuniones_zoom`
  ADD PRIMARY KEY (`id_reunion`),
  ADD KEY `id_curso` (`id_curso`);

ALTER TABLE `reuniones_zoom`
  MODIFY `id_reunion` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `reuniones_zoom`
  ADD CONSTRAINT `reuniones_zoom_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

ALTER TABLE `rechazos_inscripciones`
  ADD PRIMARY KEY (`id_rechazo`),
  ADD KEY `id_inscripcion` (`id_inscripcion`);

ALTER TABLE `rechazos_inscripciones`
  MODIFY `id_rechazo` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rechazos_inscripciones`
  ADD CONSTRAINT `rechazos_inscripciones_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
