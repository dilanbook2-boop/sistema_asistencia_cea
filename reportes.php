<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$soloMio = es_docente();
$eidFiltro = (int)(empleado_actual_id() ?? 0);

try {
    $stmtR = $pdo->prepare("
        SELECT
            e.nombre,
            c.nombre AS carrera,
            COUNT(*) FILTER (WHERE a.estado = 'puntual') AS puntuales,
            COUNT(*) FILTER (WHERE a.estado = 'tardanza') AS tardanzas,
            COUNT(*) FILTER (WHERE a.estado = 'falta') AS faltas,
            COUNT(*) FILTER (WHERE a.estado = 'justificada') AS justificadas
        FROM empleados e
        LEFT JOIN carreras c ON c.id = e.carrera_id
        LEFT JOIN asistencias a ON a.empleado_id = e.id
        WHERE e.estado = 'activo' " . ($soloMio ? 'AND e.id = :eid ' : '') . "
        GROUP BY e.nombre, c.nombre
        ORDER BY c.nombre ASC, e.nombre ASC
    ");
    $stmtR->execute($soloMio ? ['eid' => $eidFiltro] : []);
    $resumen = $stmtR->fetchAll();

    $stmt = $pdo->prepare("
        SELECT e.nombre, a.fecha, a.hora, a.tipo_marcacion, a.estado
        FROM asistencias a
        JOIN empleados e ON e.id = a.empleado_id
        WHERE a.fecha BETWEEN :desde AND :hasta " . ($soloMio ? 'AND a.empleado_id = :eid ' : '') . "
        ORDER BY a.fecha DESC, a.hora DESC
    ");
    $params = ['desde' => $desde, 'hasta' => $hasta];
    if ($soloMio) { $params['eid'] = $eidFiltro; }
    $stmt->execute($params);
    $detalle = $stmt->fetchAll();
} catch (PDOException $e) {
    $resumen = [];
    $detalle = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Sistema de Asistencia CEA</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<h2 class="page-title">Reportes</h2>

<div class="panel">
    <h3><?= es_admin() ? 'Resumen acumulado por docente' : 'Mi resumen acumulado' ?></h3>
    <table>
        <thead>
            <tr><th>Nombre</th><th>Carrera</th><th>Puntuales</th><th>Tardanzas</th><th>Faltas</th><th>Justificadas</th></tr>
        </thead>
        <tbody>
            <?php if (empty($resumen)): ?>
                <tr><td colspan="6">Aún no hay datos para generar el reporte.</td></tr>
            <?php else: ?>
                <?php foreach ($resumen as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nombre']) ?></td>
                        <td><?= htmlspecialchars($r['carrera'] ?? 'Sin carrera') ?></td>
                        <td><?= (int)$r['puntuales'] ?></td>
                        <td><?= (int)$r['tardanzas'] ?></td>
                        <td><?= (int)$r['faltas'] ?></td>
                        <td><?= (int)$r['justificadas'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="panel">
    <h3>Detalle de marcaciones por fecha</h3>
    <form method="GET" action="reportes.php" class="form-grid">
        <div class="field">
            <label>Desde</label>
            <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="field">
            <label>Hasta</label>
            <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
        </div>
        <div class="field">
            <button type="submit" class="btn-primary" style="background:#5b4fe0; color:#fff;">Filtrar</button>
        </div>
    </form>

    <table style="margin-top:16px;">
        <thead>
            <tr><th>Nombre</th><th>Fecha</th><th>Hora</th><th>Tipo</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php if (empty($detalle)): ?>
                <tr><td colspan="5">No hay marcaciones en ese rango de fechas.</td></tr>
            <?php else: ?>
                <?php foreach ($detalle as $d): ?>
                    <?php $clase = $d['estado'] === 'puntual' ? 'ok' : ($d['estado'] === 'tardanza' ? 'late' : ($d['estado'] === 'justificada' ? 'justified' : 'absent')); ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nombre']) ?></td>
                        <td><?= htmlspecialchars($d['fecha']) ?></td>
                        <td><?= htmlspecialchars($d['hora']) ?></td>
                        <td><?= htmlspecialchars($d['tipo_marcacion']) ?></td>
                        <td><span class="badge <?= $clase ?>"><?= htmlspecialchars(ucfirst($d['estado'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <p class="muted" style="margin-top:14px;">La exportación a PDF/Excel se implementa en la versión final (ej. con Dompdf o PhpSpreadsheet).</p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
