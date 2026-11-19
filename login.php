<?php
session_start();
require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if ($usuario === '' || $clave === '') {
        $error = 'Completa usuario y contraseña.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM usuarios_sistema WHERE usuario = :usuario LIMIT 1');
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($clave, $user['clave'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_rol'] = $user['rol'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingresar - Sistema de Asistencia CEA</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h1>LORETO CETAL NOCHE</h1>
            <p class="subtitle">Sistema de Gestión de Asistencia</p>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="admin" required autofocus>

                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" placeholder="••••••••" required>

                <button type="submit">Ingresar</button>
            </form>

            <p class="hint">Demo: usuario <strong>admin</strong> / clave <strong>admin123</strong></p>
        </div>
    </div>
</body>
</html>
