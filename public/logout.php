<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';

cerrar_sesion();
header('Location: login.php');
exit;