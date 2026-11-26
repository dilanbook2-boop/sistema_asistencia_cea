<?php $pagina_actual = basename($_SERVER['PHP_SELF']); ?>
<div class="topbar-dark">
    <div>LORETO CETAL NOCHE</div>
    <div class="topbar-right">
        <span class="user-label"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?> · <?= es_admin() ? 'Administrador' : 'Docente' ?></span>
        <a href="logout.php" class="logout-link">CERRAR SESIÓN</a>
    </div>
</div>
<div class="app-body">
    <div class="sidebar">
        <div class="logo-wrap">
            <?php if (file_exists(__DIR__ . '/../assets/logo.png')): ?>
                <img src="assets/logo.png" alt="Logo CEA" class="logo-img">
            <?php else: ?>
                <div class="logo-placeholder"></div>
            <?php endif; ?>
        </div>
        <nav class="side-nav">
            <a href="dashboard.php" class="<?= $pagina_actual === 'dashboard.php' ? 'active' : '' ?>">Inicio</a>
            <?php if (es_admin()): ?>
                <a href="personal.php" class="<?= $pagina_actual === 'personal.php' ? 'active' : '' ?>">Personal</a>
            <?php endif; ?>
            <a href="justificaciones.php" class="<?= $pagina_actual === 'justificaciones.php' ? 'active' : '' ?>">Justificaciones</a>
            <a href="reportes.php" class="<?= $pagina_actual === 'reportes.php' ? 'active' : '' ?>"><?= es_admin() ? 'Reportes' : 'Mis reportes' ?></a>
            <?php if (es_admin()): ?>
                <a href="configuracion.php" class="<?= $pagina_actual === 'configuracion.php' ? 'active' : '' ?>">Configuración</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="main">
