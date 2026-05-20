ALTER TABLE cursos
    ADD COLUMN clave_certificado VARCHAR(50) NULL AFTER clave_curso;

ALTER TABLE inscripciones
    ADD COLUMN clave_certificado VARCHAR(80) NULL AFTER estatus_certificado;

CREATE INDEX idx_inscripciones_clave_certificado ON inscripciones (clave_certificado);
