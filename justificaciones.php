<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'justificar') {
    $asistenciaId = $_POST['asistencia_id'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');

    if ($asistenciaId !== '' && $motivo !== '') {
        try {
            $pdo->beginTransaction();
            $ins = $pdo->prepare('INSERT INTO justificaciones (asistencia_id, motivo) VALUES (:aid, :motivo)');
            $ins->execute(['aid' => $asistenciaId, 'motivo' => $motivo]);

            $upd = $pdo->prepare("UPDATE asistencias SET estado = 'justificada' WHERE id = :aid");
            $upd->execute(['aid' => $asistenciaId]);

            $pdo->commit();
            $mensaje = 'Justificación registrada.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensaje = 'Error: ' . $e->getMessage();
        }
    }
}

try {
    $pendientes = $pdo->query("
        SELECT a.id, e.nombre, a.fecha, a.hora, a.estado
        FROM asistencias a
        JOIN empleados e ON e.id = a.empleado_id
        WHERE a.estado IN ('tardanza', 'falta')
        ORDER BY a.fecha DESC
    ")->fetchAll();

    $justificadas = $pdo->query("
        SELECT e.nombre, a.fecha, j.motivo, j.creado_en
        FROM justificaciones j
        JOIN asistencias a ON a.id = j.asistencia_id
        JOIN empleados e ON e.id = a.empleado_id
        ORDER BY j.creado_en DESC
        LIMIT 15
    ")->fetchAll();
} catch (PDOException $e) {
    $pendientes = [];
    $justificadas = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Justificaciones - Sistema de Asistencia CEA</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<h2 class="page-title">Justificaciones</h2>

<?php if ($mensaje): ?>
    <div class="panel" style="background:#f5f3ff; padding:14px 18px;"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="panel">
    <h3>Tardanzas y faltas pendientes de justificar</h3>
    <table>
        <thead>
            <tr><th>Nombre</th><th>Fecha</th><th>Hora</th><th>Estado</th><th>Justificar</th></tr>
        </thead>
        <tbody>
            <?php if (empty($pendientes)): ?>
                <tr><td colspan="5">No hay tardanzas ni faltas pendientes.</td></tr>
            <?php else: ?>
                <?php foreach ($pendientes as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['fecha']) ?></td>
                        <td><?= htmlspecialchars($p['hora']) ?></td>
                        <td><span class="badge <?= $p['estado'] === 'tardanza' ? 'late' : 'absent' ?>"><?= htmlspecialchars(ucfirst($p['estado'])) ?></span></td>
                        <td>
                            <details>
                                <summary class="btn-link">Justificar</summary>
                                <form method="POST" action="justificaciones.php" style="margin-top:8px;">
                                    <input type="hidden" name="accion" value="justificar">
                                    <input type="hidden" name="asistencia_id" value="<?= $p['id'] ?>">
                                    <div class="form-grid">
                                        <div class="field" style="min-width:220px;">
                                            <label>Motivo</label>
                                            <textarea name="motivo" rows="2" required style="padding:8px; border-radius:8px; border:1px solid #ddd;"></textarea>
                                        </div>
                                        <div class="field">
                                            <button type="submit" class="btn-primary" style="background:#5b4fe0; color:#fff;">Guardar</button>
                                        </div>
                                    </div>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="panel">
    <h3>Últimas justificaciones registradas</h3>
    <table>
        <thead>
            <tr><th>Nombre</th><th>Fecha</th><th>Motivo</th></tr>
        </thead>
        <tbody>
            <?php if (empty($justificadas)): ?>
                <tr><td colspan="3">Aún no hay justificaciones registradas.</td></tr>
            <?php else: ?>
                <?php foreach ($justificadas as $j): ?>
                    <tr>
                        <td><?= htmlspecialchars($j['nombre']) ?></td>
                        <td><?= htmlspecialchars($j['fecha']) ?></td>
                        <td><?= htmlspecialchars($j['motivo']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
