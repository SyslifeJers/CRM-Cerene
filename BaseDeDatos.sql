-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-05-2026 a las 01:18:41
-- Versión del servidor: 11.8.6-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u529445062_cenere`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `AdeudosDiagnostico`
--

CREATE TABLE `AdeudosDiagnostico` (
  `id` int(11) NOT NULL,
  `nino_id` int(11) NOT NULL,
  `psicologo_id` int(11) DEFAULT NULL,
  `cita_inicial_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `saldo_restante` decimal(10,2) NOT NULL,
  `estatus_id` int(11) NOT NULL DEFAULT 2,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `AdeudosDiagnosticoPagos`
--

CREATE TABLE `AdeudosDiagnosticoPagos` (
  `id` int(11) NOT NULL,
  `adeudo_id` int(11) NOT NULL,
  `metodo` varchar(50) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Cita`
--

CREATE TABLE `Cita` (
  `id` int(11) NOT NULL,
  `IdNino` int(11) DEFAULT NULL,
  `IdUsuario` int(11) NOT NULL,
  `idGenerado` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `costo` decimal(12,2) NOT NULL,
  `Programado` datetime NOT NULL,
  `Estatus` int(11) NOT NULL,
  `Tipo` varchar(250) NOT NULL,
  `paquete_id` int(11) DEFAULT NULL,
  `diagnostico_id` int(11) DEFAULT NULL,
  `diagnostico_sesion` int(11) DEFAULT NULL,
  `adeudo_diagnostico_id` int(11) DEFAULT NULL,
  `FormaPago` varchar(250) NOT NULL,
  `Tiempo` int(11) NOT NULL DEFAULT 60,
  `forzada` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `CitaPagos`
--

CREATE TABLE `CitaPagos` (
  `id` int(11) NOT NULL,
  `cita_id` int(11) NOT NULL,
  `metodo` varchar(50) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Clientes`
--

CREATE TABLE `Clientes` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `activo` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `correo` varchar(250) DEFAULT NULL,
  `tipo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `colaTickets`
--

CREATE TABLE `colaTickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_cita` int(11) NOT NULL,
  `estado` enum('pendiente','en_proceso','impreso','error','cancelado') NOT NULL DEFAULT 'pendiente',
  `intentos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `mensaje_error` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `validado` int(11) NOT NULL,
  `nota` varchar(250) NOT NULL
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
-- Estructura de tabla para la tabla `CorteCaja`
--

CREATE TABLE `CorteCaja` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `efectivo_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `registrado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Estructura de tabla para la tabla `DemoPagos`
--

CREATE TABLE `DemoPagos` (
  `id` int(11) NOT NULL,
  `origen` varchar(30) NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `paquete_id` int(11) DEFAULT NULL,
  `paciente_id` int(11) NOT NULL,
  `paciente_nombre` varchar(150) NOT NULL,
  `psicologo_id` int(11) DEFAULT NULL,
  `psicologo_nombre` varchar(150) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `fecha_pago` datetime NOT NULL,
  `fecha_corte` date NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `DemoSaldoMovimientos`
--

CREATE TABLE `DemoSaldoMovimientos` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `saldo_anterior` decimal(10,2) NOT NULL,
  `saldo_nuevo` decimal(10,2) NOT NULL,
  `pago_id` int(11) DEFAULT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `paquete_id` int(11) DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `DiagnosticoPagos`
--

CREATE TABLE `DiagnosticoPagos` (
  `id` int(11) NOT NULL,
  `diagnostico_id` int(11) NOT NULL,
  `metodo` varchar(50) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Diagnosticos`
--

CREATE TABLE `Diagnosticos` (
  `id` int(11) NOT NULL,
  `nino_id` int(11) NOT NULL,
  `psicologo_id` int(11) DEFAULT NULL,
  `cita_inicial_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `pago_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_restante` decimal(10,2) NOT NULL,
  `sesiones_total` int(11) NOT NULL,
  `sesiones_completadas` int(11) NOT NULL DEFAULT 0,
  `estatus_id` int(11) NOT NULL DEFAULT 2,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_pago`
--

CREATE TABLE `documentos_pago` (
  `id_documento` int(11) NOT NULL,
  `id_inscripcion` int(11) DEFAULT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','revisado','aceptado','rechazado') DEFAULT 'pendiente',
  `notas_revision` text DEFAULT NULL,
  `id_revisor` int(11) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Estatus`
--

CREATE TABLE `Estatus` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_adjuntos_evaluacion`
--

CREATE TABLE `exp_adjuntos_evaluacion` (
  `id_adjunto` int(11) NOT NULL,
  `id_evaluacion` int(11) NOT NULL,
  `tipo_archivo` varchar(20) DEFAULT NULL,
  `nombre_archivo` varchar(255) DEFAULT NULL,
  `ruta_archivo` varchar(500) DEFAULT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_areas_evaluacion`
--

CREATE TABLE `exp_areas_evaluacion` (
  `id_area` int(11) NOT NULL,
  `nombre_area` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_criterios_evaluacion`
--

CREATE TABLE `exp_criterios_evaluacion` (
  `id_criterio` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_evaluaciones`
--

CREATE TABLE `exp_evaluaciones` (
  `id_evaluacion` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_area` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_evaluacion_examen`
--

CREATE TABLE `exp_evaluacion_examen` (
  `id_eval` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `respuestas` text NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `status` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_evaluacion_fotos`
--

CREATE TABLE `exp_evaluacion_fotos` (
  `id_eval_foto` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `seccion` varchar(255) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_evaluacion_fotos_imagenes`
--

CREATE TABLE `exp_evaluacion_fotos_imagenes` (
  `id_imagen` int(11) NOT NULL,
  `id_eval_foto` int(11) NOT NULL,
  `ruta` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_examenes`
--

CREATE TABLE `exp_examenes` (
  `id_examen` int(11) NOT NULL,
  `id_area` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nombre_examen` varchar(255) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_nino_criterio`
--

CREATE TABLE `exp_nino_criterio` (
  `id_nino` int(11) NOT NULL,
  `id_criterio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_opciones_pregunta`
--

CREATE TABLE `exp_opciones_pregunta` (
  `id_opcion` int(11) NOT NULL,
  `texto` varchar(255) NOT NULL,
  `id_exam` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_preguntas_evaluacion`
--

CREATE TABLE `exp_preguntas_evaluacion` (
  `id_pregunta` int(11) NOT NULL,
  `id_seccion` int(11) NOT NULL,
  `pregunta` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_pregunta_opcion`
--

CREATE TABLE `exp_pregunta_opcion` (
  `id_pregunta` int(11) NOT NULL,
  `id_opcion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_progreso_general`
--

CREATE TABLE `exp_progreso_general` (
  `id_progreso` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `lenguaje` tinyint(4) DEFAULT NULL,
  `motricidad` tinyint(4) DEFAULT NULL,
  `atencion` tinyint(4) DEFAULT NULL,
  `memoria` tinyint(4) DEFAULT NULL,
  `social` tinyint(4) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_secciones_examen`
--

CREATE TABLE `exp_secciones_examen` (
  `id_seccion` int(11) NOT NULL,
  `id_examen` int(11) NOT NULL,
  `nombre_seccion` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_valoraciones_sesion`
--

CREATE TABLE `exp_valoraciones_sesion` (
  `id_valoracion` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_valoracion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exp_valoracion_detalle`
--

CREATE TABLE `exp_valoracion_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_valoracion` int(11) NOT NULL,
  `id_criterio` int(11) NOT NULL,
  `valor` tinyint(4) NOT NULL
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
-- Estructura de tabla para la tabla `HistorialEstatus`
--

CREATE TABLE `HistorialEstatus` (
  `id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `idEstatus` int(11) NOT NULL,
  `idCita` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL
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
-- Estructura de tabla para la tabla `LogSistema`
--

CREATE TABLE `LogSistema` (
  `id` int(10) UNSIGNED NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  `modulo` varchar(100) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `entidad` varchar(100) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `MensajeCreado`
--

CREATE TABLE `MensajeCreado` (
  `id` int(11) NOT NULL,
  `mensaje_id` int(11) NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nino`
--

CREATE TABLE `nino` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `activo` int(11) NOT NULL,
  `edad` int(11) NOT NULL,
  `Observacion` text NOT NULL,
  `FechaIngreso` date DEFAULT NULL,
  `idtutor` int(11) NOT NULL,
  `saldo_paquete` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `NinoPagosPendiente`
--

CREATE TABLE `NinoPagosPendiente` (
  `id` int(11) NOT NULL,
  `nino_id` int(11) NOT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `concepto` varchar(50) NOT NULL DEFAULT 'saldo_pendiente',
  `metodo` varchar(50) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Estructura de tabla para la tabla `PagoResumenDiario`
--

CREATE TABLE `PagoResumenDiario` (
  `id` int(11) NOT NULL,
  `origen` varchar(30) NOT NULL,
  `referencia_id` int(11) NOT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `diagnostico_id` int(11) DEFAULT NULL,
  `adeudo_id` int(11) DEFAULT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `paciente_nombre` varchar(150) NOT NULL,
  `psicologo_id` int(11) DEFAULT NULL,
  `psicologo_nombre` varchar(150) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `fecha_pago` datetime NOT NULL,
  `fecha_corte` date NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Pagos`
--

CREATE TABLE `Pagos` (
  `id` int(11) NOT NULL,
  `origen` varchar(30) NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `paquete_id` int(11) DEFAULT NULL,
  `paciente_id` int(11) NOT NULL,
  `paciente_nombre` varchar(150) NOT NULL,
  `psicologo_id` int(11) DEFAULT NULL,
  `psicologo_nombre` varchar(150) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `fecha_pago` datetime NOT NULL,
  `fecha_corte` date NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Paquetes`
--

CREATE TABLE `Paquetes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `primer_pago_monto` decimal(10,2) NOT NULL,
  `saldo_adicional` decimal(10,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

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
-- Estructura de tabla para la tabla `Precios`
--

CREATE TABLE `Precios` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `costo` decimal(14,2) NOT NULL,
  `activo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `PromocionesCatalogo`
--

CREATE TABLE `PromocionesCatalogo` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tipo_descuento` enum('porcentaje','monto_fijo','sesion_extra') NOT NULL DEFAULT 'porcentaje',
  `aplica_a` enum('paquete','cita_seguimiento') NOT NULL DEFAULT 'cita_seguimiento',
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vigencia_inicio` date DEFAULT NULL,
  `vigencia_fin` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ProspectoEstatusSeguimiento`
--

CREATE TABLE `ProspectoEstatusSeguimiento` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ProspectosSeguimiento`
--

CREATE TABLE `ProspectosSeguimiento` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `total_completadas` int(11) NOT NULL DEFAULT 0,
  `total_canceladas` int(11) NOT NULL DEFAULT 0,
  `calificacion` decimal(5,2) NOT NULL DEFAULT 0.00,
  `ultima_cita` datetime DEFAULT NULL,
  `estatus_id` int(11) NOT NULL DEFAULT 1,
  `promocion_id` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `origen_reporte` varchar(100) NOT NULL DEFAULT 'prospectos_promocion',
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ProspectosSeguimientoComunicacionMensajes`
--

CREATE TABLE `ProspectosSeguimientoComunicacionMensajes` (
  `id` int(11) NOT NULL,
  `seguimiento_id` int(11) DEFAULT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `estatus_id` int(11) NOT NULL,
  `plantilla` text NOT NULL,
  `mensaje_renderizado` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ProspectosSeguimientoHistorial`
--

CREATE TABLE `ProspectosSeguimientoHistorial` (
  `id` int(11) NOT NULL,
  `prospecto_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `estatus_id` int(11) NOT NULL,
  `promocion_id` int(11) DEFAULT NULL,
  `promocion_texto` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `calificacion` decimal(5,2) NOT NULL DEFAULT 0.00,
  `origen` varchar(50) NOT NULL DEFAULT 'app',
  `usuario_id` int(11) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ReservacionContinua`
--

CREATE TABLE `ReservacionContinua` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `psicologo_id` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `hora_inicio` time NOT NULL,
  `tiempo` int(11) NOT NULL DEFAULT 60,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `forzada` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ReservacionContinuaDia`
--

CREATE TABLE `ReservacionContinuaDia` (
  `id` int(11) NOT NULL,
  `reservacion_id` int(11) NOT NULL,
  `dia_semana` tinyint(4) NOT NULL
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
-- Estructura de tabla para la tabla `ReunionInterna`
--

CREATE TABLE `ReunionInterna` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `inicio` datetime NOT NULL,
  `fin` datetime NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ReunionInternaPsicologo`
--

CREATE TABLE `ReunionInternaPsicologo` (
  `id` int(11) NOT NULL,
  `reunion_id` int(11) NOT NULL,
  `psicologo_id` int(11) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

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
-- Estructura de tabla para la tabla `SaldoMovimientos`
--

CREATE TABLE `SaldoMovimientos` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `saldo_anterior` decimal(10,2) NOT NULL,
  `saldo_nuevo` decimal(10,2) NOT NULL,
  `pago_id` int(11) DEFAULT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `paquete_id` int(11) DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento_comunicacion_mensajes`
--

CREATE TABLE `seguimiento_comunicacion_mensajes` (
  `id` int(11) NOT NULL,
  `seguimiento_id` int(11) DEFAULT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `estatus_id` int(11) NOT NULL,
  `plantilla` text NOT NULL,
  `mensaje_renderizado` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `SolicitudAjusteSaldo`
--

CREATE TABLE `SolicitudAjusteSaldo` (
  `id` int(11) NOT NULL,
  `nino_id` int(11) NOT NULL,
  `solicitado_por` int(11) NOT NULL,
  `aprobado_por` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `saldo_anterior` decimal(10,2) NOT NULL,
  `saldo_solicitado` decimal(10,2) NOT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `respuesta` varchar(255) DEFAULT NULL,
  `estatus` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `fecha_solicitud` datetime NOT NULL,
  `fecha_resolucion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `SolicitudReprogramacion`
--

CREATE TABLE `SolicitudReprogramacion` (
  `id` int(11) NOT NULL,
  `cita_id` int(11) NOT NULL,
  `fecha_anterior` datetime NOT NULL,
  `nueva_fecha` datetime NOT NULL,
  `estatus` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `tipo` enum('reprogramacion','cancelacion') NOT NULL DEFAULT 'reprogramacion',
  `solicitado_por` int(11) NOT NULL,
  `comentarios` text DEFAULT NULL,
  `fecha_solicitud` datetime NOT NULL,
  `aprobado_por` int(11) DEFAULT NULL,
  `fecha_respuesta` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `soporte_tickets`
--

CREATE TABLE `soporte_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `creado_por` int(11) NOT NULL,
  `problema_general` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `area_problema` varchar(150) DEFAULT NULL,
  `nino_id` int(11) DEFAULT NULL,
  `estado` enum('abierto','en_progreso','resuelto','cerrado') NOT NULL DEFAULT 'abierto',
  `asignado_a` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `soporte_ticket_adjuntos`
--

CREATE TABLE `soporte_ticket_adjuntos` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `uploader_id` int(11) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `nombre_original` varchar(255) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `tamano` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `soporte_ticket_mensajes`
--

CREATE TABLE `soporte_ticket_mensajes` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_id` int(10) UNSIGNED NOT NULL,
  `autor_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `spu_flujos`
--

CREATE TABLE `spu_flujos` (
  `id_flujo` int(11) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icon` varchar(80) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `spu_paciente_flujos`
--

CREATE TABLE `spu_paciente_flujos` (
  `id_paciente_flujo` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `id_flujo` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `actualizado_por` int(11) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `spu_paciente_tareas`
--

CREATE TABLE `spu_paciente_tareas` (
  `id_paciente_tarea` int(11) NOT NULL,
  `id_nino` int(11) NOT NULL,
  `id_tarea` int(11) NOT NULL,
  `status` enum('no_iniciado','en_proceso','completado') NOT NULL DEFAULT 'no_iniciado',
  `actualizado_por` int(11) DEFAULT NULL,
  `completado_en` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `spu_perfiles`
--

CREATE TABLE `spu_perfiles` (
  `id_perfil` int(11) NOT NULL,
  `id_flujo` int(11) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icon` varchar(80) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `spu_tareas`
--

CREATE TABLE `spu_tareas` (
  `id_tarea` int(11) NOT NULL,
  `id_perfil` int(11) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `titulo` varchar(180) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `evidencia` enum('none','optional','required') NOT NULL DEFAULT 'none',
  `alerta_tipo` enum('none','citas') NOT NULL DEFAULT 'none',
  `alerta_cantidad` int(11) NOT NULL DEFAULT 0,
  `tipos_permitidos` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp()
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

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `AdeudosDiagnostico`
--
ALTER TABLE `AdeudosDiagnostico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_adeudos_diag_nino` (`nino_id`),
  ADD KEY `idx_adeudos_diag_estatus` (`estatus_id`),
  ADD KEY `idx_adeudos_diag_psicologo` (`psicologo_id`),
  ADD KEY `idx_adeudos_diag_cita_inicial` (`cita_inicial_id`);

--
-- Indices de la tabla `AdeudosDiagnosticoPagos`
--
ALTER TABLE `AdeudosDiagnosticoPagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_adeudos_diag_pagos_adeudo` (`adeudo_id`);

--
-- Indices de la tabla `Cita`
--
ALTER TABLE `Cita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cu` (`idGenerado`),
  ADD KEY `kf_cn` (`IdNino`),
  ADD KEY `kk_cnu` (`IdUsuario`),
  ADD KEY `fk_estatus_c` (`Estatus`),
  ADD KEY `fk_cita_paquete` (`paquete_id`),
  ADD KEY `fk_cita_adeudo_diagnostico` (`adeudo_diagnostico_id`),
  ADD KEY `fk_cita_diagnostico` (`diagnostico_id`);

--
-- Indices de la tabla `CitaPagos`
--
ALTER TABLE `CitaPagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cita_pagos_cita_id` (`cita_id`);

--
-- Indices de la tabla `Clientes`
--
ALTER TABLE `Clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `colaTickets`
--
ALTER TABLE `colaTickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estado_creado_en` (`estado`,`creado_en`),
  ADD KEY `fk_colatickets_cita` (`id_cita`);

--
-- Indices de la tabla `colores`
--
ALTER TABLE `colores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `colores_nombre_unique` (`nombre`),
  ADD UNIQUE KEY `colores_codigo_hex_unique` (`codigo_hex`);

--
-- Indices de la tabla `comprobantes_inscripcion`
--
ALTER TABLE `comprobantes_inscripcion`
  ADD PRIMARY KEY (`id_comprobante`),
  ADD KEY `id_inscripcion` (`id_inscripcion`);

--
-- Indices de la tabla `contenido_curso`
--
ALTER TABLE `contenido_curso`
  ADD PRIMARY KEY (`id_contenido`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `CorteCaja`
--
ALTER TABLE `CorteCaja`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cortecaja_fecha` (`fecha`),
  ADD KEY `idx_cortecaja_registrado_por` (`registrado_por`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id_curso`),
  ADD UNIQUE KEY `clave_curso` (`clave_curso`);

--
-- Indices de la tabla `DemoPagos`
--
ALTER TABLE `DemoPagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_demo_pagos_origen_referencia` (`origen`,`referencia_id`),
  ADD KEY `idx_demo_pagos_cita` (`cita_id`),
  ADD KEY `idx_demo_pagos_paquete` (`paquete_id`),
  ADD KEY `idx_demo_pagos_paciente` (`paciente_id`),
  ADD KEY `idx_demo_pagos_fecha_corte` (`fecha_corte`),
  ADD KEY `idx_demo_pagos_metodo` (`metodo_pago`);

--
-- Indices de la tabla `DemoSaldoMovimientos`
--
ALTER TABLE `DemoSaldoMovimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_demo_saldo_paciente` (`paciente_id`),
  ADD KEY `idx_demo_saldo_tipo` (`tipo`),
  ADD KEY `idx_demo_saldo_pago` (`pago_id`),
  ADD KEY `idx_demo_saldo_cita` (`cita_id`),
  ADD KEY `idx_demo_saldo_paquete` (`paquete_id`);

--
-- Indices de la tabla `DiagnosticoPagos`
--
ALTER TABLE `DiagnosticoPagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diagnostico_pagos_diagnostico` (`diagnostico_id`);

--
-- Indices de la tabla `Diagnosticos`
--
ALTER TABLE `Diagnosticos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diagnosticos_nino` (`nino_id`),
  ADD KEY `idx_diagnosticos_estatus` (`estatus_id`),
  ADD KEY `idx_diagnosticos_psicologo` (`psicologo_id`),
  ADD KEY `idx_diagnosticos_cita_inicial` (`cita_inicial_id`);

--
-- Indices de la tabla `documentos_pago`
--
ALTER TABLE `documentos_pago`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `id_inscripcion` (`id_inscripcion`);

--
-- Indices de la tabla `Estatus`
--
ALTER TABLE `Estatus`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `exp_adjuntos_evaluacion`
--
ALTER TABLE `exp_adjuntos_evaluacion`
  ADD PRIMARY KEY (`id_adjunto`),
  ADD KEY `id_evaluacion` (`id_evaluacion`);

--
-- Indices de la tabla `exp_areas_evaluacion`
--
ALTER TABLE `exp_areas_evaluacion`
  ADD PRIMARY KEY (`id_area`);

--
-- Indices de la tabla `exp_criterios_evaluacion`
--
ALTER TABLE `exp_criterios_evaluacion`
  ADD PRIMARY KEY (`id_criterio`);

--
-- Indices de la tabla `exp_evaluaciones`
--
ALTER TABLE `exp_evaluaciones`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD KEY `id_nino` (`id_nino`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_area` (`id_area`);

--
-- Indices de la tabla `exp_evaluacion_examen`
--
ALTER TABLE `exp_evaluacion_examen`
  ADD PRIMARY KEY (`id_eval`),
  ADD KEY `id_nino` (`id_nino`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `exp_evaluacion_examen_ibfk_3` (`id_examen`);

--
-- Indices de la tabla `exp_evaluacion_fotos`
--
ALTER TABLE `exp_evaluacion_fotos`
  ADD PRIMARY KEY (`id_eval_foto`),
  ADD KEY `id_nino` (`id_nino`);

--
-- Indices de la tabla `exp_evaluacion_fotos_imagenes`
--
ALTER TABLE `exp_evaluacion_fotos_imagenes`
  ADD PRIMARY KEY (`id_imagen`),
  ADD KEY `id_eval_foto` (`id_eval_foto`);

--
-- Indices de la tabla `exp_examenes`
--
ALTER TABLE `exp_examenes`
  ADD PRIMARY KEY (`id_examen`),
  ADD KEY `exp_examenes_ibfk_1` (`id_area`),
  ADD KEY `exp_examenes_ibfk_2` (`id_usuario`);

--
-- Indices de la tabla `exp_nino_criterio`
--
ALTER TABLE `exp_nino_criterio`
  ADD PRIMARY KEY (`id_nino`,`id_criterio`),
  ADD KEY `id_criterio` (`id_criterio`);

--
-- Indices de la tabla `exp_opciones_pregunta`
--
ALTER TABLE `exp_opciones_pregunta`
  ADD PRIMARY KEY (`id_opcion`),
  ADD KEY `fk_opcion_exam` (`id_exam`);

--
-- Indices de la tabla `exp_preguntas_evaluacion`
--
ALTER TABLE `exp_preguntas_evaluacion`
  ADD PRIMARY KEY (`id_pregunta`),
  ADD KEY `exp_preguntas_evaluacion_ibfk_1` (`id_seccion`);

--
-- Indices de la tabla `exp_pregunta_opcion`
--
ALTER TABLE `exp_pregunta_opcion`
  ADD PRIMARY KEY (`id_pregunta`,`id_opcion`),
  ADD KEY `exp_pregunta_opcion_ibfk_2` (`id_opcion`);

--
-- Indices de la tabla `exp_progreso_general`
--
ALTER TABLE `exp_progreso_general`
  ADD PRIMARY KEY (`id_progreso`),
  ADD KEY `id_nino` (`id_nino`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `exp_secciones_examen`
--
ALTER TABLE `exp_secciones_examen`
  ADD PRIMARY KEY (`id_seccion`),
  ADD KEY `exp_secciones_examen_ibfk_1` (`id_examen`);

--
-- Indices de la tabla `exp_valoraciones_sesion`
--
ALTER TABLE `exp_valoraciones_sesion`
  ADD PRIMARY KEY (`id_valoracion`),
  ADD KEY `id_nino` (`id_nino`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `exp_valoracion_detalle`
--
ALTER TABLE `exp_valoracion_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_valoracion` (`id_valoracion`),
  ADD KEY `id_criterio` (`id_criterio`);

--
-- Indices de la tabla `frecuencia_pago`
--
ALTER TABLE `frecuencia_pago`
  ADD PRIMARY KEY (`id_frecuencia`);

--
-- Indices de la tabla `HistorialEstatus`
--
ALTER TABLE `HistorialEstatus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cita_hist` (`idCita`),
  ADD KEY `fk_estatus_hist` (`idEstatus`),
  ADD KEY `fk_usuario_hist` (`idUsuario`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD KEY `id_curso` (`id_curso`),
  ADD KEY `id_participante` (`id_participante`),
  ADD KEY `inscripciones_ibfk_3` (`IdOpcionPago`);

--
-- Indices de la tabla `LogSistema`
--
ALTER TABLE `LogSistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_fecha` (`fecha`),
  ADD KEY `idx_logs_modulo` (`modulo`),
  ADD KEY `idx_logs_usuario` (`usuario_id`);

--
-- Indices de la tabla `MensajeCreado`
--
ALTER TABLE `MensajeCreado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mensajecreado_fecha` (`fecha`),
  ADD KEY `idx_mensajecreado_mensaje` (`mensaje_id`);

--
-- Indices de la tabla `nino`
--
ALTER TABLE `nino`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_nino_tutor` (`idtutor`);

--
-- Indices de la tabla `NinoPagosPendiente`
--
ALTER TABLE `NinoPagosPendiente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nino_pagos_pendiente_nino` (`nino_id`),
  ADD KEY `idx_nino_pagos_pendiente_cita` (`cita_id`);

--
-- Indices de la tabla `opciones_pago`
--
ALTER TABLE `opciones_pago`
  ADD PRIMARY KEY (`id_opcion`),
  ADD KEY `fk_opciones_pago_frecuencia` (`id_frecuencia`);

--
-- Indices de la tabla `PagoResumenDiario`
--
ALTER TABLE `PagoResumenDiario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pago_resumen_fecha_corte` (`fecha_corte`),
  ADD KEY `idx_pago_resumen_fecha_pago` (`fecha_pago`),
  ADD KEY `idx_pago_resumen_metodo` (`metodo_pago`),
  ADD KEY `idx_pago_resumen_psicologo` (`psicologo_id`),
  ADD KEY `idx_pago_resumen_paciente` (`paciente_id`),
  ADD KEY `idx_pago_resumen_origen_referencia` (`origen`,`referencia_id`);

--
-- Indices de la tabla `Pagos`
--
ALTER TABLE `Pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pagos_origen_referencia` (`origen`,`referencia_id`),
  ADD KEY `idx_pagos_cita` (`cita_id`),
  ADD KEY `idx_pagos_paquete` (`paquete_id`),
  ADD KEY `idx_pagos_paciente` (`paciente_id`),
  ADD KEY `idx_pagos_fecha_corte` (`fecha_corte`),
  ADD KEY `idx_pagos_metodo` (`metodo_pago`);

--
-- Indices de la tabla `Paquetes`
--
ALTER TABLE `Paquetes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_paquetes_nombre` (`nombre`);

--
-- Indices de la tabla `participantes`
--
ALTER TABLE `participantes`
  ADD PRIMARY KEY (`id_participante`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `Precios`
--
ALTER TABLE `Precios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `PromocionesCatalogo`
--
ALTER TABLE `PromocionesCatalogo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `ProspectoEstatusSeguimiento`
--
ALTER TABLE `ProspectoEstatusSeguimiento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `ProspectosSeguimiento`
--
ALTER TABLE `ProspectosSeguimiento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prospecto_paciente` (`paciente_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `estatus_id` (`estatus_id`),
  ADD KEY `promocion_id` (`promocion_id`);

--
-- Indices de la tabla `ProspectosSeguimientoComunicacionMensajes`
--
ALTER TABLE `ProspectosSeguimientoComunicacionMensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pscm_paciente` (`paciente_id`),
  ADD KEY `idx_pscm_seguimiento` (`seguimiento_id`);

--
-- Indices de la tabla `ProspectosSeguimientoHistorial`
--
ALTER TABLE `ProspectosSeguimientoHistorial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_prospecto` (`prospecto_id`),
  ADD KEY `idx_hist_paciente` (`paciente_id`),
  ADD KEY `idx_hist_creado` (`creado_en`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `estatus_id` (`estatus_id`),
  ADD KEY `promocion_id` (`promocion_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `rechazos_inscripciones`
--
ALTER TABLE `rechazos_inscripciones`
  ADD PRIMARY KEY (`id_rechazo`),
  ADD KEY `id_inscripcion` (`id_inscripcion`);

--
-- Indices de la tabla `ReservacionContinua`
--
ALTER TABLE `ReservacionContinua`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reservacion_continua_paciente` (`paciente_id`),
  ADD KEY `fk_reservacion_continua_psicologo` (`psicologo_id`),
  ADD KEY `fk_reservacion_continua_creado_por` (`creado_por`);

--
-- Indices de la tabla `ReservacionContinuaDia`
--
ALTER TABLE `ReservacionContinuaDia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_reservacion_dia` (`reservacion_id`,`dia_semana`);

--
-- Indices de la tabla `reuniones_zoom`
--
ALTER TABLE `reuniones_zoom`
  ADD PRIMARY KEY (`id_reunion`),
  ADD KEY `id_curso` (`id_curso`);

--
-- Indices de la tabla `ReunionInterna`
--
ALTER TABLE `ReunionInterna`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reunion_inicio` (`inicio`);

--
-- Indices de la tabla `ReunionInternaPsicologo`
--
ALTER TABLE `ReunionInternaPsicologo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_reunion_psicologo` (`reunion_id`,`psicologo_id`),
  ADD KEY `idx_psicologo` (`psicologo_id`);

--
-- Indices de la tabla `Rol`
--
ALTER TABLE `Rol`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `SaldoMovimientos`
--
ALTER TABLE `SaldoMovimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_saldo_movimientos_paciente` (`paciente_id`),
  ADD KEY `idx_saldo_movimientos_tipo` (`tipo`),
  ADD KEY `idx_saldo_movimientos_pago` (`pago_id`),
  ADD KEY `idx_saldo_movimientos_cita` (`cita_id`),
  ADD KEY `idx_saldo_movimientos_paquete` (`paquete_id`);

--
-- Indices de la tabla `seguimiento_comunicacion_mensajes`
--
ALTER TABLE `seguimiento_comunicacion_mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scm_paciente` (`paciente_id`),
  ADD KEY `idx_scm_seguimiento` (`seguimiento_id`);

--
-- Indices de la tabla `SolicitudAjusteSaldo`
--
ALTER TABLE `SolicitudAjusteSaldo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitud_ajuste_estatus` (`estatus`),
  ADD KEY `idx_solicitud_ajuste_fecha` (`fecha_solicitud`),
  ADD KEY `fk_solicitud_ajuste_nino` (`nino_id`),
  ADD KEY `fk_solicitud_ajuste_solicitante` (`solicitado_por`),
  ADD KEY `fk_solicitud_ajuste_aprobador` (`aprobado_por`);

--
-- Indices de la tabla `SolicitudReprogramacion`
--
ALTER TABLE `SolicitudReprogramacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitud_estatus` (`estatus`),
  ADD KEY `idx_solicitud_cita` (`cita_id`),
  ADD KEY `fk_solicitud_solicitante` (`solicitado_por`),
  ADD KEY `fk_solicitud_aprobador` (`aprobado_por`);

--
-- Indices de la tabla `soporte_tickets`
--
ALTER TABLE `soporte_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_soporte_tickets_creado_por` (`creado_por`),
  ADD KEY `idx_soporte_tickets_estado` (`estado`),
  ADD KEY `idx_soporte_tickets_nino_id` (`nino_id`),
  ADD KEY `idx_soporte_tickets_asignado_a` (`asignado_a`),
  ADD KEY `idx_soporte_tickets_created_at` (`created_at`);

--
-- Indices de la tabla `soporte_ticket_adjuntos`
--
ALTER TABLE `soporte_ticket_adjuntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_soporte_ticket_adjuntos_ticket_id` (`ticket_id`),
  ADD KEY `idx_soporte_ticket_adjuntos_uploader_id` (`uploader_id`),
  ADD KEY `idx_soporte_ticket_adjuntos_created_at` (`created_at`);

--
-- Indices de la tabla `soporte_ticket_mensajes`
--
ALTER TABLE `soporte_ticket_mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_soporte_ticket_mensajes_ticket_id` (`ticket_id`),
  ADD KEY `idx_soporte_ticket_mensajes_autor_id` (`autor_id`),
  ADD KEY `idx_soporte_ticket_mensajes_created_at` (`created_at`);

--
-- Indices de la tabla `spu_flujos`
--
ALTER TABLE `spu_flujos`
  ADD PRIMARY KEY (`id_flujo`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `spu_paciente_flujos`
--
ALTER TABLE `spu_paciente_flujos`
  ADD PRIMARY KEY (`id_paciente_flujo`),
  ADD UNIQUE KEY `uk_spu_paciente_flujo` (`id_nino`,`id_flujo`),
  ADD KEY `id_flujo` (`id_flujo`);

--
-- Indices de la tabla `spu_paciente_tareas`
--
ALTER TABLE `spu_paciente_tareas`
  ADD PRIMARY KEY (`id_paciente_tarea`),
  ADD UNIQUE KEY `uk_spu_paciente_tarea` (`id_nino`,`id_tarea`),
  ADD KEY `id_tarea` (`id_tarea`);

--
-- Indices de la tabla `spu_perfiles`
--
ALTER TABLE `spu_perfiles`
  ADD PRIMARY KEY (`id_perfil`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `id_flujo` (`id_flujo`);

--
-- Indices de la tabla `spu_tareas`
--
ALTER TABLE `spu_tareas`
  ADD PRIMARY KEY (`id_tarea`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `id_perfil` (`id_perfil`);

--
-- Indices de la tabla `Usuarios`
--
ALTER TABLE `Usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_rol` (`IdRol`),
  ADD KEY `fk_usuarios_color` (`color_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `AdeudosDiagnostico`
--
ALTER TABLE `AdeudosDiagnostico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `AdeudosDiagnosticoPagos`
--
ALTER TABLE `AdeudosDiagnosticoPagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Cita`
--
ALTER TABLE `Cita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CitaPagos`
--
ALTER TABLE `CitaPagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Clientes`
--
ALTER TABLE `Clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `colaTickets`
--
ALTER TABLE `colaTickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `colores`
--
ALTER TABLE `colores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comprobantes_inscripcion`
--
ALTER TABLE `comprobantes_inscripcion`
  MODIFY `id_comprobante` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenido_curso`
--
ALTER TABLE `contenido_curso`
  MODIFY `id_contenido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `CorteCaja`
--
ALTER TABLE `CorteCaja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `DemoPagos`
--
ALTER TABLE `DemoPagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `DemoSaldoMovimientos`
--
ALTER TABLE `DemoSaldoMovimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `DiagnosticoPagos`
--
ALTER TABLE `DiagnosticoPagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Diagnosticos`
--
ALTER TABLE `Diagnosticos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos_pago`
--
ALTER TABLE `documentos_pago`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Estatus`
--
ALTER TABLE `Estatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_adjuntos_evaluacion`
--
ALTER TABLE `exp_adjuntos_evaluacion`
  MODIFY `id_adjunto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_areas_evaluacion`
--
ALTER TABLE `exp_areas_evaluacion`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_criterios_evaluacion`
--
ALTER TABLE `exp_criterios_evaluacion`
  MODIFY `id_criterio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_evaluacion_examen`
--
ALTER TABLE `exp_evaluacion_examen`
  MODIFY `id_eval` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_evaluacion_fotos`
--
ALTER TABLE `exp_evaluacion_fotos`
  MODIFY `id_eval_foto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_evaluacion_fotos_imagenes`
--
ALTER TABLE `exp_evaluacion_fotos_imagenes`
  MODIFY `id_imagen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_examenes`
--
ALTER TABLE `exp_examenes`
  MODIFY `id_examen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_opciones_pregunta`
--
ALTER TABLE `exp_opciones_pregunta`
  MODIFY `id_opcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_preguntas_evaluacion`
--
ALTER TABLE `exp_preguntas_evaluacion`
  MODIFY `id_pregunta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_progreso_general`
--
ALTER TABLE `exp_progreso_general`
  MODIFY `id_progreso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_secciones_examen`
--
ALTER TABLE `exp_secciones_examen`
  MODIFY `id_seccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_valoraciones_sesion`
--
ALTER TABLE `exp_valoraciones_sesion`
  MODIFY `id_valoracion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exp_valoracion_detalle`
--
ALTER TABLE `exp_valoracion_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `frecuencia_pago`
--
ALTER TABLE `frecuencia_pago`
  MODIFY `id_frecuencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `HistorialEstatus`
--
ALTER TABLE `HistorialEstatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id_inscripcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `LogSistema`
--
ALTER TABLE `LogSistema`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `MensajeCreado`
--
ALTER TABLE `MensajeCreado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `nino`
--
ALTER TABLE `nino`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `NinoPagosPendiente`
--
ALTER TABLE `NinoPagosPendiente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `opciones_pago`
--
ALTER TABLE `opciones_pago`
  MODIFY `id_opcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `PagoResumenDiario`
--
ALTER TABLE `PagoResumenDiario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Pagos`
--
ALTER TABLE `Pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Paquetes`
--
ALTER TABLE `Paquetes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `participantes`
--
ALTER TABLE `participantes`
  MODIFY `id_participante` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Precios`
--
ALTER TABLE `Precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `PromocionesCatalogo`
--
ALTER TABLE `PromocionesCatalogo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ProspectoEstatusSeguimiento`
--
ALTER TABLE `ProspectoEstatusSeguimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ProspectosSeguimiento`
--
ALTER TABLE `ProspectosSeguimiento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ProspectosSeguimientoComunicacionMensajes`
--
ALTER TABLE `ProspectosSeguimientoComunicacionMensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ProspectosSeguimientoHistorial`
--
ALTER TABLE `ProspectosSeguimientoHistorial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rechazos_inscripciones`
--
ALTER TABLE `rechazos_inscripciones`
  MODIFY `id_rechazo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ReservacionContinua`
--
ALTER TABLE `ReservacionContinua`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ReservacionContinuaDia`
--
ALTER TABLE `ReservacionContinuaDia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reuniones_zoom`
--
ALTER TABLE `reuniones_zoom`
  MODIFY `id_reunion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ReunionInterna`
--
ALTER TABLE `ReunionInterna`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ReunionInternaPsicologo`
--
ALTER TABLE `ReunionInternaPsicologo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Rol`
--
ALTER TABLE `Rol`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `SaldoMovimientos`
--
ALTER TABLE `SaldoMovimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seguimiento_comunicacion_mensajes`
--
ALTER TABLE `seguimiento_comunicacion_mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `SolicitudAjusteSaldo`
--
ALTER TABLE `SolicitudAjusteSaldo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `SolicitudReprogramacion`
--
ALTER TABLE `SolicitudReprogramacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `soporte_tickets`
--
ALTER TABLE `soporte_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `soporte_ticket_adjuntos`
--
ALTER TABLE `soporte_ticket_adjuntos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `soporte_ticket_mensajes`
--
ALTER TABLE `soporte_ticket_mensajes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `spu_flujos`
--
ALTER TABLE `spu_flujos`
  MODIFY `id_flujo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `spu_paciente_flujos`
--
ALTER TABLE `spu_paciente_flujos`
  MODIFY `id_paciente_flujo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `spu_paciente_tareas`
--
ALTER TABLE `spu_paciente_tareas`
  MODIFY `id_paciente_tarea` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `spu_perfiles`
--
ALTER TABLE `spu_perfiles`
  MODIFY `id_perfil` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `spu_tareas`
--
ALTER TABLE `spu_tareas`
  MODIFY `id_tarea` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Usuarios`
--
ALTER TABLE `Usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `AdeudosDiagnostico`
--
ALTER TABLE `AdeudosDiagnostico`
  ADD CONSTRAINT `fk_adeudos_diag_cita_inicial` FOREIGN KEY (`cita_inicial_id`) REFERENCES `Cita` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adeudos_diag_estatus` FOREIGN KEY (`estatus_id`) REFERENCES `Estatus` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adeudos_diag_nino` FOREIGN KEY (`nino_id`) REFERENCES `nino` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_adeudos_diag_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `Usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `AdeudosDiagnosticoPagos`
--
ALTER TABLE `AdeudosDiagnosticoPagos`
  ADD CONSTRAINT `fk_adeudos_diag_pagos_adeudo` FOREIGN KEY (`adeudo_id`) REFERENCES `AdeudosDiagnostico` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `Cita`
--
ALTER TABLE `Cita`
  ADD CONSTRAINT `fk_cita_adeudo_diagnostico` FOREIGN KEY (`adeudo_diagnostico_id`) REFERENCES `AdeudosDiagnostico` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cita_diagnostico` FOREIGN KEY (`diagnostico_id`) REFERENCES `Diagnosticos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cita_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `Paquetes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cu` FOREIGN KEY (`idGenerado`) REFERENCES `Usuarios` (`id`),
  ADD CONSTRAINT `fk_estatus_c` FOREIGN KEY (`Estatus`) REFERENCES `Estatus` (`id`),
  ADD CONSTRAINT `kf_cn` FOREIGN KEY (`IdNino`) REFERENCES `nino` (`id`),
  ADD CONSTRAINT `kk_cnu` FOREIGN KEY (`IdUsuario`) REFERENCES `Usuarios` (`id`);

--
-- Filtros para la tabla `CitaPagos`
--
ALTER TABLE `CitaPagos`
  ADD CONSTRAINT `fk_cita_pagos_cita` FOREIGN KEY (`cita_id`) REFERENCES `Cita` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `colaTickets`
--
ALTER TABLE `colaTickets`
  ADD CONSTRAINT `fk_colatickets_cita` FOREIGN KEY (`id_cita`) REFERENCES `Cita` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `contenido_curso`
--
ALTER TABLE `contenido_curso`
  ADD CONSTRAINT `contenido_curso_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `CorteCaja`
--
ALTER TABLE `CorteCaja`
  ADD CONSTRAINT `fk_cortecaja_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `Usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `DemoSaldoMovimientos`
--
ALTER TABLE `DemoSaldoMovimientos`
  ADD CONSTRAINT `fk_demo_saldo_pago` FOREIGN KEY (`pago_id`) REFERENCES `DemoPagos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `DiagnosticoPagos`
--
ALTER TABLE `DiagnosticoPagos`
  ADD CONSTRAINT `fk_diagnostico_pagos_diagnostico` FOREIGN KEY (`diagnostico_id`) REFERENCES `Diagnosticos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `Diagnosticos`
--
ALTER TABLE `Diagnosticos`
  ADD CONSTRAINT `fk_diagnosticos_cita_inicial` FOREIGN KEY (`cita_inicial_id`) REFERENCES `Cita` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_diagnosticos_estatus` FOREIGN KEY (`estatus_id`) REFERENCES `Estatus` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_diagnosticos_nino` FOREIGN KEY (`nino_id`) REFERENCES `nino` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_diagnosticos_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `Usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `documentos_pago`
--
ALTER TABLE `documentos_pago`
  ADD CONSTRAINT `documentos_pago_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`);

--
-- Filtros para la tabla `exp_evaluacion_examen`
--
ALTER TABLE `exp_evaluacion_examen`
  ADD CONSTRAINT `exp_evaluacion_examen_ibfk_1` FOREIGN KEY (`id_nino`) REFERENCES `nino` (`id`),
  ADD CONSTRAINT `exp_evaluacion_examen_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `Usuarios` (`id`),
  ADD CONSTRAINT `exp_evaluacion_examen_ibfk_3` FOREIGN KEY (`id_examen`) REFERENCES `exp_examenes` (`id_examen`);

--
-- Filtros para la tabla `exp_evaluacion_fotos`
--
ALTER TABLE `exp_evaluacion_fotos`
  ADD CONSTRAINT `exp_evaluacion_fotos_ibfk_1` FOREIGN KEY (`id_nino`) REFERENCES `nino` (`id`);

--
-- Filtros para la tabla `exp_evaluacion_fotos_imagenes`
--
ALTER TABLE `exp_evaluacion_fotos_imagenes`
  ADD CONSTRAINT `exp_evaluacion_fotos_imagenes_ibfk_1` FOREIGN KEY (`id_eval_foto`) REFERENCES `exp_evaluacion_fotos` (`id_eval_foto`);

--
-- Filtros para la tabla `exp_examenes`
--
ALTER TABLE `exp_examenes`
  ADD CONSTRAINT `exp_examenes_ibfk_1` FOREIGN KEY (`id_area`) REFERENCES `exp_areas_evaluacion` (`id_area`) ON DELETE CASCADE,
  ADD CONSTRAINT `exp_examenes_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `Usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `exp_nino_criterio`
--
ALTER TABLE `exp_nino_criterio`
  ADD CONSTRAINT `exp_nino_criterio_ibfk_1` FOREIGN KEY (`id_nino`) REFERENCES `nino` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exp_nino_criterio_ibfk_2` FOREIGN KEY (`id_criterio`) REFERENCES `exp_criterios_evaluacion` (`id_criterio`) ON DELETE CASCADE;

--
-- Filtros para la tabla `exp_opciones_pregunta`
--
ALTER TABLE `exp_opciones_pregunta`
  ADD CONSTRAINT `fk_opcion_exam` FOREIGN KEY (`id_exam`) REFERENCES `exp_examenes` (`id_examen`) ON DELETE CASCADE;

--
-- Filtros para la tabla `exp_preguntas_evaluacion`
--
ALTER TABLE `exp_preguntas_evaluacion`
  ADD CONSTRAINT `exp_preguntas_evaluacion_ibfk_1` FOREIGN KEY (`id_seccion`) REFERENCES `exp_secciones_examen` (`id_seccion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `exp_pregunta_opcion`
--
ALTER TABLE `exp_pregunta_opcion`
  ADD CONSTRAINT `exp_pregunta_opcion_ibfk_1` FOREIGN KEY (`id_pregunta`) REFERENCES `exp_preguntas_evaluacion` (`id_pregunta`) ON DELETE CASCADE,
  ADD CONSTRAINT `exp_pregunta_opcion_ibfk_2` FOREIGN KEY (`id_opcion`) REFERENCES `exp_opciones_pregunta` (`id_opcion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `exp_secciones_examen`
--
ALTER TABLE `exp_secciones_examen`
  ADD CONSTRAINT `exp_secciones_examen_ibfk_1` FOREIGN KEY (`id_examen`) REFERENCES `exp_examenes` (`id_examen`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `exp_valoraciones_sesion`
--
ALTER TABLE `exp_valoraciones_sesion`
  ADD CONSTRAINT `exp_valoraciones_sesion_ibfk_1` FOREIGN KEY (`id_nino`) REFERENCES `nino` (`id`),
  ADD CONSTRAINT `exp_valoraciones_sesion_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `Usuarios` (`id`);

--
-- Filtros para la tabla `exp_valoracion_detalle`
--
ALTER TABLE `exp_valoracion_detalle`
  ADD CONSTRAINT `exp_valoracion_detalle_ibfk_1` FOREIGN KEY (`id_valoracion`) REFERENCES `exp_valoraciones_sesion` (`id_valoracion`) ON DELETE CASCADE,
  ADD CONSTRAINT `exp_valoracion_detalle_ibfk_2` FOREIGN KEY (`id_criterio`) REFERENCES `exp_criterios_evaluacion` (`id_criterio`);

--
-- Filtros para la tabla `HistorialEstatus`
--
ALTER TABLE `HistorialEstatus`
  ADD CONSTRAINT `fk_cita_hist` FOREIGN KEY (`idCita`) REFERENCES `Cita` (`id`),
  ADD CONSTRAINT `fk_estatus_hist` FOREIGN KEY (`idEstatus`) REFERENCES `Estatus` (`id`),
  ADD CONSTRAINT `fk_usuario_hist` FOREIGN KEY (`idUsuario`) REFERENCES `Usuarios` (`id`);

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`),
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`id_participante`) REFERENCES `participantes` (`id_participante`),
  ADD CONSTRAINT `inscripciones_ibfk_3` FOREIGN KEY (`IdOpcionPago`) REFERENCES `opciones_pago` (`id_opcion`);

--
-- Filtros para la tabla `nino`
--
ALTER TABLE `nino`
  ADD CONSTRAINT `fk_nino_tutor` FOREIGN KEY (`idtutor`) REFERENCES `Clientes` (`id`);

--
-- Filtros para la tabla `NinoPagosPendiente`
--
ALTER TABLE `NinoPagosPendiente`
  ADD CONSTRAINT `fk_nino_pagos_pendiente_cita` FOREIGN KEY (`cita_id`) REFERENCES `Cita` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nino_pagos_pendiente_nino` FOREIGN KEY (`nino_id`) REFERENCES `nino` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `opciones_pago`
--
ALTER TABLE `opciones_pago`
  ADD CONSTRAINT `fk_opciones_pago_frecuencia` FOREIGN KEY (`id_frecuencia`) REFERENCES `frecuencia_pago` (`id_frecuencia`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `ProspectosSeguimiento`
--
ALTER TABLE `ProspectosSeguimiento`
  ADD CONSTRAINT `ProspectosSeguimiento_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `nino` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ProspectosSeguimiento_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `Clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ProspectosSeguimiento_ibfk_3` FOREIGN KEY (`estatus_id`) REFERENCES `ProspectoEstatusSeguimiento` (`id`),
  ADD CONSTRAINT `ProspectosSeguimiento_ibfk_4` FOREIGN KEY (`promocion_id`) REFERENCES `PromocionesCatalogo` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ProspectosSeguimientoHistorial`
--
ALTER TABLE `ProspectosSeguimientoHistorial`
  ADD CONSTRAINT `ProspectosSeguimientoHistorial_ibfk_1` FOREIGN KEY (`prospecto_id`) REFERENCES `ProspectosSeguimiento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ProspectosSeguimientoHistorial_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `nino` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ProspectosSeguimientoHistorial_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `Clientes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ProspectosSeguimientoHistorial_ibfk_4` FOREIGN KEY (`estatus_id`) REFERENCES `ProspectoEstatusSeguimiento` (`id`),
  ADD CONSTRAINT `ProspectosSeguimientoHistorial_ibfk_5` FOREIGN KEY (`promocion_id`) REFERENCES `PromocionesCatalogo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ProspectosSeguimientoHistorial_ibfk_6` FOREIGN KEY (`usuario_id`) REFERENCES `Usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `rechazos_inscripciones`
--
ALTER TABLE `rechazos_inscripciones`
  ADD CONSTRAINT `rechazos_inscripciones_ibfk_1` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripciones` (`id_inscripcion`);

--
-- Filtros para la tabla `ReservacionContinua`
--
ALTER TABLE `ReservacionContinua`
  ADD CONSTRAINT `fk_reservacion_continua_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `Usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservacion_continua_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `nino` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservacion_continua_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `Usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `ReservacionContinuaDia`
--
ALTER TABLE `ReservacionContinuaDia`
  ADD CONSTRAINT `fk_reservacion_continua_dia_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `ReservacionContinua` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reuniones_zoom`
--
ALTER TABLE `reuniones_zoom`
  ADD CONSTRAINT `reuniones_zoom_ibfk_1` FOREIGN KEY (`id_curso`) REFERENCES `cursos` (`id_curso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ReunionInternaPsicologo`
--
ALTER TABLE `ReunionInternaPsicologo`
  ADD CONSTRAINT `fk_reunion_interna` FOREIGN KEY (`reunion_id`) REFERENCES `ReunionInterna` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reunion_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `Usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `SaldoMovimientos`
--
ALTER TABLE `SaldoMovimientos`
  ADD CONSTRAINT `fk_saldo_movimientos_pago` FOREIGN KEY (`pago_id`) REFERENCES `Pagos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `SolicitudAjusteSaldo`
--
ALTER TABLE `SolicitudAjusteSaldo`
  ADD CONSTRAINT `fk_solicitud_ajuste_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `Usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_solicitud_ajuste_nino` FOREIGN KEY (`nino_id`) REFERENCES `nino` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_solicitud_ajuste_solicitante` FOREIGN KEY (`solicitado_por`) REFERENCES `Usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `SolicitudReprogramacion`
--
ALTER TABLE `SolicitudReprogramacion`
  ADD CONSTRAINT `fk_solicitud_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `Usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_solicitud_cita` FOREIGN KEY (`cita_id`) REFERENCES `Cita` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_solicitud_solicitante` FOREIGN KEY (`solicitado_por`) REFERENCES `Usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `spu_paciente_flujos`
--
ALTER TABLE `spu_paciente_flujos`
  ADD CONSTRAINT `spu_paciente_flujos_ibfk_1` FOREIGN KEY (`id_flujo`) REFERENCES `spu_flujos` (`id_flujo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `spu_paciente_tareas`
--
ALTER TABLE `spu_paciente_tareas`
  ADD CONSTRAINT `spu_paciente_tareas_ibfk_1` FOREIGN KEY (`id_tarea`) REFERENCES `spu_tareas` (`id_tarea`) ON DELETE CASCADE;

--
-- Filtros para la tabla `spu_perfiles`
--
ALTER TABLE `spu_perfiles`
  ADD CONSTRAINT `spu_perfiles_ibfk_1` FOREIGN KEY (`id_flujo`) REFERENCES `spu_flujos` (`id_flujo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `spu_tareas`
--
ALTER TABLE `spu_tareas`
  ADD CONSTRAINT `spu_tareas_ibfk_1` FOREIGN KEY (`id_perfil`) REFERENCES `spu_perfiles` (`id_perfil`) ON DELETE CASCADE;

--
-- Filtros para la tabla `Usuarios`
--
ALTER TABLE `Usuarios`
  ADD CONSTRAINT `fk_user_rol` FOREIGN KEY (`IdRol`) REFERENCES `Rol` (`id`),
  ADD CONSTRAINT `fk_usuarios_color` FOREIGN KEY (`color_id`) REFERENCES `colores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
