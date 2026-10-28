<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/importar.php';

requerir_rol('SOLICITANTE');
generar_plantilla();