<?php $pagina_actual = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <div class="brand">
        Asistencia CEA
        <small>Kata KT-F261</small>
    </div>
    <a href="dashboard.php" class="<?= $pagina_actual === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="empleados.php" class="<?= $pagina_actual === 'empleados.php' ? 'active' : '' ?>">Personal</a>
    <a href="asistencias.php" class="<?= $pagina_actual === 'asistencias.php' ? 'active' : '' ?>">Asistencias</a>
    <a href="reportes.php" class="<?= $pagina_actual === 'reportes.php' ? 'active' : '' ?>">Reportes</a>
    <a href="configuracion.php" class="<?= $pagina_actual === 'configuracion.php' ? 'active' : '' ?>">Configuración</a>
</div>
