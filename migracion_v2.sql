-- Migración v2: agrega carreras, vínculo de empleados a carrera, y justificaciones.
-- Seguro de correr sobre una base de datos que ya tenía el schema.sql original:
-- no borra nada de lo que ya tengas cargado (usuarios, empleados, asistencias).

CREATE TABLE IF NOT EXISTS carreras (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO carreras (nombre) VALUES
    ('Administración de Empresas'),
    ('Informática'),
    ('Contaduría General')
ON CONFLICT (nombre) DO NOTHING;

ALTER TABLE empleados
    ADD COLUMN IF NOT EXISTS carrera_id INTEGER REFERENCES carreras(id);

CREATE TABLE IF NOT EXISTS justificaciones (
    id SERIAL PRIMARY KEY,
    asistencia_id INTEGER REFERENCES asistencias(id) ON DELETE CASCADE,
    motivo TEXT NOT NULL,
    creado_en TIMESTAMP DEFAULT NOW()
);

-- Permite marcar una asistencia como justificada (si tu columna "estado" es texto libre,
-- este UPDATE no es obligatorio, solo referencia de los valores usados por el sistema).
-- Valores de estado usados en asistencias: puntual, tardanza, falta, justificada
