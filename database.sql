-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 01-06-2026 a las 03:10:18
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
-- Base de datos: `spamascotas`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `registrar_intento_fallido` (IN `p_usuario_id` VARCHAR(36))   BEGIN
    UPDATE usuarios 
    SET intentos_fallidos = intentos_fallidos + 1 
    WHERE id_usuario = p_usuario_id;

    -- Bloquear si llega a 5 intentos
    UPDATE usuarios 
    SET estado = 'Bloqueado', 
        bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
    WHERE id_usuario = p_usuario_id AND intentos_fallidos >= 5;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `validar_y_activar_usuario` (IN `p_token` VARCHAR(10), IN `p_ip` VARCHAR(45), IN `p_user_agent` TEXT)   BEGIN
    DECLARE v_email VARCHAR(100);
    DECLARE v_usuario_id VARCHAR(36);

    -- 1. Buscamos el token que no haya expirado (15 minutos)
    SELECT email INTO v_email 
    FROM usuarios_temporales 
    WHERE token_validacion = p_token 
    AND fecha_expiracion > NOW() 
    LIMIT 1;

    IF v_email IS NOT NULL THEN
        -- 2. Obtenemos el ID del usuario real
        SELECT id_usuario INTO v_usuario_id FROM usuarios WHERE email = v_email;

        -- 3. Activamos la cuenta
        UPDATE usuarios SET estado = 'Activo', esta_verificado = TRUE WHERE id_usuario = v_usuario_id;

        -- 4. Borramos el token temporal para que no se use de nuevo
        DELETE FROM usuarios_temporales WHERE email = v_email;

        -- 5. Registramos en Auditoría
        INSERT INTO audit_logs (usuario_id, accion, rol_ejecutor, ip_address, user_agent)
        VALUES (v_usuario_id, 'Activación de cuenta exitosa', 'Cliente', p_ip, p_user_agent);

        SELECT 'SUCCESS' AS resultado, 'Cuenta activada correctamente' AS mensaje;
    ELSE
        SELECT 'ERROR' AS resultado, 'El token es inválido o ha expirado (15 min)' AS mensaje;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id_log` int(11) NOT NULL,
  `usuario_id` varchar(36) DEFAULT NULL,
  `accion` varchar(255) DEFAULT NULL,
  `rol_ejecutor` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `audit_logs`
--

INSERT INTO `audit_logs` (`id_log`, `usuario_id`, `accion`, `rol_ejecutor`, `ip_address`, `user_agent`, `fecha_hora`) VALUES
(1, NULL, 'Creado personal: cami.aguijon@gmail.com', 'Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:11:24'),
(2, NULL, 'Cierre de sesión', 'Rol 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:12:37'),
(3, NULL, 'Login exitoso', 'Rol 3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:12:50'),
(4, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:25:03'),
(5, NULL, 'Intento de login fallido: cami.aguijon@gmail.com', 'Visitante', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:26:10'),
(6, NULL, 'Intento de login fallido: cami.aguijon@gmail.com', 'Visitante', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:26:41'),
(7, NULL, 'Intento de login fallido: cami.aguijon@gmail.com', 'Visitante', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:26:59'),
(8, NULL, 'Intento de login fallido: cami.aguijon@gmail.com', 'Visitante', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:27:04'),
(9, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:27:18'),
(10, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:27:28'),
(11, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:27:40'),
(12, NULL, 'Perfil de Groomer actualizado: Marceline Abadeer', 'Groomer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:28:25'),
(13, NULL, 'Perfil de Groomer actualizado: Marceline Abadeer', 'Groomer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:29:10'),
(14, NULL, 'Perfil de Groomer actualizado: Marceline Abadeer', 'Groomer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:29:24'),
(15, NULL, 'Perfil actualizado por staff', 'Groomer', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:35:02'),
(16, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:35:39'),
(17, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:35:46'),
(18, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:37:49'),
(19, NULL, 'Mascota agregada: Cookie', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:38:34'),
(20, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:39:35'),
(21, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:54:35'),
(22, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 03:54:46'),
(23, NULL, 'Intento de acceso no autorizado a Mascota ID: 3e1e4b71-4db6-11f1-95ad-d481d7b90b17', 'Seguridad', '::1', NULL, '2026-05-12 04:20:15'),
(24, NULL, 'Actualizó perfil de mascota: Cookie', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:21:51'),
(25, NULL, 'Cierre de sesión', 'Rol 3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:22:02'),
(26, NULL, 'Login exitoso', 'Rol 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:22:12'),
(27, NULL, 'Intento de acceso no autorizado a Mascota ID: 0fec431f-4dba-11f1-95ad-d481d7b90b17', 'Seguridad', '127.0.0.1', NULL, '2026-05-12 04:27:06'),
(28, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:31:04'),
(29, NULL, 'Actualizó perfil de mascota: Derpy', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:32:03'),
(30, NULL, 'Datos de contacto actualizados', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:34:32'),
(31, NULL, 'Datos de contacto actualizados', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:44:29'),
(32, NULL, 'Actualización de perfil personal', 'ID: 6607fc91-4da7-11f1-95ad-d481d7b90b17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:57:30'),
(33, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:58:03'),
(34, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 04:59:33'),
(35, NULL, 'Creado personal (Tarde): cam2cdm@gmail.com', 'Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:00:09'),
(36, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:00:35'),
(37, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:00:50'),
(38, NULL, 'Perfil actualizado por staff', 'Groomer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:01:10'),
(39, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:03:10'),
(40, NULL, 'Auto-registro iniciado', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:03:33'),
(41, 'ee5ede32-4dbf-11f1-95ad-d481d7b90b17', 'Activación de cuenta exitosa', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:03:50'),
(42, NULL, 'Actualizó perfil de mascota: Cookie', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:04:50'),
(43, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:05:06'),
(44, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 05:05:56'),
(45, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:20:09'),
(46, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:22:36'),
(47, NULL, 'Intento de login fallido: cam2cdm@gmail.com', 'Visitante', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:23:14'),
(48, NULL, 'Intento de login fallido: cam2cdm@gmail.com', 'Visitante', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:23:30'),
(49, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:24:29'),
(50, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:24:46'),
(51, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:24:58'),
(52, NULL, 'Agendó Grooming Completo (Corte y Baño) para mascota ID: 8236aee5-4dbb-11f1-95ad-d481d7b90b17', 'Cliente', '::1', NULL, '2026-05-25 20:35:59'),
(53, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:36:34'),
(54, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:37:06'),
(55, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:39:54'),
(56, NULL, 'Login exitoso', 'Rol 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:40:03'),
(57, NULL, 'Cita ID 56c37626-5879-11f1-8631-d481d7b90b17 confirmada.', 'Recepcion', '::1', NULL, '2026-05-25 20:44:54'),
(58, NULL, 'Cierre de sesión', 'Rol 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:45:08'),
(59, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:45:28'),
(60, NULL, 'Cierre de sesión', 'Rol 3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:48:49'),
(61, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:48:57'),
(62, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:49:57'),
(63, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:50:07'),
(64, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:51:04'),
(65, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 20:51:17'),
(66, NULL, 'Completó/Actualizó su perfil', 'ID: 6607fc91-4da7-11f1-95ad-d481d7b90b17', '::1', NULL, '2026-05-25 20:56:13'),
(67, NULL, 'Agendó Solo Baño y Secado para mascota ID: 8236aee5-4dbb-11f1-95ad-d481d7b90b17', 'Cliente', '::1', NULL, '2026-05-25 21:02:56'),
(68, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:03:42'),
(69, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:03:51'),
(70, NULL, 'Completó/Actualizó su perfil', 'ID: ee5ede32-4dbf-11f1-95ad-d481d7b90b17', '::1', NULL, '2026-05-25 21:04:17'),
(71, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:20:11'),
(72, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:23:19'),
(73, NULL, 'Actualizó perfil de mascota: Sussie', 'Cliente', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:27:36'),
(74, NULL, 'Actualizó perfil de mascota: Sussie', 'Cliente', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:27:49'),
(75, NULL, 'Agendó Solo Baño y Secado para Sussie', 'Cliente', '::1', NULL, '2026-05-25 21:28:33'),
(76, NULL, 'Cierre de sesión', 'Rol 4', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:29:08'),
(77, NULL, 'Login exitoso', 'Rol 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:29:22'),
(78, NULL, 'Cita ID 1aa0cc20-587d-11f1-8631-d481d7b90b17 confirmada.', 'Recepcion', '::1', NULL, '2026-05-25 21:29:32'),
(79, NULL, 'Cita ID aee1c26a-5880-11f1-8631-d481d7b90b17 confirmada.', 'Recepcion', '::1', NULL, '2026-05-25 21:29:33'),
(80, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:29:37'),
(81, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:29:48'),
(82, NULL, 'Servicio Cita 56c37626-5879-11f1-8631-d481d7b90b17 completado. Checklist validado. Stock descontado ID: 1', 'Groomer', '127.0.0.1', NULL, '2026-05-25 21:39:39'),
(83, NULL, 'Servicio Cita 56c37626-5879-11f1-8631-d481d7b90b17 completado. Checklist validado. Stock descontado ID: 1', 'Groomer', '::1', NULL, '2026-05-25 21:54:46'),
(84, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:55:18'),
(85, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 21:58:40'),
(86, NULL, 'Agendó Solo Baño y Secado para Cookie', 'Cliente', '::1', NULL, '2026-05-25 22:01:51'),
(87, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:02:11'),
(88, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:09:02'),
(89, NULL, 'Agendó Solo Baño y Secado para Sussie', 'Cliente', '::1', NULL, '2026-05-25 22:10:23'),
(90, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:10:30'),
(91, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:10:41'),
(92, NULL, 'Cita ID 86cb816b-5886-11f1-8631-d481d7b90b17 confirmada.', 'Recepcion', '::1', NULL, '2026-05-25 22:10:46'),
(93, NULL, 'Cita ID 55995cb8-5885-11f1-8631-d481d7b90b17 confirmada.', 'Recepcion', '::1', NULL, '2026-05-25 22:10:48'),
(94, NULL, 'Pago de Cita ID 86cb816b-5886-11f1-8631-d481d7b90b17 registrado vía Efectivo', 'Recepcion', '127.0.0.1', NULL, '2026-05-25 22:10:55'),
(95, NULL, 'Pago de Cita ID aee1c26a-5880-11f1-8631-d481d7b90b17 registrado vía Transferencia', 'Recepcion', '::1', NULL, '2026-05-25 22:11:02'),
(96, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:11:23'),
(97, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:11:34'),
(98, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:14:31'),
(99, NULL, 'Intento de login fallido: cmam2004@gmail.com', 'Visitante', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:14:42'),
(100, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 22:14:58'),
(101, NULL, 'Agendó Solo Baño y Secado para Cookie', 'Cliente', '::1', NULL, '2026-05-25 22:15:44'),
(102, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 15:05:36'),
(103, NULL, 'Servicio Cita 55995cb8-5885-11f1-8631-d481d7b90b17 completado. Checklist validado. Stock descontado ID: 1', 'Groomer', '::1', NULL, '2026-05-28 15:06:20'),
(104, NULL, 'Servicio Cita 1aa0cc20-587d-11f1-8631-d481d7b90b17 completado. Checklist validado. Stock descontado ID: 1', 'Groomer', '::1', NULL, '2026-05-28 15:06:39'),
(105, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:21:59'),
(106, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:22:09'),
(107, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:22:25'),
(108, NULL, 'Cambio de contraseña administrativa exitoso', 'Admin', '::1', NULL, '2026-05-31 19:22:41'),
(109, NULL, 'Cita ID 45d5396a-5887-11f1-8631-d481d7b90b17 confirmada.', 'Recepcion', '::1', NULL, '2026-05-31 19:22:59'),
(110, NULL, 'Pago de Cita ID 45d5396a-5887-11f1-8631-d481d7b90b17 registrado vía QR', 'Recepcion', '::1', NULL, '2026-05-31 19:23:11'),
(111, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:26:09'),
(112, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:26:20'),
(113, NULL, 'Intento de login fallido: madeleinne.2004@gmail.com', 'Visitante', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:26:53'),
(114, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:27:36'),
(115, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:38:37'),
(116, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 19:38:48'),
(117, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 21:48:28'),
(118, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 21:48:49'),
(119, NULL, 'Cierre de sesión', 'Rol 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 21:54:56'),
(120, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 21:55:05'),
(121, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 21:58:44'),
(122, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 21:58:53'),
(123, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 21:59:16'),
(124, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 21:59:38'),
(125, NULL, 'Agendó Spa Premium (Deslanado y Masaje) para Derpy', 'Cliente', '127.0.0.1', NULL, '2026-05-31 22:00:37'),
(126, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:01:08'),
(127, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:01:16'),
(128, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:02:39'),
(129, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:02:58'),
(130, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:27:45'),
(131, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:27:54'),
(132, NULL, 'Agendó Grooming Completo (Corte y Baño) (Extras: Corte de Uñas, Limpieza de Oídos, Corte Higiénico) para Derpy', 'Cliente', '::1', NULL, '2026-05-31 22:29:59'),
(133, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:30:45'),
(134, NULL, 'Login exitoso', 'Rol 3', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:30:59'),
(135, NULL, 'Servicio Cita aee1c26a-5880-11f1-8631-d481d7b90b17 completado. Checklist validado. Stock descontado ID: 1', 'Groomer', '::1', NULL, '2026-05-31 22:31:10'),
(136, NULL, 'Servicio Cita aee1c26a-5880-11f1-8631-d481d7b90b17 completado. Checklist validado. Stock descontado ID: 1', 'Groomer', '::1', NULL, '2026-05-31 22:36:58'),
(137, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:37:49'),
(138, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 22:38:04'),
(139, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 23:10:32'),
(140, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 23:10:41'),
(141, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 23:18:52'),
(142, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 23:19:03'),
(143, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 23:48:27'),
(144, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 23:57:53'),
(145, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-31 23:59:56'),
(146, NULL, 'Login exitoso', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 00:05:33'),
(147, NULL, 'Cierre de sesión', 'Rol 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 00:15:53'),
(148, NULL, 'Login exitoso', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 00:16:02'),
(149, NULL, 'Servicio Cita 86cb816b-5886-11f1-8631-d481d7b90b17 completado. Uso: 5 ml de insumo ID: e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 'Groomer', '::1', NULL, '2026-06-01 00:19:14'),
(150, NULL, 'Cierre de sesión', 'Rol 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 00:19:48'),
(151, NULL, 'Login exitoso', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 00:20:03'),
(152, NULL, 'Cierre de sesión', 'Rol 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 00:21:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_servicio`
--

