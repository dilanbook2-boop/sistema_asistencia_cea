<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

try {
    $totalEmpleados = $pdo->query("SELECT COUNT(*) AS n FROM empleados WHERE estado = 'activo'")->fetch()['n'];
    $puntualesHoy = $pdo->query("SELECT COUNT(*) AS n FROM asistencias WHERE fecha = CURRENT_DATE AND estado = 'puntual'")->fetch()['n'];
    $tardanzasHoy = $pdo->query("SELECT COUNT(*) AS n FROM asistencias WHERE fecha = CURRENT_DATE AND estado = 'tardanza'")->fetch()['n'];
    $faltasHoy = $pdo->query("SELECT COUNT(*) AS n FROM asistencias WHERE fecha = CURRENT_DATE AND estado = 'falta'")->fetch()['n'];

    $ultimas = $pdo->query("
        SELECT e.nombre, a.fecha, a.hora, a.tipo_marcacion, a.estado
        FROM asistencias a
        JOIN empleados e ON e.id = a.empleado_id
        ORDER BY a.sincronizado_en DESC
        LIMIT 8
    ")->fetchAll();
} catch (PDOException $e) {
    $totalEmpleados = $puntualesHoy = $tardanzasHoy = $faltasHoy = 0;
    $ultimas = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - Sistema de Asistencia CEA</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<h2 class="page-title">Inicio</h2>

<div class="cards">
    <div class="card">
        <div class="value"><?= $totalEmpleados ?></div>
        <div class="label">Personal activo</div>
    </div>
    <div class="card">
        <div class="value"><?= $puntualesHoy ?></div>
        <div class="label">Puntuales hoy</div>
    </div>
    <div class="card">
        <div class="value"><?= $tardanzasHoy ?></div>
        <div class="label">Tardanzas hoy</div>
    </div>
    <div class="card">
        <div class="value"><?= $faltasHoy ?></div>
        <div class="label">Faltas hoy</div>
    </div>
</div>

<div class="action-bar">
    <div class="info">
        <strong>Estado del biométrico: desconectado (demo)</strong>
        Última sincronización: sin registros aún
    </div>
    <button type="button">Sincronizar ahora</button>
</div>

<div class="panel">
    <h3>Últimas marcaciones sincronizadas</h3>
    <table>
        <thead>
            <tr><th>Nombre</th><th>Fecha</th><th>Hora</th><th>Tipo</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php if (empty($ultimas)): ?>
                <tr><td colspan="5">Aún no hay registros sincronizados desde el biométrico.</td></tr>
            <?php else: ?>
                <?php foreach ($ultimas as $fila): ?>
                    <?php $clase = $fila['estado'] === 'puntual' ? 'ok' : ($fila['estado'] === 'tardanza' ? 'late' : ($fila['estado'] === 'justificada' ? 'justified' : 'absent')); ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['nombre']) ?></td>
                        <td><?= htmlspecialchars($fila['fecha']) ?></td>
                        <td><?= htmlspecialchars($fila['hora']) ?></td>
                        <td><?= htmlspecialchars($fila['tipo_marcacion']) ?></td>
                        <td><span class="badge <?= $clase ?>"><?= htmlspecialchars(ucfirst($fila['estado'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
