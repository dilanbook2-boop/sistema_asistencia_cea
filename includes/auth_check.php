<?php
/**
 * Verifica sesión iniciada y expone helpers de roles.
 * Roles: 'administrador' (acceso total) y 'docente' (solo ve su propia información).
 */
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Releer rol/vínculo desde la BD en cada petición: si se elimina el usuario
// o se le cambia el rol, el efecto es inmediato (sin esperar a que cierre sesión).
$stmtAuth = $pdo->prepare('SELECT id, nombre, rol, empleado_id FROM usuarios_sistema WHERE id = :id');
$stmtAuth->execute(['id' => $_SESSION['usuario_id']]);
$usuarioActual = $stmtAuth->fetch();

if (!$usuarioActual) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['usuario_nombre'] = $usuarioActual['nombre'];
$_SESSION['usuario_rol'] = $usuarioActual['rol'];
$_SESSION['empleado_id'] = $usuarioActual['empleado_id'];

function es_admin(): bool {
    return ($_SESSION['usuario_rol'] ?? '') === 'administrador';
}

function es_docente(): bool {
    return ($_SESSION['usuario_rol'] ?? '') === 'docente';
}

/** ID del empleado vinculado al docente que inició sesión (o null). */
function empleado_actual_id(): ?int {
    return !empty($_SESSION['empleado_id']) ? (int)$_SESSION['empleado_id'] : null;
}

/** Corta el acceso a pantallas exclusivas del administrador. */
function requiere_admin(): void {
    if (!es_admin()) {
        header('Location: dashboard.php?denegado=1');
        exit;
    }
}
