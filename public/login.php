<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';

if (usuario_actual() !== null) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (intentar_login($usuario, $password)) {
        header('Location: index.php');
        exit;
    }

    $error = 'Usuario o contraseña incorrectos.';
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/../app/header.php';
?>
<div class="card login">
    <h2>Iniciar sesión</h2>

    <?php if ($error): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Usuario
            <input type="text" name="usuario" value="<?= h($_POST['usuario'] ?? '') ?>" required autofocus>
        </label>
        <label>Contraseña
            <input type="password" name="password" required>
        </label>
        <button type="submit">Entrar</button>
    </form>
</div>
<?php require __DIR__ . '/../app/footer.php'; ?>