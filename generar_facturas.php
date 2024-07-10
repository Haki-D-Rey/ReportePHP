<?php

require_once 'conexion.php';
require_once 'builderFacturaPDF.php'; // Asegúrate de tener el archivo builderFacturaPDF.php en tu proyecto

class GeneradorFacturas
{
    private $conexionBD;

    public function __construct()
    {
        date_default_timezone_set('America/Guatemala');
        $this->conexionBD = new ConexionBD();
    }

    public function generarFacturas()
    {
        $pageSize = 100; // Número de registros por página
        $page = 0; // Página inicial

        do {
            $offset = $page * $pageSize;
            $query = "SELECT tb.*, tbv.id_participante as IdParticipanteVerificacion 
                      FROM wp_eiparticipante tb 
                      INNER JOIN wp_eiparticipante_verificacion tbv ON tb.id_participante = tbv.id_participante
                      WHERE tb.estaInscrito = 1 
                      AND tb.evento IN ('XXI PRECONGRESO CIENTÍFICO MÉDICO', 'XXI CONGRESO CIENTÍFICO MÉDICO', 'XXI PRECONGRESO y CONGRESO CIENTÍFICO MÉDICO') 
                      AND tb.id_participante >= 1030 LIMIT ? OFFSET ?";

            $stmt = $this->conexionBD->getConexion()->prepare($query);
            $stmt->bind_param('ii', $pageSize, $offset);
            $stmt->execute();

            $result = $stmt->get_result();


            while ($factura = $result->fetch_assoc()) {
                $this->generarYGuardarFactura($factura);
            }

            $page++;
        } while ($result->num_rows > 0);
    }


    private function generarYGuardarFactura($factura)
    {
        try {
            // Generar la factura PDF
            $facturaPDF = new builderFacturaPDF();
            $facturaPDF->setEmpresa('HOSPITAL MILITAR ESCUELA "DR. ALEJANDRO DÁVILA BOLAÑOS"', "J0310000002860", "Rotonda el Guegüense 400 m. al Este & 300 m. al Sur. Managua, Nicaragua.", "(505) 1801-1000", "congresomedico@hospitalmilitar.com.ni");
            $facturaPDF->setFactura($factura['id_participante'], date("d-m-Y"), "");
            $facturaPDF->setCliente($factura['nombre'], $factura['identificacion'], "DNI", $factura['telefono'], $factura['departamento'] . ', ' . $factura['pais']);

            //
            $datosInscripcion = $this->getDetilsEventParticipant($factura['id_participante']);
            $valorPrecio = mb_strtoupper($datosInscripcion[0]['divisa'] . '' . $datosInscripcion[0]['precio'] . ' USD', 'UTF-8');

            // Añadir productos
            $facturaPDF->agregarProducto(mb_strtoupper($datosInscripcion[0]['planinscripcion'], 'UTF-8'), mb_strtoupper($datosInscripcion[0]['detalleprecongreso'], 'UTF-8'), 1, $valorPrecio,  mb_strtoupper($datosInscripcion[0]['fecha'], 'UTF-8'), $valorPrecio);
            $codigoBarra = $this->generarCodigoBarraPersonalizado($datosInscripcion[0]['nombreCompleto'], $datosInscripcion[0]['codigo_participante']);
            $facturaPDF->agregarDetallesFacturas($valorPrecio, "$0.00 USD", $valorPrecio, $codigoBarra);
            $pdfContent = $facturaPDF->imprimirFactura();

            // Guardar el PDF y convertirlo a base64
            $file_path = __DIR__ . '/reporte_factura_' . trim($datosInscripcion[0]['nombreCompleto']) . '-' . $datosInscripcion[0]['codigo_participante'] . '.pdf';
            $facturaPDF->Output('F', $file_path);

            // Convert PDF content to Base64
            $pdfBase64 = $this->pdfToBase64($pdfContent);

            $conexion = $this->conexionBD->getConexion();

            $validate = $this->validateExistsDataPdf($conexion, $datosInscripcion[0]['id_participante_verificacion']);
            $fecha_actual = date('Y-m-d H:i:s');

            if ($validate > 0) {
                // Record exists, perform an update
                $updateQuery = "UPDATE wp_eiparticipante_factura_evento 
                                SET descripcion = ?, archivoBase64 = ?, TYPEMIME = ?, fecha_modificacion = ?
                                WHERE id_participante_verificacion = ?";
                $updateStmt = $conexion->prepare($updateQuery);
                if ($updateStmt === false) {
                    throw new Exception("Error preparing the update statement: " . $conexion->error);
                }

                $updateStmt->bind_param('ssssi', $descripcion, $pdfBase64, $typeMIME, $fecha_actual,$datosInscripcion[0]['id_participante_verificacion']);
                if (!$updateStmt->execute()) {
                    throw new Exception("Error executing the update statement: " . $updateStmt->error);
                }

                $updateStmt->close();
            } else {
                // Prepare the insert query
                $insertQuery = "INSERT INTO wp_eiparticipante_factura_evento 
                                (id_participante_verificacion, descripcion, archivoBase64, TYPEMIME, fecha_creacion) 
                                VALUES (?, ?, ?, ?, ?)";


                $insertStmt = $conexion->prepare($insertQuery);

                if ($insertStmt === false) {
                    throw new Exception("Error preparing the statement: " . $conexion->error);
                }

                $descripcion = 'reporte_factura_' . trim($datosInscripcion[0]['nombreCompleto']) . '-' . $datosInscripcion[0]['codigo_participante'] . '.pdf';
                $typeMIME = 'application/pdf';

                // Bind parameters
                $insertStmt->bind_param('issss', $datosInscripcion[0]['id_participante_verificacion'], $descripcion, $pdfBase64, $typeMIME, $fecha_actual );

                // Execute the statement
                if (!$insertStmt->execute()) {
                    throw new Exception("Error executing the statement: " . $insertStmt->error);
                }

                // Close the statement
                $insertStmt->close();
            }

            echo 'Factura generada y guardada en la base de datos para el participante ID: ' . $factura['id_participante'] . '<br>';
        } catch (Exception $e) {
            echo 'Error al generar/guardar la factura para el participante ID ' . $factura['id_participante'] . ': ' . $e->getMessage() . '<br>';
        }
    }

