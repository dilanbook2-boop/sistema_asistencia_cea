<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$mensaje = '';

// Crear nuevo usuario del sistema (quien puede iniciar sesión)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_usuario') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $rol = $_POST['rol'] ?? 'administrador';

    if ($nombre !== '' && $usuario !== '' && $clave !== '') {
        try {
            $hash = password_hash($clave, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO usuarios_sistema (nombre, usuario, clave, rol) VALUES (:nombre, :usuario, :clave, :rol)');
            $stmt->execute(['nombre' => $nombre, 'usuario' => $usuario, 'clave' => $hash, 'rol' => $rol]);
            $mensaje = 'Usuario del sistema creado correctamente.';
        } catch (PDOException $e) {
            $mensaje = 'Error: ya existe un usuario con ese nombre de usuario.';
        }
    } else {
        $mensaje = 'Completa todos los campos para crear el usuario.';
    }
}

// Eliminar un usuario del sistema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_usuario') {
    $id = $_POST['usuario_id'] ?? '';
    if ($id !== '' && (int)$id !== (int)$_SESSION['usuario_id']) {
        $stmt = $pdo->prepare('DELETE FROM usuarios_sistema WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $mensaje = 'Usuario del sistema eliminado.';
    } else {
        $mensaje = 'No puedes eliminar el usuario con el que iniciaste sesión.';
    }
}

// Dar de baja (retirar) a un docente o personal administrativo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'retirar_empleado') {
    $id = $_POST['empleado_id'] ?? '';
    if ($id !== '') {
        $stmt = $pdo->prepare("UPDATE empleados SET estado = 'retirado' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $mensaje = 'Personal dado de baja correctamente.';
    }
}

// Reactivar a un empleado retirado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reactivar_empleado') {
    $id = $_POST['empleado_id'] ?? '';
    if ($id !== '') {
        $stmt = $pdo->prepare("UPDATE empleados SET estado = 'activo' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $mensaje = 'Personal reactivado correctamente.';
    }
}

try {
    $usuariosSistema = $pdo->query('SELECT * FROM usuarios_sistema ORDER BY nombre ASC')->fetchAll();
    $activos = $pdo->query("SELECT id, nombre, cargo FROM empleados WHERE estado = 'activo' ORDER BY nombre ASC")->fetchAll();
    $retirados = $pdo->query("SELECT id, nombre, cargo FROM empleados WHERE estado = 'retirado' ORDER BY nombre ASC")->fetchAll();
} catch (PDOException $e) {
    $usuariosSistema = [];
    $activos = [];
    $retirados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - Sistema de Asistencia CEA</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<h2 class="page-title">Configuración</h2>

<?php if ($mensaje): ?>
    <div class="panel" style="background:#f5f3ff; padding:14px 18px;"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="panel">
    <h3>Logo de la institución</h3>
    <p class="muted">Para que el logo aparezca en el menú lateral, coloca un archivo llamado <strong>logo.png</strong> dentro de la carpeta <strong>assets/</strong> del proyecto (reemplaza el círculo gris automáticamente, sin tocar código).</p>
</div>

<div class="panel">
    <h3>Estado del biométrico</h3>
    <p style="font-size:14px; margin-bottom:10px;">
        IP del equipo: <strong>192.168.1.201</strong> &nbsp;|&nbsp;
        Estado: <span class="badge absent">Desconectado (demo)</span>
    </p>
    <button type="button" class="btn-primary" style="background:#5b4fe0; color:#fff;">Sincronizar ahora</button>
</div>

<div class="panel">
    <h3>Usuarios del sistema</h3>
    <p class="muted" style="margin-bottom:10px;">Personas que pueden iniciar sesión en este sistema (administradores/coordinadores). Esto es distinto al personal docente.</p>

    <table>
        <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Acción</th></tr></thead>
        <tbody>
            <?php foreach ($usuariosSistema as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['usuario']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($u['rol'])) ?></td>
                    <td>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                            <form method="POST" action="configuracion.php" onsubmit="return confirm('¿Eliminar este usuario del sistema?');" style="display:inline;">
                                <input type="hidden" name="accion" value="eliminar_usuario">
                                <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-link btn-danger">Eliminar</button>
                            </form>
                        <?php else: ?>
                            <span class="muted">Sesión actual</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <details style="margin-top:16px;">
        <summary>+ Agregar nuevo usuario del sistema</summary>
        <form method="POST" action="configuracion.php">
            <input type="hidden" name="accion" value="crear_usuario">
            <div class="form-grid">
                <div class="field">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="field">
                    <label>Usuario</label>
                    <input type="text" name="usuario" required>
                </div>
                <div class="field">
                    <label>Contraseña</label>
                    <input type="password" name="clave" required>
                </div>
                <div class="field">
                    <label>Rol</label>
                    <select name="rol">
                        <option value="administrador">Administrador</option>
                        <option value="coordinador">Coordinador</option>
                    </select>
                </div>
                <div class="field">
                    <button type="submit" class="btn-primary" style="background:#5b4fe0; color:#fff;">Crear usuario</button>
                </div>
            </div>
        </form>
    </details>
</div>

<div class="panel">
    <h3>Dar de baja personal</h3>
    <p class="muted" style="margin-bottom:10px;">Usa esto cuando un docente o personal administrativo se retire del CEA. Su historial de asistencia se conserva, solo deja de aparecer como activo.</p>
    <table>
        <thead><tr><th>Nombre</th><th>Cargo</th><th>Acción</th></tr></thead>
        <tbody>
            <?php if (empty($activos)): ?>
                <tr><td colspan="3">No hay personal activo.</td></tr>
            <?php else: ?>
                <?php foreach ($activos as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                        <td><?= htmlspecialchars($a['cargo']) ?></td>
                        <td>
                            <form method="POST" action="configuracion.php" onsubmit="return confirm('¿Dar de baja a este personal?');" style="display:inline;">
                                <input type="hidden" name="accion" value="retirar_empleado">
                                <input type="hidden" name="empleado_id" value="<?= $a['id'] ?>">
                                <button type="submit" class="btn-link btn-danger">Dar de baja</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($retirados)): ?>
        <h3 style="margin-top:20px;">Personal retirado</h3>
        <table>
            <thead><tr><th>Nombre</th><th>Cargo</th><th>Acción</th></tr></thead>
            <tbody>
                <?php foreach ($retirados as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nombre']) ?></td>
                        <td><?= htmlspecialchars($r['cargo']) ?></td>
                        <td>
                            <form method="POST" action="configuracion.php" style="display:inline;">
                                <input type="hidden" name="accion" value="reactivar_empleado">
                                <input type="hidden" name="empleado_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn-link btn-success">Reactivar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
