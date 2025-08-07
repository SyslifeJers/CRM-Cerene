<?php
require_once '../DB/Conexion.php';
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $database = new Database();
    $id_curso = isset($_POST['id_curso']) ? intval($_POST['id_curso']) : (isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0);

    $conn = $database->getConnection();

    $query = $conn->prepare("
        SELECT 
            i.id_inscripcion,
            p.titulo,
            CONCAT(p.titulo, ' ', p.nombre, ' ', p.apellido) AS nombre_completo,
            COALESCE(
                (
                    SELECT SUM(IFNULL(ci.monto_pagado, 0))
                    FROM comprobantes_inscripcion ci
                    WHERE ci.validado = 1
                      AND ci.id_inscripcion = i.id_inscripcion
                ),
                IFNULL(i.monto_pagado, 0)
            ) AS monto_validado,
            DATE_FORMAT(i.fecha_inscripcion, '%d/%m/%Y') AS fecha_inscripcion,
            p.telefono,
            (
                SELECT ci.comprobante_path
                FROM comprobantes_inscripcion ci
                WHERE ci.validado = 1
                  AND ci.id_inscripcion = i.id_inscripcion
                ORDER BY ci.id_comprobante DESC
                LIMIT 1
            ) AS comprobante_path
        FROM inscripciones i
        JOIN participantes p ON i.id_participante = p.id_participante
        JOIN cursos c ON i.id_curso = c.id_curso
        LEFT JOIN opciones_pago op ON i.IdOpcionPago = op.id_opcion
        WHERE i.id_curso = ?
          AND (i.estado = 'pago_validado' OR i.estado = 'pagos programados' OR i.estado = 'Revision de pago')
          AND p.email IS NOT NULL
          AND p.email <> ''
    ");

    $query->bind_param('i', $id_curso);
    $query->execute();
    $result = $query->get_result();

    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    $headers = ['ID Inscripción', 'Título', 'Nombre Completo', 'Monto Validado', 'Fecha Inscripción', 'Teléfono', 'Comprobante'];
    $sheet->fromArray($headers, NULL, 'A1');

    $rowNum = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue("A{$rowNum}", $row['id_inscripcion']);
        $sheet->setCellValue("B{$rowNum}", $row['titulo']);
        $sheet->setCellValue("C{$rowNum}", $row['nombre_completo']);
        $sheet->setCellValue("D{$rowNum}", $row['monto_validado']);
        $sheet->setCellValue("E{$rowNum}", $row['fecha_inscripcion']);
        $sheet->setCellValue("F{$rowNum}", $row['telefono']);

        if ($row['comprobante_path']) {
            $url = "https://cursos.clinicacerene.com/documentos/" . $row['comprobante_path'];
            $sheet->setCellValue("G{$rowNum}", 'Ver comprobante');
            $sheet->getCell("G{$rowNum}")->getHyperlink()->setUrl($url);
            $sheet->getStyle("G{$rowNum}")->getFont()->getColor()->setARGB('FF0000FF');
            $sheet->getStyle("G{$rowNum}")->getFont()->setUnderline(true);
        } else {
            $sheet->setCellValue("G{$rowNum}", 'faltante');
        }

        $rowNum++;
    }

    // Descargar Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="reporte_curso_'.$id_curso.'.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
