<?php
session_start();
require_once __DIR__ . '/config/db.php';

const MAX_INTENTOS = 5;      // intentos fallidos permitidos
const BLOQUEO_MINUTOS = 10;  // minutos de bloqueo al llegar al límite

$error = '';
$bloqueado = false;
$segundosRestantes = 0;

/** Segundos que faltan para que termine el bloqueo de un usuario (0 = no bloqueado). */
function segundos_bloqueo(PDO $pdo, string $usuario): int {
    $min = (int)BLOQUEO_MINUTOS; // constante entera, segura para interpolar
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS n,
               EXTRACT(EPOCH FROM (MAX(creado_en) + INTERVAL '$min minutes' - NOW())) AS restante
        FROM intentos_login
        WHERE usuario = :u AND creado_en > NOW() - INTERVAL '$min minutes'
    ");
    $stmt->execute(['u' => $usuario]);
    $fila = $stmt->fetch();
    if ((int)$fila['n'] >= MAX_INTENTOS && $fila['restante'] > 0) {
        return (int)ceil($fila['restante']);
    }
    return 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $claveBloqueo = mb_strtolower($usuario); // el bloqueo no distingue mayúsculas

    if ($usuario === '' || $clave === '') {
        $error = 'Completa usuario y contraseña.';
    } else {
        // Limpieza de registros viejos
        $pdo->exec("DELETE FROM intentos_login WHERE creado_en < NOW() - INTERVAL '1 day'");

        $segundosRestantes = segundos_bloqueo($pdo, $claveBloqueo);

        if ($segundosRestantes > 0) {
            // Cuenta bloqueada: ni siquiera se verifica la contraseña
            $bloqueado = true;
        } else {
            $stmt = $pdo->prepare('
                SELECT u.*, e.estado AS empleado_estado
                FROM usuarios_sistema u
                LEFT JOIN empleados e ON e.id = u.empleado_id
                WHERE LOWER(u.usuario) = :usuario LIMIT 1
            ');
            $stmt->execute(['usuario' => $claveBloqueo]);
            $user = $stmt->fetch();

            if ($user && password_verify($clave, $user['clave'])) {
                if ($user['rol'] === 'docente' && $user['empleado_estado'] !== 'activo') {
                    $error = 'Tu cuenta está inactiva. Contacta al administrador.';
                } else {
                    $pdo->prepare('DELETE FROM intentos_login WHERE usuario = :u')->execute(['u' => $claveBloqueo]);
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['usuario_nombre'] = $user['nombre'];
                    $_SESSION['usuario_rol'] = $user['rol'];
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $pdo->prepare('INSERT INTO intentos_login (usuario, ip) VALUES (:u, :ip)')
                    ->execute(['u' => $claveBloqueo, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

                $segundosRestantes = segundos_bloqueo($pdo, $claveBloqueo);
                if ($segundosRestantes > 0) {
                    $bloqueado = true;
                } else {
                    $minB = (int)BLOQUEO_MINUTOS;
                    $stmtN = $pdo->prepare("SELECT COUNT(*) AS n FROM intentos_login WHERE usuario = :u AND creado_en > NOW() - INTERVAL '$minB minutes'");
                    $stmtN->execute(['u' => $claveBloqueo]);
                    $quedan = MAX_INTENTOS - (int)$stmtN->fetch()['n'];
                    $error = 'Usuario o contraseña incorrectos. Te quedan ' . max($quedan, 0) . ' intento(s) antes del bloqueo.';
                }
            }
        }
    }
}

if ($bloqueado) {
    $mins = (int)ceil($segundosRestantes / 60);
    $error = 'Demasiados intentos fallidos. Cuenta bloqueada temporalmente; vuelve a intentar en ' . $mins . ' minuto(s).';
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
                <input type="text" id="usuario" name="usuario" placeholder="usuario" required autofocus <?= $bloqueado ? 'disabled' : '' ?>>

                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" placeholder="••••••••" required <?= $bloqueado ? 'disabled' : '' ?>>

                <button type="submit" <?= $bloqueado ? 'disabled style="opacity:.5;cursor:not-allowed;"' : '' ?>>Ingresar</button>
            </form>

            <p class="hint">Demo: usuario <strong>admin</strong> / clave <strong>admin123</strong></p>
        </div>
    </div>
</body>
</html>
