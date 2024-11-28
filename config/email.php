<?php
// Configuración de SMTP
$_ENV['SMTP_HOST'] = 'smtp.gmail.com';
$_ENV['SMTP_PORT'] = 587;
$_ENV['SMTP_USER'] = 'tu-email@gmail.com';
$_ENV['SMTP_PASS'] = 'tu-contraseña-de-aplicacion';
$_ENV['SMTP_FROM_EMAIL'] = 'notificaciones@tuempresa.com';
$_ENV['SMTP_FROM_NAME'] = 'Sistema de Presupuestos';

// Configuración de notificaciones
$_ENV['NOTIFICACIONES_ACTIVAS'] = true;
$_ENV['MAX_INTENTOS_EMAIL'] = 3;
$_ENV['TIEMPO_ENTRE_INTENTOS'] = 300; // 5 minutos 