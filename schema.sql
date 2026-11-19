-- Script de creación de base de datos
-- Sistema de Gestión de Asistencia Automatizada
-- Motor: PostgreSQL

CREATE TABLE usuarios_sistema (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL DEFAULT 'administrador',
    creado_en TIMESTAMP DEFAULT NOW()
);

CREATE TABLE carreras (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE empleados (
    id SERIAL PRIMARY KEY,
    id_huella INTEGER UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    cargo VARCHAR(100),
    carrera_id INTEGER REFERENCES carreras(id),
    estado VARCHAR(20) DEFAULT 'activo', -- activo, retirado
    creado_en TIMESTAMP DEFAULT NOW()
);

CREATE TABLE horarios (
    id SERIAL PRIMARY KEY,
    empleado_id INTEGER REFERENCES empleados(id) ON DELETE CASCADE,
    hora_entrada TIME NOT NULL,
    hora_salida TIME NOT NULL,
    tolerancia_minutos INTEGER DEFAULT 10
);

CREATE TABLE asistencias (
    id SERIAL PRIMARY KEY,
    empleado_id INTEGER REFERENCES empleados(id) ON DELETE CASCADE,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    tipo_marcacion VARCHAR(20) DEFAULT 'huella', -- huella, tarjeta
    estado VARCHAR(20) DEFAULT 'puntual', -- puntual, tardanza, falta
    sincronizado_en TIMESTAMP DEFAULT NOW()
);

CREATE TABLE justificaciones (
    id SERIAL PRIMARY KEY,
    asistencia_id INTEGER REFERENCES asistencias(id) ON DELETE CASCADE,
    motivo TEXT NOT NULL,
    creado_en TIMESTAMP DEFAULT NOW()
);

-- Carreras de ejemplo (ajusta los nombres a las carreras reales del CEA)
INSERT INTO carreras (nombre) VALUES
    ('Administración de Empresas'),
    ('Informática'),
    ('Contaduría General');

-- Usuario administrador de prueba
-- clave en texto plano por ahora: admin123 (se guarda ya cifrada abajo)
INSERT INTO usuarios_sistema (nombre, usuario, clave, rol)
VALUES ('Administrador CEA', 'admin', '$2y$10$1C9smkuYyhSqsELoZX29luUPiy9LJrOIl91PNbgsNS80pdJThY9be', 'administrador');
-- Nota: ese hash corresponde a la clave "admin123" generada con password_hash() de PHP.
-- Usuario de prueba: admin / admin123
