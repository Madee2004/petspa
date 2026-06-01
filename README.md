# 🐾 Sistema de Gestión Integral para Pet Spa

Este proyecto es una aplicación web completa para la gestión de un Spa de Mascotas. Incluye un módulo de reservas de citas (Grooming) con cálculo dinámico de precios y tiempos, control de inventario milimétrico, e-commerce con carrito de compras, y notificaciones automatizadas vía Telegram y Correo Electrónico.

---

## 🚀 Guía de Instalación y Configuración

Para probar el sistema localmente, sigue estos pasos para configurar la base de datos y conectar tus propias credenciales de correo y Telegram.

### Paso 1: Preparar la Base de Datos
1. Importa el archivo `.sql` proporcionado en tu gestor de base de datos (ej. phpMyAdmin).
2. **Limpiar datos de prueba:** Para evitar conflictos con los usuarios de prueba originales, ejecuta el siguiente código SQL en phpMyAdmin. Esto limpiará las cuentas antiguas de forma segura:

sql
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM usuarios;
DELETE FROM groomers;
-- Opcional: Limpiar historial si deseas una prueba desde cero
TRUNCATE TABLE citas;
TRUNCATE TABLE ventas;
TRUNCATE TABLE detalle_ventas_productos;
SET FOREIGN_KEY_CHECKS = 1;

### Paso 2: Crear tu Usuario Administrador y Configurar Correos
Para que los correos de notificación del sistema te lleguen a ti (o al evaluador) en lugar del desarrollador original, debes actualizar los siguientes archivos:

ca.php (Creación del Admin): * Abre este archivo y modifica la variable del correo para poner el tuyo.

Una vez modificado, abre tu navegador y ejecuta la ruta http://localhost/tu_carpeta/ca.php. Esto creará tu usuario Administrador de forma automática. ¡Ya puedes iniciar sesión!

editar_perfil_groomer.php: * Revisa este archivo y cambia el correo quemado por el tuyo para las pruebas de ese módulo.

mailer.php: * Configura aquí las credenciales SMTP o el correo remitente para que el sistema pueda enviar notificaciones válidas.

💡 Sugerencia para Pruebas: Para crear y probar roles de Cliente o Groomer, te recomendamos usar servicios de correos temporales (como TempMail) para verificar la recepción de correos sin saturar tu bandeja personal.

### Paso 3: Configurar el Bot de Telegram (Alertas de E-commerce)
El sistema envía alertas en tiempo real sobre nuevos pedidos y cancelaciones. Para que lleguen a tu celular, necesitas conectar tu propio Bot de Telegram.

Busca en Telegram a @BotFather para crear un bot y obtener tu Token.

Busca a @userinfobot para obtener tu Chat ID numérico.

Actualiza estas credenciales en los siguientes archivos:

catalogo.php: Alrededor de la línea donde se procesa la compra (Busca $telegram_token y $chat_id).

dashboard.php: En la sección de "Cancelar Pedido" (Busca $telegram_token y $chat_id).

### Paso 4: ¡Todo Listo!
Ya puedes acceder al sistema desde el index.php principal, iniciar sesión con el administrador que creaste en el Paso 2 y comenzar a gestionar el catálogo, los inventarios y las citas.
