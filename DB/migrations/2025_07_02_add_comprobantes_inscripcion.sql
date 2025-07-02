-- SQL script to modify database for multiple payment receipts

-- Add new column to inscripciones table for linking payment option
ALTER TABLE inscripciones
    ADD COLUMN id_opcion_pago INT NULL AFTER id_participante;

-- Table to store each payment receipt of an inscription
CREATE TABLE comprobantes_inscripcion (
    id_comprobante INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    numero_pago INT NOT NULL,
    metodo_pago VARCHAR(100) NOT NULL,
    referencia_pago VARCHAR(255) NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL,
    comprobante_path VARCHAR(255) NOT NULL,
    fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripciones(id_inscripcion)
);