CREATE TABLE `checklist_servicio` (
  `id_tarea` varchar(36) NOT NULL DEFAULT uuid(),
  `id_ficha` varchar(36) DEFAULT NULL,
  `tarea` varchar(100) DEFAULT NULL,
  `completado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id_cita` varchar(36) NOT NULL DEFAULT uuid(),
  `cliente_id` varchar(36) DEFAULT NULL,
  `mascota_id` varchar(36) DEFAULT NULL,
  `groomer_id` varchar(36) DEFAULT NULL,
  `servicio_id` varchar(36) DEFAULT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime DEFAULT NULL,
  `estado` enum('Pendiente','Confirmada','En Proceso','Finalizada','Cancelada') DEFAULT 'Pendiente',
  `calificacion_cliente` int(11) DEFAULT NULL CHECK (`calificacion_cliente` between 1 and 5),
  `comentario_cliente` text DEFAULT NULL,
  `precio_total_estimado` decimal(10,2) DEFAULT 0.00,
  `metodo_pago` enum('QR','Efectivo','Transferencia','Pendiente') DEFAULT 'Pendiente',
  `monto` decimal(10,2) DEFAULT 0.00,
  `duracion_minutos` int(11) DEFAULT 60,
  `servicio` varchar(100) DEFAULT 'Grooming Estándar',
  `pago_estado` varchar(20) DEFAULT 'No Pagado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id_cita`, `cliente_id`, `mascota_id`, `groomer_id`, `servicio_id`, `fecha_hora_inicio`, `fecha_hora_fin`, `estado`, `calificacion_cliente`, `comentario_cliente`, `precio_total_estimado`, `metodo_pago`, `monto`, `duracion_minutos`, `servicio`, `pago_estado`) VALUES
('1aa0cc20-587d-11f1-8631-d481d7b90b17', NULL, '8236aee5-4dbb-11f1-95ad-d481d7b90b17', '4388219c-4db0-11f1-95ad-d481d7b90b17', NULL, '2026-05-28 11:00:00', NULL, '', NULL, NULL, NULL, 'Pendiente', 90.00, 85, 'Solo Baño y Secado', 'No Pagado'),
('27ffc403-5d3c-11f1-8f36-d481d7b90b17', NULL, '8236aee5-4dbb-11f1-95ad-d481d7b90b17', '749ca058-4dbf-11f1-95ad-d481d7b90b17', NULL, '2026-06-01 10:00:00', NULL, 'Pendiente', NULL, NULL, NULL, 'Pendiente', 210.00, 150, 'Spa Premium (Deslanado y Masaje)', 'No Pagado'),
('41fb5657-5d40-11f1-8f36-d481d7b90b17', NULL, '8236aee5-4dbb-11f1-95ad-d481d7b90b17', '749ca058-4dbf-11f1-95ad-d481d7b90b17', NULL, '2026-06-02 09:30:00', NULL, 'Pendiente', NULL, '', NULL, 'Pendiente', 195.00, 155, 'Grooming Completo (Corte y Baño) (Extras: Corte de Uñas, Limpieza de Oídos, Corte Higiénico)', 'No Pagado'),
('45d5396a-5887-11f1-8631-d481d7b90b17', NULL, '17b8b0fd-4dc0-11f1-95ad-d481d7b90b17', '4388219c-4db0-11f1-95ad-d481d7b90b17', NULL, '2026-05-29 08:00:00', NULL, 'Confirmada', NULL, NULL, NULL, 'QR', 60.00, 55, 'Solo Baño y Secado', 'Pagado'),
('55995cb8-5885-11f1-8631-d481d7b90b17', NULL, '17b8b0fd-4dc0-11f1-95ad-d481d7b90b17', '4388219c-4db0-11f1-95ad-d481d7b90b17', NULL, '2026-05-28 10:00:00', NULL, '', NULL, NULL, NULL, 'Pendiente', 60.00, 55, 'Solo Baño y Secado', 'No Pagado'),
('56c37626-5879-11f1-8631-d481d7b90b17', NULL, '8236aee5-4dbb-11f1-95ad-d481d7b90b17', '4388219c-4db0-11f1-95ad-d481d7b90b17', NULL, '2026-05-28 08:00:00', NULL, '', NULL, NULL, NULL, 'Pendiente', 150.00, 105, 'Grooming Completo (Corte y Baño)', 'No Pagado'),
('86cb816b-5886-11f1-8631-d481d7b90b17', NULL, '7e363d80-5880-11f1-8631-d481d7b90b17', '4388219c-4db0-11f1-95ad-d481d7b90b17', NULL, '2026-05-28 16:00:00', NULL, 'Finalizada', NULL, NULL, NULL, 'Efectivo', 40.00, 35, 'Solo Baño y Secado', 'Pagado'),
('aee1c26a-5880-11f1-8631-d481d7b90b17', NULL, '7e363d80-5880-11f1-8631-d481d7b90b17', '4388219c-4db0-11f1-95ad-d481d7b90b17', NULL, '2026-05-28 14:00:00', NULL, 'Finalizada', NULL, NULL, NULL, 'Transferencia', 40.00, 35, 'Solo Baño y Secado', 'Pagado');

--
-- Disparadores `citas`
--
DELIMITER $$
CREATE TRIGGER `actualizar_rating_groomer` AFTER UPDATE ON `citas` FOR EACH ROW BEGIN
    IF NEW.calificacion_cliente IS NOT NULL THEN
        UPDATE groomers 
        SET rating_promedio = (SELECT AVG(calificacion_cliente) FROM citas WHERE groomer_id = NEW.groomer_id AND calificacion_cliente IS NOT NULL)
        WHERE id_groomer = NEW.groomer_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `antes_insertar_cita` BEFORE INSERT ON `citas` FOR EACH ROW BEGIN
    DECLARE v_duracion_servicio INT;
    DECLARE v_buffer_limpieza INT;
    DECLARE v_precio_base DECIMAL(10,2);
    
    -- Obtenemos los tiempos y el precio base del catálogo [cite: 48, 49]
    SELECT duracion_base_minutos, tiempo_limpieza_minutos, precio_base 
    INTO v_duracion_servicio, v_buffer_limpieza, v_precio_base
    FROM servicios WHERE id_servicio = NEW.servicio_id;
    
    -- El groomer está bloqueado: Duración técnica + Limpieza [cite: 37, 40]
    SET NEW.fecha_hora_fin = DATE_ADD(NEW.fecha_hora_inicio, INTERVAL (v_duracion_servicio + v_buffer_limpieza) MINUTE);
    
    -- Inicializamos el precio de la cita [cite: 49]
    SET NEW.precio_total_estimado = v_precio_base;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_alimento`
--

CREATE TABLE `detalles_alimento` (
  `id_producto` varchar(36) NOT NULL,
  `peso_kg` decimal(5,2) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `sabor` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_insumo`
--

CREATE TABLE `detalles_insumo` (
  `id_producto` varchar(36) NOT NULL,
  `unidad_medida` varchar(20) DEFAULT 'ml',
  `contenido_por_envase` int(11) NOT NULL,
  `ml_totales` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_insumo`
--

INSERT INTO `detalles_insumo` (`id_producto`, `unidad_medida`, `contenido_por_envase`, `ml_totales`) VALUES
('e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 'ml', 347, 1730.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_juguete`
--

CREATE TABLE `detalles_juguete` (
  `id_producto` varchar(36) NOT NULL,
  `material` varchar(50) DEFAULT NULL,
  `durabilidad_estimada` varchar(50) DEFAULT NULL,
  `edad_recomendada` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_ropa`
--

CREATE TABLE `detalles_ropa` (
  `id_producto` varchar(36) NOT NULL,
  `talla` enum('XS','S','M','L','XL') DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `tipo_tela` varchar(50) DEFAULT NULL,
  `temporada` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_ropa`
--

INSERT INTO `detalles_ropa` (`id_producto`, `talla`, `color`, `tipo_tela`, `temporada`) VALUES
('fb790215-5d4e-11f1-8f36-d481d7b90b17', 'S', 'Rojo', 'Polar', 'Invierno');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas_productos`
--

CREATE TABLE `detalle_ventas_productos` (
  `id_detalle` varchar(36) NOT NULL DEFAULT uuid(),
  `venta_id` varchar(36) DEFAULT NULL,
  `producto_id` varchar(36) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_ventas_productos`
--

INSERT INTO `detalle_ventas_productos` (`id_detalle`, `venta_id`, `producto_id`, `cantidad`, `subtotal`) VALUES
('2b55125d-5d47-11f1-8f36-d481d7b90b17', '2b54e624-5d47-11f1-8f36-d481d7b90b17', 'ec9027ed-5d3f-11f1-8f36-d481d7b90b17', 1, 85.00),
('2b5527e4-5d47-11f1-8f36-d481d7b90b17', '2b54e624-5d47-11f1-8f36-d481d7b90b17', 'e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 1, 85.00),
('5755c29a-5d4a-11f1-8f36-d481d7b90b17', '5755993d-5d4a-11f1-8f36-d481d7b90b17', 'ec9027ed-5d3f-11f1-8f36-d481d7b90b17', 1, 85.00),
('94abb38c-5d46-11f1-8f36-d481d7b90b17', '94ab83ba-5d46-11f1-8f36-d481d7b90b17', 'ec9027ed-5d3f-11f1-8f36-d481d7b90b17', 4, 340.00),
('a34d6c4a-5d4a-11f1-8f36-d481d7b90b17', 'a34d35cb-5d4a-11f1-8f36-d481d7b90b17', 'e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 1, 85.00),
('b025e137-5d49-11f1-8f36-d481d7b90b17', 'b025b7f9-5d49-11f1-8f36-d481d7b90b17', 'ec9027ed-5d3f-11f1-8f36-d481d7b90b17', 1, 85.00),
('b025fa33-5d49-11f1-8f36-d481d7b90b17', 'b025b7f9-5d49-11f1-8f36-d481d7b90b17', 'e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 1, 85.00),
('b600d027-5d4f-11f1-8f36-d481d7b90b17', 'b6009be1-5d4f-11f1-8f36-d481d7b90b17', 'fb790215-5d4e-11f1-8f36-d481d7b90b17', 1, 50.00),
('b600e529-5d4f-11f1-8f36-d481d7b90b17', 'b6009be1-5d4f-11f1-8f36-d481d7b90b17', 'e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 1, 85.00),
('d28d1d5a-5d49-11f1-8f36-d481d7b90b17', 'd28ce8f4-5d49-11f1-8f36-d481d7b90b17', 'e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 1, 85.00),
('ff84a1db-5d45-11f1-8f36-d481d7b90b17', 'ff844786-5d45-11f1-8f36-d481d7b90b17', 'e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 1, 85.00),
('ff84d794-5d45-11f1-8f36-d481d7b90b17', 'ff844786-5d45-11f1-8f36-d481d7b90b17', 'ec9027ed-5d3f-11f1-8f36-d481d7b90b17', 1, 85.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencias_fotos`
--

CREATE TABLE `evidencias_fotos` (
  `id_foto` varchar(36) NOT NULL DEFAULT uuid(),
  `id_ficha` varchar(36) DEFAULT NULL,
  `url_foto` text NOT NULL,
  `tipo` enum('Antes','Despues') NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencias_grooming`
--

CREATE TABLE `evidencias_grooming` (
  `id_evidencia` varchar(36) NOT NULL DEFAULT uuid(),
  `id_ficha` varchar(36) DEFAULT NULL,
  `url_foto` text NOT NULL,
  `momento` enum('Antes','Despues','Detalle') DEFAULT 'Antes',
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fichas_grooming`
--

CREATE TABLE `fichas_grooming` (
  `id_ficha` varchar(36) NOT NULL DEFAULT uuid(),
  `cita_id` varchar(36) DEFAULT NULL,
  `estado_ingreso` text DEFAULT NULL,
  `comentarios_post_servicio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `groomers`
--

CREATE TABLE `groomers` (
  `id_groomer` varchar(36) NOT NULL DEFAULT uuid(),
  `usuario_id` varchar(36) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL,
  `rating_promedio` decimal(3,2) DEFAULT 0.00,
  `disponible` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `groomers`
--

INSERT INTO `groomers` (`id_groomer`, `usuario_id`, `especialidad`, `rating_promedio`, `disponible`) VALUES
('4388219c-4db0-11f1-95ad-d481d7b90b17', '4387e363-4db0-11f1-95ad-d481d7b90b17', 'Gatos y Perros', 0.00, 1),
('749ca058-4dbf-11f1-95ad-d481d7b90b17', '749c8a99-4dbf-11f1-95ad-d481d7b90b17', 'Spa', 0.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumos_cita`
--

CREATE TABLE `insumos_cita` (
  `id_insumo_cita` varchar(36) NOT NULL DEFAULT uuid(),
  `cita_id` varchar(36) DEFAULT NULL,
  `producto_id` varchar(36) DEFAULT NULL,
  `cantidad_usada` decimal(10,2) DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `insumos_cita`
--
DELIMITER $$
CREATE TRIGGER `despues_usar_insumo` AFTER INSERT ON `insumos_cita` FOR EACH ROW BEGIN
    DECLARE v_precio_venta_insumo DECIMAL(10,2);
    DECLARE v_nombre_prod VARCHAR(100);
    DECLARE v_admin_id VARCHAR(36);

    -- Obtenemos datos del producto usado [cite: 11, 53]
    SELECT nombre, precio_venta INTO v_nombre_prod, v_precio_venta_insumo 
    FROM productos WHERE id_producto = NEW.producto_id;

    -- 1. Actualizamos el precio total de la cita (Precio Base + Insumos) [cite: 12, 50]
    UPDATE citas 
    SET precio_total_estimado = precio_total_estimado + (v_precio_venta_insumo * NEW.cantidad_usada)
    WHERE id_cita = NEW.cita_id;

    -- 2. Descontamos del stock general [cite: 11, 55]
    UPDATE productos SET stock_actual = stock_actual - NEW.cantidad_usada 
    WHERE id_producto = NEW.producto_id;

    -- 3. Notificación automática por stock bajo [cite: 54]
    IF (SELECT stock_actual FROM productos WHERE id_producto = NEW.producto_id) <= (SELECT stock_minimo FROM productos WHERE id_producto = NEW.producto_id) THEN
        INSERT INTO notificaciones (usuario_id, mensaje, tipo_receptor)
        SELECT id_usuario, CONCAT('Alerta: Stock crítico de ', v_nombre_prod), 'Admin'
        FROM usuarios WHERE rol_id = (SELECT id_rol FROM roles WHERE nombre_rol = 'Administrador');
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `id_insumo` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `stock_minimo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`id_insumo`, `nombre`, `cantidad`, `stock_minimo`) VALUES
(1, 'Shampoo Estándar Cosmético', 44, 10),
(2, 'Shampoo Medicado (Clorhexidina)', 5, 10),
(3, 'Shampoo Hipoalergénico', 20, 5),
(4, 'Tratamiento Deslanado', 15, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lotes`
--

CREATE TABLE `lotes` (
  `id_lote` varchar(36) NOT NULL DEFAULT uuid(),
  `producto_id` varchar(36) DEFAULT NULL,
  `codigo_lote` varchar(50) NOT NULL,
  `fecha_entrada` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_recibida` decimal(10,2) DEFAULT NULL,
  `cantidad_actual` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lotes_productos`
--

CREATE TABLE `lotes_productos` (
  `id_lote` varchar(36) NOT NULL DEFAULT uuid(),
  `producto_id` varchar(36) DEFAULT NULL,
  `codigo_lote` varchar(50) NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `cantidad_inicial` decimal(10,2) DEFAULT NULL,
  `cantidad_actual` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `id_mascota` varchar(36) NOT NULL DEFAULT uuid(),
  `propietario_id` varchar(36) DEFAULT NULL,
  `nombre` varchar(50) NOT NULL,
  `especie` varchar(30) DEFAULT NULL,
  `raza` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `peso_actual` decimal(5,2) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `vacunas_al_dia` tinyint(1) DEFAULT 1,
  `temperamento` enum('Tranquilo','Nervioso','Agresivo','Miedoso') DEFAULT 'Tranquilo',
  `foto_url` varchar(255) DEFAULT 'default_pet.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mascotas`
--

INSERT INTO `mascotas` (`id_mascota`, `propietario_id`, `nombre`, `especie`, `raza`, `fecha_nacimiento`, `peso_actual`, `alergias`, `vacunas_al_dia`, `temperamento`, `foto_url`) VALUES
('0fec431f-4dba-11f1-95ad-d481d7b90b17', '4387e363-4db0-11f1-95ad-d481d7b90b17', 'Cookie', 'Perro', 'Pástor Alemán', '0000-00-00', 25.00, 'Ninguna', 1, 'Tranquilo', 'pet_1778559693.jpeg'),
('17b8b0fd-4dc0-11f1-95ad-d481d7b90b17', 'ee5ede32-4dbf-11f1-95ad-d481d7b90b17', 'Cookie', 'Ave', 'Pollito', '0000-00-00', 0.60, 'Ninguna', 1, 'Agresivo', 'pet_1778562283.png'),
('7e363d80-5880-11f1-8631-d481d7b90b17', '6607fc91-4da7-11f1-95ad-d481d7b90b17', 'Sussie', 'Ave', 'Urraca', '2025-05-05', 0.20, 'Ninguna', 1, 'Tranquilo', 'pet_1779744432.jpeg'),
('8236aee5-4dbb-11f1-95ad-d481d7b90b17', '6607fc91-4da7-11f1-95ad-d481d7b90b17', 'Derpy', 'Gato', 'Tigre Azul', '0000-00-00', 25.00, 'Ninguna', 1, 'Tranquilo', 'pet_1778560314.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id_notificacion` varchar(36) NOT NULL DEFAULT uuid(),
  `usuario_id` varchar(36) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('Cita','Stock','Recordatorio','Sistema') DEFAULT 'Cita',
  `leido` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL,
  `nombre_permiso` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id_permiso`, `nombre_permiso`, `descripcion`) VALUES
(1, 'CREAR_PERSONAL', 'Capacidad exclusiva de crear cuentas de Recepción y Groomers'),
(2, 'AUTO_REGISTRO', 'Permitir que el usuario cree su propia cuenta'),
(3, 'GESTION_CITAS', 'Crear, reprogramar y cancelar reservas[cite: 3]'),
(4, 'GESTION_MASCOTAS', 'Administrar fichas y restricciones médicas[cite: 3]'),
(5, 'LLENAR_FICHA_TECNICA', 'Checklist obligatorio y carga de fotos antes/después[cite: 2, 3]'),
(6, 'VER_REPORTES', 'Acceso a KPIs y analítica financiera[cite: 3]');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` varchar(36) NOT NULL DEFAULT uuid(),
  `nombre` varchar(100) NOT NULL,
  `tipo_producto` enum('Alimento','Juguete','Ropa','Insumo_Spa') NOT NULL,
  `stock_actual` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  `es_insumo_grooming` tinyint(1) DEFAULT 0,
  `foto_url` varchar(255) DEFAULT 'default_product.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre`, `tipo_producto`, `stock_actual`, `stock_minimo`, `precio_venta`, `es_insumo_grooming`, `foto_url`) VALUES
('e6b7ac18-5d45-11f1-8f36-d481d7b90b17', 'Shampoo Pelos Claros', 'Insumo_Spa', 5, 5, 85.00, 2, 'prod_1780269018.png'),
('ec9027ed-5d3f-11f1-8f36-d481d7b90b17', 'Shampoo Pelos Claros', 'Insumo_Spa', 3, 5, 85.00, 2, 'prod_1780266455.png'),
('fb790215-5d4e-11f1-8f36-d481d7b90b17', 'Vestido de Navidad', 'Ropa', 5, 5, 50.00, 0, 'prod_1780272918.jpeg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Administrador'),
(2, 'Recepción'),
(3, 'Groomer'),
(4, 'Cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `rol_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol_permisos`
--

INSERT INTO `rol_permisos` (`rol_id`, `permiso_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(2, 3),
(2, 4),
(3, 3),
(3, 5),
(4, 2),
(4, 3),
(4, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id_servicio` varchar(36) NOT NULL DEFAULT uuid(),
  `nombre_servicio` varchar(100) NOT NULL,
  `duracion_base_minutos` int(11) NOT NULL,
  `precio_base` decimal(10,2) NOT NULL,
  `tiempo_limpieza_minutos` int(11) DEFAULT 15
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id_sesion` varchar(36) NOT NULL DEFAULT uuid(),
  `usuario_id` varchar(36) DEFAULT NULL,
  `jwt_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_expiracion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` varchar(36) NOT NULL DEFAULT uuid(),
  `rol_id` int(11) DEFAULT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `ci` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `esta_verificado` tinyint(1) DEFAULT 0,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Activo','Inactivo','Bloqueado') DEFAULT 'Inactivo',
  `google_id` varchar(255) DEFAULT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `intentos_fallidos` int(11) DEFAULT 0,
  `bloqueado_hasta` timestamp NULL DEFAULT NULL,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `cambio_password_pendiente` tinyint(1) DEFAULT 1,
  `turno` varchar(20) DEFAULT 'Mañana',
  `foto_perfil` varchar(255) DEFAULT 'default_user.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `rol_id`, `nombre_completo`, `ci`, `email`, `telefono`, `direccion`, `password_hash`, `esta_verificado`, `fecha_registro`, `estado`, `google_id`, `two_factor_secret`, `two_factor_enabled`, `intentos_fallidos`, `bloqueado_hasta`, `ultimo_acceso`, `cambio_password_pendiente`, `turno`, `foto_perfil`) VALUES
('4387e363-4db0-11f1-95ad-d481d7b90b17', 3, 'Marceline Abadeer', NULL, 'cami.aguijon@gmail.com', '2228764', NULL, '$2y$12$JyglqfPQFctPgMYSMcsRuerECgbJiqMwhnwfgrRD5xWEXAODON9zi', 1, '2026-05-12 03:11:24', 'Activo', NULL, NULL, 0, 0, NULL, NULL, 1, 'Mañana', 'default_user.png'),
('6607fc91-4da7-11f1-95ad-d481d7b90b17', 4, 'Camila Madeleine', '13762475', 'madeleinne.2004@gmail.com', '65572349', 'Calle Casimiro Corrales N1158', '$2y$12$VF2.DNTg3Eeg/jKdh4DVJOnWbFbJKP98peL6cAs6KHj1GUZEpeWWe', 1, '2026-05-12 02:07:57', 'Activo', NULL, NULL, 0, 0, NULL, NULL, 1, 'Mañana', 'perfil_6607fc91-4da7-11f1-95ad-d481d7b90b17_1779742573.png'),
('749c8a99-4dbf-11f1-95ad-d481d7b90b17', 3, 'Bonnibel Bubblegum', NULL, 'cam2cdm@gmail.com', NULL, NULL, '$2y$12$64nZtify7EFaJOOu16CNZuBKN2o42r18EP/UddF/zkbuezZ3Pdl5y', 1, '2026-05-12 05:00:09', 'Activo', NULL, NULL, 0, 0, NULL, NULL, 1, 'Tarde', 'default_user.png'),
('a62eae97-4da9-11f1-95ad-d481d7b90b17', 1, 'Administrador Central', NULL, 'caguilarm@fcpn.edu.bo', NULL, NULL, '$2y$12$tZIPTG8y00jnisLdzWZpN.BgnC38YpAidU8O.uzu0k.4sKmOGuJIW', 1, '2026-05-12 02:24:03', 'Activo', NULL, NULL, 0, 0, NULL, NULL, 1, 'Mañana', 'default_user.png'),
('ee5ede32-4dbf-11f1-95ad-d481d7b90b17', 4, 'Camila Madeleine', '112', 'cmam2004@gmail.com', '123', 'Calle casimiro corrales', '$2y$12$lWx84hgt/zLjv5I96UOVU.qx3CCbZtSdngxlC3bHxNqv3is7d/l7W', 1, '2026-05-12 05:03:33', 'Activo', NULL, NULL, 0, 0, NULL, NULL, 1, 'Mañana', 'default_user.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_temporales`
--

CREATE TABLE `usuarios_temporales` (
  `id_temp` varchar(36) NOT NULL DEFAULT uuid(),
  `email` varchar(100) NOT NULL,
  `token_validacion` varchar(10) DEFAULT NULL,
  `fecha_expiracion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `datos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_json`)),
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` varchar(36) NOT NULL DEFAULT uuid(),
  `cliente_id` varchar(36) DEFAULT NULL,
  `cita_id` varchar(36) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Transferencia','QR','Tarjeta') DEFAULT NULL,
  `fecha_venta` timestamp NOT NULL DEFAULT current_timestamp(),
  `codigo_recojo` varchar(15) DEFAULT NULL,
  `estado_pedido` enum('Pendiente','Pagado','Entregado','Cancelado') DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `cliente_id`, `cita_id`, `total`, `metodo_pago`, `fecha_venta`, `codigo_recojo`, `estado_pedido`) VALUES
('2b54e624-5d47-11f1-8f36-d481d7b90b17', 'ee5ede32-4dbf-11f1-95ad-d481d7b90b17', NULL, 170.00, 'Transferencia', '2026-05-31 23:19:23', NULL, 'Pendiente'),
('5755993d-5d4a-11f1-8f36-d481d7b90b17', 'ee5ede32-4dbf-11f1-95ad-d481d7b90b17', NULL, 85.00, 'QR', '2026-05-31 23:42:05', 'PED-8505C', 'Cancelado'),
('94ab83ba-5d46-11f1-8f36-d481d7b90b17', '6607fc91-4da7-11f1-95ad-d481d7b90b17', NULL, 340.00, 'Transferencia', '2026-05-31 23:15:10', NULL, 'Pendiente'),
('a34d35cb-5d4a-11f1-8f36-d481d7b90b17', 'ee5ede32-4dbf-11f1-95ad-d481d7b90b17', NULL, 85.00, 'QR', '2026-05-31 23:44:12', 'PED-E1AD1', 'Cancelado'),
('b025b7f9-5d49-11f1-8f36-d481d7b90b17', 'ee5ede32-4dbf-11f1-95ad-d481d7b90b17', NULL, 170.00, 'QR', '2026-05-31 23:37:24', 'PED-A0894', 'Cancelado'),
('b6009be1-5d4f-11f1-8f36-d481d7b90b17', '6607fc91-4da7-11f1-95ad-d481d7b90b17', NULL, 135.00, 'QR', '2026-06-01 00:20:31', 'PED-0521B', 'Cancelado'),
('d28ce8f4-5d49-11f1-8f36-d481d7b90b17', 'ee5ede32-4dbf-11f1-95ad-d481d7b90b17', NULL, 85.00, 'QR', '2026-05-31 23:38:22', 'PED-DA8A1', 'Cancelado'),
('ff844786-5d45-11f1-8f36-d481d7b90b17', '6607fc91-4da7-11f1-95ad-d481d7b90b17', NULL, 170.00, 'Transferencia', '2026-05-31 23:11:00', NULL, 'Pendiente');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `checklist_servicio`
--
ALTER TABLE `checklist_servicio`
  ADD PRIMARY KEY (`id_tarea`),
  ADD KEY `id_ficha` (`id_ficha`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `mascota_id` (`mascota_id`),
  ADD KEY `groomer_id` (`groomer_id`),
  ADD KEY `servicio_id` (`servicio_id`);

--
-- Indices de la tabla `detalles_alimento`
--
ALTER TABLE `detalles_alimento`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `detalles_insumo`
--
ALTER TABLE `detalles_insumo`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `detalles_juguete`
--
ALTER TABLE `detalles_juguete`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `detalles_ropa`
--
ALTER TABLE `detalles_ropa`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `detalle_ventas_productos`
--
ALTER TABLE `detalle_ventas_productos`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `evidencias_fotos`
--
ALTER TABLE `evidencias_fotos`
  ADD PRIMARY KEY (`id_foto`),
  ADD KEY `id_ficha` (`id_ficha`);

--
-- Indices de la tabla `evidencias_grooming`
--
ALTER TABLE `evidencias_grooming`
  ADD PRIMARY KEY (`id_evidencia`),
  ADD KEY `id_ficha` (`id_ficha`);

--
-- Indices de la tabla `fichas_grooming`
--
ALTER TABLE `fichas_grooming`
  ADD PRIMARY KEY (`id_ficha`),
  ADD KEY `cita_id` (`cita_id`);

--
-- Indices de la tabla `groomers`
--
ALTER TABLE `groomers`
  ADD PRIMARY KEY (`id_groomer`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `insumos_cita`
--
ALTER TABLE `insumos_cita`
  ADD PRIMARY KEY (`id_insumo_cita`),
  ADD KEY `cita_id` (`cita_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`id_insumo`);

--
-- Indices de la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD PRIMARY KEY (`id_lote`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `lotes_productos`
--
ALTER TABLE `lotes_productos`
  ADD PRIMARY KEY (`id_lote`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`id_mascota`),
  ADD KEY `propietario_id` (`propietario_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD UNIQUE KEY `nombre_permiso` (`nombre_permiso`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`rol_id`,`permiso_id`),
  ADD KEY `permiso_id` (`permiso_id`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id_servicio`);

--
-- Indices de la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id_sesion`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `ci` (`ci`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Indices de la tabla `usuarios_temporales`
--
ALTER TABLE `usuarios_temporales`
  ADD PRIMARY KEY (`id_temp`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `cita_id` (`cita_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `id_insumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `checklist_servicio`
--
ALTER TABLE `checklist_servicio`
  ADD CONSTRAINT `checklist_servicio_ibfk_1` FOREIGN KEY (`id_ficha`) REFERENCES `fichas_grooming` (`id_ficha`);

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id_mascota`),
  ADD CONSTRAINT `citas_ibfk_3` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id_groomer`),
  ADD CONSTRAINT `citas_ibfk_4` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id_servicio`);

--
-- Filtros para la tabla `detalles_alimento`
--
ALTER TABLE `detalles_alimento`
  ADD CONSTRAINT `detalles_alimento_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalles_insumo`
--
ALTER TABLE `detalles_insumo`
  ADD CONSTRAINT `detalles_insumo_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalles_juguete`
--
ALTER TABLE `detalles_juguete`
  ADD CONSTRAINT `detalles_juguete_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalles_ropa`
--
ALTER TABLE `detalles_ropa`
  ADD CONSTRAINT `detalles_ropa_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_ventas_productos`
--
ALTER TABLE `detalle_ventas_productos`
  ADD CONSTRAINT `detalle_ventas_productos_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id_venta`),
  ADD CONSTRAINT `detalle_ventas_productos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `evidencias_fotos`
--
ALTER TABLE `evidencias_fotos`
  ADD CONSTRAINT `evidencias_fotos_ibfk_1` FOREIGN KEY (`id_ficha`) REFERENCES `fichas_grooming` (`id_ficha`);

--
-- Filtros para la tabla `evidencias_grooming`
--
ALTER TABLE `evidencias_grooming`
  ADD CONSTRAINT `evidencias_grooming_ibfk_1` FOREIGN KEY (`id_ficha`) REFERENCES `fichas_grooming` (`id_ficha`);

--
-- Filtros para la tabla `fichas_grooming`
--
ALTER TABLE `fichas_grooming`
  ADD CONSTRAINT `fichas_grooming_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id_cita`);

--
-- Filtros para la tabla `groomers`
--
ALTER TABLE `groomers`
  ADD CONSTRAINT `groomers_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `insumos_cita`
--
ALTER TABLE `insumos_cita`
  ADD CONSTRAINT `insumos_cita_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id_cita`),
  ADD CONSTRAINT `insumos_cita_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `lotes`
--
ALTER TABLE `lotes`
  ADD CONSTRAINT `lotes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `lotes_productos`
--
ALTER TABLE `lotes_productos`
  ADD CONSTRAINT `lotes_productos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD CONSTRAINT `mascotas_ibfk_1` FOREIGN KEY (`propietario_id`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `rol_permisos_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE,
  ADD CONSTRAINT `rol_permisos_ibfk_2` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id_rol`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id_cita`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
