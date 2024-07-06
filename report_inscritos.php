<?php

require('./builderFacturaPDF.php'); // Asegúrate de tener el archivo code128.php en tu proyecto

/// Conexión a la base de datos
$dsn = 'mysql:host=c98055.sgvps.net;dbname=db2gdg4nfxpgyk';
$username = 'udeq5kxktab81';
$password = 'clmjsfgcrt5m';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa.<br>";

    $pageSize = 100; // Número de registros por página
    $page = 0; // Página inicial

    do {
        $offset = $page * $pageSize;
        $query = "SELECT tb.*, tbv.id_participante as IdParticipanteVerificacion FROM wp_eiparticipante tb 
        INNER JOIN wp_eiparticipante_verificacion tbv
            ON tb.id_participante = tbv.id_participante
        WHERE tb.estaInscrito = 1 AND tb.evento IN(
            'XXI PRECONGRESO CIENTÍFICO MÉDICO',
            'XXI CONGRESO CIENTÍFICO MÉDICO',
            'XXI PRECONGRESO y CONGRESO CIENTÍFICO MÉDICO'
        ) AND tb.id_participante >= 1030 LIMIT $pageSize OFFSET $offset";

        $stmt = $pdo->query($query);
        $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($facturas) === 0) {
            break; // Si no hay más registros, salir del bucle
        }

        // Procesar los datos obtenidos

        // Procesar los datos obtenidos
        foreach ($facturas as $factura) {
            // Generar la factura PDF
            $facturaPDF = new builderFacturaPDF();
            $facturaPDF->setEmpresa('HOSPITAL MILITAR ESCUELA "DR. ALEJANDRO DÁVILA BOLAÑOS"', "0000000000", "Rotonda el Guegüense 400 m. al Este & 300 m. al Sur. Managua, Nicaragua.", "(505) 1801-1000", "congresomedico@hospitalmilitar.com.ni");
            $facturaPDF->setFactura($factura['id_participante'], date("d-m-Y"), "");
            $facturaPDF->setCliente($factura['nombre'], $factura['identificacion'], "DNI", $factura['telefono'], $factura['direccion']);

            // Añadir productos
            $facturaPDF->agregarProducto('Plan Inscripcion', 'Detalle Precongreso', 1, '100.00', '100.00');
            $facturaPDF->imprimirFactura();

            // Guardar el PDF y convertirlo a base64
            $file_path = __DIR__ . '/reporte_factura_' . $factura['id_participante'] . '.pdf';
            $facturaPDF->Output('F', $file_path);

            $pdfBase64 = pdfToBase64($file_path);

            // Guardar en la base de datos
            $updateQuery = "UPDATE wp_eiparticipante_factura_evento SET archivoBase64  = :pdfBase64 WHERE id_participante = :IdParticipanteVerificacion";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                'pdfBase64' => $pdfBase64,
                'id_participante' => $factura['IdParticipanteVerificacion']
            ]);

            echo 'Factura generada y guardada en la base de datos para el participante ID: ' . $factura['id_participante'] . '<br>';
        }

        $page++; 
    } while (true);

} catch (PDOException $e) {
    echo 'Conexión fallida: ' . $e->getMessage();
    exit;
}

// Ejemplo de uso:
$factura = new builderFacturaPDF();
$factura->setEmpresa('HOSPITAL MILITAR ESCUELA "DR. ALEJANDRO DÁVILA BOLAÑOS"', "0000000000", "Rotonda el Guegüense 400 m. al Este & 300 m. al Sur. Managua, Nicaragua.", "(505) 1801-1000", "congresomedico@hospitalmilitar.com.ni");
$factura->setFactura("1", "13-09-2022", "");
$factura->setCliente("Carlos Alfaro", "00000000", "DNI", "00000000", "Managua, Nicaragua, Centro América");

$productos = array(
    array(
        "descripcion" => "Nombre de producto a vendersaddddddddddddddddddddddddddddddddddddddddddddddddddd",
        "cantidad" => "7",
        "precio" => "$10 USD",
        "descuento" => "$0.00 USD",
        "subtotal" => "$70.00 USD"
    )
);

foreach ($productos as $producto) {
    $factura->agregarProducto($producto['descripcion'], $producto['cantidad'], $producto['precio'], $producto['descuento'], $producto['subtotal']);
}

$factura->imprimirFactura();


// Función para convertir PDF a base64
function pdfToBase64($pdfFilePath) {
    // Verificar si el archivo existe
    if (!file_exists($pdfFilePath)) {
        die("El archivo PDF '$pdfFilePath' no existe.");
    }

    // Obtener el contenido del PDF en binario
    $pdfContent = file_get_contents($pdfFilePath);

    // Convertir el contenido a base64
    $base64 = base64_encode($pdfContent);

    return $base64;
}
?>