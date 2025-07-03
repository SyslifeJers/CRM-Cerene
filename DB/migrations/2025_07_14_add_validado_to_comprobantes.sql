ALTER TABLE comprobantes_inscripcion
    ADD COLUMN validado TINYINT(1) NOT NULL DEFAULT 0 AFTER fecha_carga;