    private function validateExistsDataPdf($conexion, $id)
    {
        $recordCount = 0;
        // Check if the record exists
        $checkQuery = "SELECT COUNT(*) FROM wp_eiparticipante_factura_evento WHERE id_participante_verificacion = ?";
        $checkStmt = $conexion->prepare($checkQuery);
        if ($checkStmt === false) {
            throw new Exception("Error preparing the check statement: " . $conexion->error);
        }

        $checkStmt->bind_param('i', $id);
        $checkStmt->execute();
        $checkStmt->bind_result($recordCount);
        $checkStmt->fetch();
        $checkStmt->close();

        return $recordCount;
    }

    private function pdfToBase64($pdfContent)
    {
        // if (!file_exists($pdfFilePath)) {
        //     throw new Exception("El archivo PDF '$pdfFilePath' no existe.");
        // }
        // $pdfContent = file_get_contents($pdfFilePath);
        return base64_encode($pdfContent);
    }


    function getDetilsEventParticipant($idParticipante)
    {
        $query = "SELECT
                tbv.id as id_participante_verificacion,
                CONCAT(tb.nombre, '', tb.apellidos) as nombreCompleto,
                tbins.descripcion AS planinscripcion,
                tbins.valor AS precio,
                tbins.unidad_divisa AS divisa,
                IFNULL(
                REPLACE
                    (
                        JSON_UNQUOTE(
                            JSON_EXTRACT(
                                tbrd.json_relacion_inscripcion,
                                '$[0].RelacionEvento[0].Descripcion'
                            )
                        ),
                        ',',
                        ''
                    ),
                    'EL PLAN ADQUIRIDO NO INCLUYE PRECONGRESO'
                ) AS detalleprecongreso,
                IFNULL(
                    REPLACE
                        (
                            JSON_UNQUOTE(
                                JSON_EXTRACT(
                                    tbrd.json_relacion_inscripcion,
                                    '$[0].RelacionEvento[0].Codigo'
                                )
                            ),
                            ',',
                            ''
                        ),
                        'PINS-S00-NI'
                ) AS CodigoRelacionEvento,
                tb.fecha,
                tbv.codigo_participante
                FROM
                    wp_eiparticipante tb
                INNER JOIN wp_tipo_planes_inscripcion tbins ON
                    tb.id_tipo_planes_inscripcion = tbins.id
                INNER JOIN wp_eiparticipante_verificacion tbv ON
                    tb.id_participante = tbv.id_participante
                INNER JOIN wp_tipo_planes_inscripcion_relacion_detalles tbrd ON
                    tbv.id = tbrd.id_participante_verificacion
                WHERE
                    tb.estaInscrito = 1 
                    AND tb.evento IN(
                        'XXI PRECONGRESO CIENTÍFICO MÉDICO',
                        'XXI CONGRESO CIENTÍFICO MÉDICO',
                        'XXI PRECONGRESO y CONGRESO CIENTÍFICO MÉDICO'
                    ) 
                    AND tb.id_participante >= 1030
                    AND tb.id_participante = ?";

        $stmt = $this->conexionBD->getConexion()->prepare($query);
        $stmt->bind_param('i', $idParticipante);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $datosParticipantesRelacionEventos[] = $row;
        }

        return $datosParticipantesRelacionEventos;
    }

    function generarCodigoBarraPersonalizado($nombreCompleto, $codigoPersonalizado)
    {

        $palabras = explode(" ", $nombreCompleto);
        $iniciales = array();

        foreach ($palabras as $palabra) {
            $iniciales[] = strtoupper(substr($palabra, 0, 1));
        }
        $inicialesStr = implode("", $iniciales);
        $codigoFinal = $inicialesStr . "-" . $codigoPersonalizado;

        return $codigoFinal;
    }

    public function __destruct()
    {
        $this->conexionBD->cerrarConexion();
    }
}
