<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$miHorario = null;
try {
    if (es_docente()) {
        // Vista del docente: solo SUS datos, acumulado del mes actual
        $eid = empleado_actual_id();
        $cont = function ($estado) use ($pdo, $eid) {
            $st = $pdo->prepare("SELECT COUNT(*) AS n FROM asistencias WHERE empleado_id = :e AND estado = :s AND date_trunc('month', fecha) = date_trunc('month', CURRENT_DATE)");
            $st->execute(['e' => $eid, 's' => $estado]);
            return $st->fetch()['n'];
        };
        $puntualesHoy = $cont('puntual');
        $tardanzasHoy = $cont('tardanza');
        $faltasHoy = $cont('falta');
        $justificadasMes = $cont('justificada');

        $st = $pdo->prepare("SELECT a.fecha, a.hora, a.tipo_marcacion, a.estado FROM asistencias a WHERE a.empleado_id = :e ORDER BY a.fecha DESC, a.hora DESC LIMIT 8");
        $st->execute(['e' => $eid]);
        $ultimas = $st->fetchAll();

        $st = $pdo->prepare("SELECT hora_entrada, hora_salida, tolerancia_minutos FROM horarios WHERE empleado_id = :e LIMIT 1");
        $st->execute(['e' => $eid]);
        $miHorario = $st->fetch();
        $totalEmpleados = 0;
    } else {
        $totalEmpleados = $pdo->query("SELECT COUNT(*) AS n FROM empleados WHERE estado = 'activo'")->fetch()['n'];
        $puntualesHoy = $pdo->query("SELECT COUNT(*) AS n FROM asistencias WHERE fecha = CURRENT_DATE AND estado = 'puntual'")->fetch()['n'];
        $tardanzasHoy = $pdo->query("SELECT COUNT(*) AS n FROM asistencias WHERE fecha = CURRENT_DATE AND estado = 'tardanza'")->fetch()['n'];
        $faltasHoy = $pdo->query("SELECT COUNT(*) AS n FROM asistencias WHERE fecha = CURRENT_DATE AND estado = 'falta'")->fetch()['n'];
        $justificadasMes = 0;

        $ultimas = $pdo->query("
            SELECT e.nombre, a.fecha, a.hora, a.tipo_marcacion, a.estado
            FROM asistencias a
            JOIN empleados e ON e.id = a.empleado_id
            ORDER BY a.sincronizado_en DESC
            LIMIT 8
        ")->fetchAll();
    }
} catch (PDOException $e) {
    $totalEmpleados = $puntualesHoy = $tardanzasHoy = $faltasHoy = $justificadasMes = 0;
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

<?php if (isset($_GET['denegado'])): ?>
    <div class="notice-warn">No tienes permiso para acceder a esa sección.</div>
<?php endif; ?>

<?php if (es_docente() && !empleado_actual_id()): ?>
    <div class="notice-warn">Tu usuario aún no está vinculado a una ficha de personal. Pide al administrador que lo vincule para ver tus marcaciones.</div>
<?php endif; ?>

<?php if (es_docente()): ?>
<div class="cards">
    <div class="card"><div class="value"><?= $puntualesHoy ?></div><div class="label">Puntuales este mes</div></div>
    <div class="card"><div class="value"><?= $tardanzasHoy ?></div><div class="label">Tardanzas este mes</div></div>
    <div class="card"><div class="value"><?= $faltasHoy ?></div><div class="label">Faltas este mes</div></div>
    <div class="card"><div class="value"><?= $justificadasMes ?></div><div class="label">Justificadas este mes</div></div>
</div>

<div class="panel">
    <h3>Mi horario</h3>
    <?php if ($miHorario): ?>
        <p style="font-size:14px;">
            Entrada: <strong><?= htmlspecialchars(substr($miHorario['hora_entrada'], 0, 5)) ?></strong> &nbsp;|&nbsp;
            Salida: <strong><?= htmlspecialchars(substr($miHorario['hora_salida'], 0, 5)) ?></strong> &nbsp;|&nbsp;
            Tolerancia: <strong><?= (int)$miHorario['tolerancia_minutos'] ?> min</strong>
        </p>
    <?php else: ?>
        <p class="muted">Aún no tienes un horario asignado.</p>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="cards">
    <div class="card"><div class="value"><?= $totalEmpleados ?></div><div class="label">Personal activo</div></div>
    <div class="card"><div class="value"><?= $puntualesHoy ?></div><div class="label">Puntuales hoy</div></div>
    <div class="card"><div class="value"><?= $tardanzasHoy ?></div><div class="label">Tardanzas hoy</div></div>
    <div class="card"><div class="value"><?= $faltasHoy ?></div><div class="label">Faltas hoy</div></div>
</div>

<div class="action-bar">
    <div class="info">
        <strong>Estado del biométrico: desconectado (demo)</strong>
        Última sincronización: sin registros aún
    </div>
    <button type="button">Sincronizar ahora</button>
</div>
<?php endif; ?>

<div class="panel">
    <h3><?= es_docente() ? 'Mis últimas marcaciones' : 'Últimas marcaciones sincronizadas' ?></h3>
    <table>
        <thead>
            <tr><?php if (!es_docente()): ?><th>Nombre</th><?php endif; ?><th>Fecha</th><th>Hora</th><th>Tipo</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php if (empty($ultimas)): ?>
                <tr><td colspan="5">Aún no hay registros sincronizados desde el biométrico.</td></tr>
            <?php else: ?>
                <?php foreach ($ultimas as $fila): ?>
                    <?php $clase = $fila['estado'] === 'puntual' ? 'ok' : ($fila['estado'] === 'tardanza' ? 'late' : ($fila['estado'] === 'justificada' ? 'justified' : 'absent')); ?>
                    <tr>
                        <?php if (!es_docente()): ?><td><?= htmlspecialchars($fila['nombre']) ?></td><?php endif; ?>
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
