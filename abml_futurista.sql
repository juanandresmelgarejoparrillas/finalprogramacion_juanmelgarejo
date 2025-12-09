-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-12-2025 a las 01:27:15
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
-- Base de datos: `abml_futurista`
--
CREATE DATABASE IF NOT EXISTS `abml_futurista` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `abml_futurista`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `correo`, `telefono`, `estado`, `fecha_creacion`) VALUES
(1, 'Juan Andres', 'juanandresmelgarejoparrillas@gmail.com', '15570918', 1, '2025-11-24 02:21:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `correo`, `telefono`, `estado`, `fecha_creacion`) VALUES
(1, 'efv', '', '', 0, '2025-11-24 02:21:48'),
(2, 'efv', 'juanandresmelgarejoparrillas@gmail.com', '3436573663', 1, '2025-11-24 02:22:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transacciones`
--

CREATE TABLE `transacciones` (
  `id` int(11) NOT NULL,
  `tipo` enum('factura','recibo','nota_credito','nota_debito','gasto') NOT NULL,
  `tipo_entidad` enum('cliente','proveedor') NOT NULL,
  `entidad_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` date NOT NULL,
  `transaccion_relacionada_id` int(11) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `letra` char(1) DEFAULT NULL,
  `condicion_pago` enum('contado','cuenta_corriente') DEFAULT NULL,
  `saldo_pendiente` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `transacciones`
--

INSERT INTO `transacciones` (`id`, `tipo`, `tipo_entidad`, `entidad_id`, `monto`, `descripcion`, `fecha`, `transaccion_relacionada_id`, `estado`, `fecha_creacion`, `numero_comprobante`, `letra`, `condicion_pago`, `saldo_pendiente`) VALUES
(1, 'factura', 'cliente', 1, 2000.00, NULL, '2025-12-08', NULL, 1, '2025-12-08 03:27:39', '0002-2344', 'A', 'contado', 0.00),
(2, 'recibo', 'cliente', 1, 2000.00, NULL, '2025-12-08', 1, 1, '2025-12-08 03:27:39', NULL, NULL, NULL, 0.00),
(3, 'factura', 'cliente', 1, 3000.00, NULL, '2025-12-08', NULL, 1, '2025-12-08 03:27:53', '21123123312', 'A', 'cuenta_corriente', 3000.00),
(4, 'factura', 'proveedor', 2, 300000.00, NULL, '2025-12-08', NULL, 1, '2025-12-08 03:28:17', '21312312', 'A', 'cuenta_corriente', 300000.00),
(5, 'factura', 'proveedor', 2, 30000.00, NULL, '2025-12-06', NULL, 1, '2025-12-08 03:28:36', '0002-2354', 'A', 'contado', 0.00),
(6, 'recibo', 'proveedor', 2, 30000.00, NULL, '2025-12-06', 5, 1, '2025-12-08 03:28:36', NULL, NULL, NULL, 0.00),
(7, '', 'proveedor', 0, 6000.00, 'Se compro pintura para pintar caños', '2025-12-08', NULL, 1, '2025-12-08 21:36:49', 'Pintureria', NULL, NULL, 0.00),
(8, '', 'proveedor', 0, 6000.00, 'Se compro pintura para pintar caños', '2025-12-08', NULL, 1, '2025-12-08 21:36:56', 'Pintureria', NULL, NULL, 0.00),
(9, 'gasto', 'proveedor', 0, 15000.00, 'para pintar la oficina', '2025-12-08', NULL, 1, '2025-12-08 22:02:25', 'Pintureria', NULL, NULL, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `rol` enum('admin','normal') DEFAULT 'normal',
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `clave`, `rol`, `estado`, `fecha_creacion`) VALUES
(1, 'admin', '$2y$10$KflZlFuf/PUi6y7GwNVGPOwse7Pqql6D0mURgzBEcP9ecDBBMSbba', 'admin', 1, '2025-11-24 02:20:33'),
(2, 'user', '$2y$10$L3yZ/qiKOnhQY2DsAUs8r./9ON8x2ohHuObAanVNWGB8PJHYnu5di', 'normal', 1, '2025-11-24 02:20:33');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaccion_relacionada_id` (`transaccion_relacionada_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD CONSTRAINT `transacciones_ibfk_1` FOREIGN KEY (`transaccion_relacionada_id`) REFERENCES `transacciones` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
