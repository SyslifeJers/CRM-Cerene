ALTER TABLE inscripciones
    ADD COLUMN estatus_certificado ENUM('pendiente', 'enviado', 'problema', 'reenviado') NOT NULL DEFAULT 'pendiente' AFTER estado;
