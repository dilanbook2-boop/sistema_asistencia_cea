<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$mensaje = '';

// Registrar nuevo docente + su horario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_docente') {
    $nombre = trim($_POST['nombre'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $idHuella = trim($_POST['id_huella'] ?? '');
    $carreraId = $_POST['carrera_id'] ?? '';
    $horaEntrada = $_POST['hora_entrada'] ?? '';
    $horaSalida = $_POST['hora_salida'] ?? '';
    $tolerancia = $_POST['tolerancia'] ?? 10;

    if ($nombre !== '' && $idHuella !== '' && $carreraId !== '') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO empleados (nombre, cargo, id_huella, carrera_id) VALUES (:nombre, :cargo, :id_huella, :carrera_id) RETURNING id');
            $stmt->execute([
                'nombre' => $nombre,
                'cargo' => $cargo,
                'id_huella' => $idHuella,
                'carrera_id' => $carreraId,
            ]);
            $empleadoId = $stmt->fetch()['id'];

            if ($horaEntrada !== '' && $horaSalida !== '') {
                $stmt2 = $pdo->prepare('INSERT INTO horarios (empleado_id, hora_entrada, hora_salida, tolerancia_minutos) VALUES (:eid, :he, :hs, :tol)');
                $stmt2->execute(['eid' => $empleadoId, 'he' => $horaEntrada, 'hs' => $horaSalida, 'tol' => $tolerancia]);
            }

            $pdo->commit();
            $mensaje = 'Docente registrado correctamente.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensaje = 'Error al registrar: ' . $e->getMessage();
        }
    } else {
        $mensaje = 'Completa al menos nombre, ID de huella y carrera.';
    }
}

// Editar horario de un docente ya existente (ej. cambio de semestre)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar_horario') {
    $empleadoId = $_POST['empleado_id'] ?? '';
    $horaEntrada = $_POST['hora_entrada'] ?? '';
    $horaSalida = $_POST['hora_salida'] ?? '';
    $tolerancia = $_POST['tolerancia'] ?? 10;

    if ($empleadoId !== '' && $horaEntrada !== '' && $horaSalida !== '') {
        $existe = $pdo->prepare('SELECT id FROM horarios WHERE empleado_id = :eid');
        $existe->execute(['eid' => $empleadoId]);
        $fila = $existe->fetch();

        if ($fila) {
            $upd = $pdo->prepare('UPDATE horarios SET hora_entrada = :he, hora_salida = :hs, tolerancia_minutos = :tol WHERE empleado_id = :eid');
            $upd->execute(['he' => $horaEntrada, 'hs' => $horaSalida, 'tol' => $tolerancia, 'eid' => $empleadoId]);
        } else {
            $ins = $pdo->prepare('INSERT INTO horarios (empleado_id, hora_entrada, hora_salida, tolerancia_minutos) VALUES (:eid, :he, :hs, :tol)');
            $ins->execute(['eid' => $empleadoId, 'he' => $horaEntrada, 'hs' => $horaSalida, 'tol' => $tolerancia]);
        }
        $mensaje = 'Horario actualizado correctamente.';
    }
}

try {
    $carreras = $pdo->query('SELECT * FROM carreras ORDER BY nombre ASC')->fetchAll();

    $docentes = $pdo->query("
        SELECT e.*, c.nombre AS carrera_nombre, h.hora_entrada, h.hora_salida, h.tolerancia_minutos
        FROM empleados e
        LEFT JOIN carreras c ON c.id = e.carrera_id
        LEFT JOIN horarios h ON h.empleado_id = e.id
        WHERE e.estado = 'activo'
        ORDER BY c.nombre ASC, e.nombre ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $carreras = [];
    $docentes = [];
}

// Agrupar docentes por carrera para mostrarlos en secciones
$porCarrera = [];
foreach ($docentes as $d) {
    $clave = $d['carrera_nombre'] ?? 'Sin carrera asignada';
    $porCarrera[$clave][] = $d;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Personal - Sistema de Asistencia CEA</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<h2 class="page-title">Personal</h2>

<?php if ($mensaje): ?>
    <div class="panel" style="background:#f5f3ff; padding:14px 18px;"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="panel">
    <details>
        <summary>+ Registrar nuevo docente</summary>
        <form method="POST" action="personal.php">
            <input type="hidden" name="accion" value="crear_docente">
            <div class="form-grid">
                <div class="field">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="field">
                    <label>Cargo</label>
                    <input type="text" name="cargo" placeholder="Docente">
                </div>
                <div class="field">
                    <label>ID de huella</label>
                    <input type="number" name="id_huella" required>
                </div>
                <div class="field">
                    <label>Carrera</label>
                    <select name="carrera_id" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Hora entrada</label>
                    <input type="time" name="hora_entrada">
                </div>
                <div class="field">
                    <label>Hora salida</label>
                    <input type="time" name="hora_salida">
                </div>
                <div class="field">
                    <label>Tolerancia (min)</label>
                    <input type="number" name="tolerancia" value="10" style="width:90px;">
                </div>
                <div class="field">
                    <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#5b4fe0,#c23fc0); color:#fff;">Guardar docente</button>
                </div>
            </div>
        </form>
    </details>
</div>

<?php if (empty($porCarrera)): ?>
    <div class="panel">Aún no hay docentes registrados.</div>
<?php endif; ?>

<?php foreach ($porCarrera as $carreraNombre => $lista): ?>
    <div class="panel">
        <h3><?= htmlspecialchars($carreraNombre) ?> (<?= count($lista) ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>ID huella</th>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Horario</th>
                    <th>Tolerancia</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['id_huella']) ?></td>
                        <td><?= htmlspecialchars($d['nombre']) ?></td>
                        <td><?= htmlspecialchars($d['cargo']) ?></td>
                        <td>
                            <?= $d['hora_entrada'] ? htmlspecialchars(substr($d['hora_entrada'],0,5) . ' - ' . substr($d['hora_salida'],0,5)) : '<span class="muted">Sin horario</span>' ?>
                        </td>
                        <td><?= $d['tolerancia_minutos'] !== null ? $d['tolerancia_minutos'] . ' min' : '-' ?></td>
                        <td>
                            <details>
                                <summary class="btn-link">Editar horario</summary>
                                <form method="POST" action="personal.php" style="margin-top:8px;">
                                    <input type="hidden" name="accion" value="editar_horario">
                                    <input type="hidden" name="empleado_id" value="<?= $d['id'] ?>">
                                    <div class="form-grid">
                                        <div class="field">
                                            <label>Hora entrada</label>
                                            <input type="time" name="hora_entrada" value="<?= htmlspecialchars($d['hora_entrada'] ?? '') ?>" required>
                                        </div>
                                        <div class="field">
                                            <label>Hora salida</label>
                                            <input type="time" name="hora_salida" value="<?= htmlspecialchars($d['hora_salida'] ?? '') ?>" required>
                                        </div>
                                        <div class="field">
                                            <label>Tolerancia (min)</label>
                                            <input type="number" name="tolerancia" value="<?= htmlspecialchars($d['tolerancia_minutos'] ?? 10) ?>" style="width:90px;">
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
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<p class="muted">Para dar de baja a un docente o personal administrativo que se retira, ve a <strong>Configuración</strong>.</p>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
