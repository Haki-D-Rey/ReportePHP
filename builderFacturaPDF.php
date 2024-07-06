<?php 

require('./code128.php'); 

class builderFacturaPDF extends PDF_Code128  {

    // Propiedades privadas para almacenar los datos de la factura
    private $empresa;
    private $factura;
    private $cliente;
    private $productos = array();

    protected $widths;
    protected $aligns;


    // Constructor
    function __construct() {
        parent::__construct();
    }

    function setEmpresa($nombre, $ruc, $direccion, $telefono, $email) {
        $this->empresa = array(
            "nombre" => $nombre,
            "ruc" => $ruc,
            "direccion" => $direccion,
            "telefono" => $telefono,
            "email" => $email
        );
    }

    function setFactura($numero, $fecha, $cajero) {
        $this->factura = array(
            "numero" => $numero,
            "fecha" => $fecha,
            "cajero" => $cajero
        );
    }

    function setCliente($nombre, $documento, $tipoDocumento, $telefono, $direccion) {
        $this->cliente = array(
            "nombre" => $nombre,
            "documento" => $documento,
            "tipoDocumento" => $tipoDocumento,
            "telefono" => $telefono,
            "direccion" => $direccion
        );
    }

    function agregarProducto($plandescripcion, $detallepre, $cantidad, $precio, $subtotal) {
        $this->productos[] = array(
            "plandescripcion" => $plandescripcion,
            "detallepre"  => $detallepre,
            "cantidad" => $cantidad,
            "precio" => $precio,
            "subtotal" => $subtotal
        );
    }

    function Header() {
        // Logo de la empresa
        $this->Image('./img/logo.png', 165, 12, 35, 35, 'PNG');
        $this->SetFont('Arial', '', 12);
        // Título
        $this->SetTextColor(20, 97, 21);
        $this->Cell(150, 10, strtoupper(iconv("UTF-8", "ISO-8859-1", $this->empresa['nombre'])), 0, 0, 'L');
        $this->Ln(9);

        // Información de la empresa
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(39, 39, 51);
        $this->Cell(150, 9, iconv("UTF-8", "ISO-8859-1", "RUC: " . $this->empresa['ruc']), 0, 0, 'L');
        $this->Ln(5);
        $this->Cell(150, 9, iconv("UTF-8", "ISO-8859-1", $this->empresa['direccion']), 0, 0, 'L');
        $this->Ln(5);
        $this->Cell(150, 9, iconv("UTF-8", "ISO-8859-1", "Teléfono:" . $this->empresa['telefono']), 0, 0, 'L');
        $this->Ln(5);
        $this->Cell(150, 9, iconv("UTF-8", "ISO-8859-1", "Email: " . $this->empresa['email']), 0, 0, 'L');
        $this->Ln(15);

        // Fecha de emisión y número de factura
        $this->SetFont('Arial', '', 10);
        $this->Cell(30, 7, iconv("UTF-8", "ISO-8859-1", "Fecha de emisión:"), 0, 0);
        $this->SetTextColor(97, 97, 97);
        $this->Cell(116, 7, iconv("UTF-8", "ISO-8859-1", date("d/m/Y", strtotime($this->factura['fecha'])) . " " . date("h:s A")), 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(39, 39, 51);
        $this->Cell(40, 7, iconv("UTF-8", "ISO-8859-1", "Factura Nro."), 0, 0, 'C');
        $this->Ln(7);

        // Detalles del cajero y número de factura
        $this->SetFont('Arial', '', 10);
        $this->Cell(12, 7, iconv("UTF-8", "ISO-8859-1", "Cliente:"), 0, 0, 'L');
        $this->SetTextColor(97, 97, 97);
        $this->Cell(134, 7, iconv("UTF-8", "ISO-8859-1", $this->cliente['nombre']), 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(97, 97, 97);
        $this->Cell(35, 7, iconv("UTF-8", "ISO-8859-1", strtoupper($this->factura['numero'])), 0, 0, 'C');
        $this->Ln(7);

        // Detalles del cliente
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(39, 39, 51);
        $this->Cell(18, 7, iconv("UTF-8", "ISO-8859-1", "Dirección:"), 0, 0);
        $this->SetTextColor(97, 97, 97);
        $this->Cell(75, 7, iconv("UTF-8", "ISO-8859-1", $this->cliente['direccion']), 0, 0);
        $this->SetTextColor(97, 97, 97);
        $this->Cell(33, 7, iconv("UTF-8", "ISO-8859-1", $this->cliente['tipoDocumento'] . ": " . $this->cliente['documento']), 0, 0, 'L');
        $this->SetTextColor(39, 39, 51);
        $this->Cell(16, 7, iconv("UTF-8", "ISO-8859-1", "Telefono:"), 0, 0, 'L');
        $this->SetTextColor(97, 97, 97);
        $this->Cell(20, 7, iconv("UTF-8", "ISO-8859-1", $this->cliente['telefono']), 0, 0);
        $this->SetTextColor(39, 39, 51);
        $this->Ln(9);
    }

    function Footer() {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, iconv("UTF-8", "ISO-8859-1", 'Página ' . $this->PageNo() ), 0, 0, 'C');
    }

    function agregarProductos() {
        // Cabecera de la tabla de productos
        $this->SetFont('Arial', '', 8);
        $this->SetFillColor(20, 97, 21);
        $this->SetDrawColor(20, 97, 21);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(60, 8, iconv("UTF-8", "ISO-8859-1", "Plan Inscripcion"), 1, 0, 'C', true);
        $this->Cell(60, 8, iconv("UTF-8", "ISO-8859-1", "Detalle Precongreso"), 1, 0, 'C', true);
        $this->Cell(15, 8, iconv("UTF-8", "ISO-8859-1", "Cantidad"), 1, 0, 'C', true);
        $this->Cell(19, 8, iconv("UTF-8", "ISO-8859-1", "Precio."), 1, 0, 'C', true);
        $this->Cell(32, 8, iconv("UTF-8", "ISO-8859-1", "Subtotal"), 1, 0, 'C', true);
        $this->Ln(); // Salto de línea después de la cabecera
    
        // Detalles de los productos
        $this->SetTextColor(39, 39, 51);
        $xInicial = $this->GetX(); // Guardar la posición X inicial

        $keysColumns = ['plandescripcion', 'detallepre', 'cantidad', 'precio', 'subtotal'];
        $this->SetWidths(array(60, 60, 15, 19, 32));
        foreach ($this->productos as $key => $producto) {
            $this->Row($producto,  $keysColumns);
        }
    }

    
    
    function agregarTotales($subtotal, $iva, $total) {
        // Totales
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(100, 7, iconv("UTF-8", "ISO-8859-1", ''), 'T', 0, 'C');
        $this->Cell(15, 7, iconv("UTF-8", "ISO-8859-1", ''), 'T', 0, 'C');
        $this->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", "SUBTOTAL"), 'T', 0, 'C');
        $this->Cell(34, 7, iconv("UTF-8", "ISO-8859-1", "+ " . $subtotal), 'T', 0, 'C');
        $this->Ln(7);

        $this->Cell(100, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(15, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", "IVA (13%)"), '', 0, 'C');
        $this->Cell(34, 7, iconv("UTF-8", "ISO-8859-1", "+ " . $iva), '', 0, 'C');
        $this->Ln(7);

        $this->Cell(100, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(15, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", "TOTAL A PAGAR"), 'T', 0, 'C');
        $this->Cell(34, 7, iconv("UTF-8", "ISO-8859-1", $total), 'T', 0, 'C');
        $this->Ln(7);

        $this->Cell(100, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(15, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", "TOTAL PAGADO"), '', 0, 'C');
        $this->Cell(34, 7, iconv("UTF-8", "ISO-8859-1", "$100.00 USD"), '', 0, 'C');
        $this->Ln(7);

        $this->Cell(100, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(15, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", "CAMBIO"), '', 0, 'C');
        $this->Cell(34, 7, iconv("UTF-8", "ISO-8859-1", "$30.00 USD"), '', 0, 'C');
        $this->Ln(7);

        $this->Cell(100, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(15, 7, iconv("UTF-8", "ISO-8859-1", ''), '', 0, 'C');
        $this->Cell(32, 7, iconv("UTF-8", "ISO-8859-1", "USTED AHORRA"), '', 0, 'C');
        $this->Cell(34, 7, iconv("UTF-8", "ISO-8859-1", "$0.00 USD"), '', 0, 'C');
        $this->Ln(12);
    }

    function imprimirFactura() {
        $this->AddPage();
        $this->agregarProductos();
        $this->agregarTotales("$70.00 USD", "$0.00 USD", "$100.00 USD");

        // Texto final
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(39, 39, 51);
        $this->MultiCell(0, 9, iconv("UTF-8", "ISO-8859-1", "*** Precios de productos incluyen impuestos. Para poder realizar un reclamo o devolución debe de presentar esta factura ***"), 0, 'C', false);

        $this->Ln(9);

        // Código de barras
        $this->SetFillColor(39, 39, 51);
        $this->SetDrawColor(23, 83, 201);
        $this->Code128(72, $this->GetY(), "COD000001V0001", 70, 20);
        $this->SetXY(12, $this->GetY() + 21);
        $this->SetFont('Arial', '', 12);
        $this->MultiCell(0, 5, iconv("UTF-8", "ISO-8859-1", "COD000001V0001"), 0, 'C', false);

        // Guardar o mostrar PDF
        $file_path = __DIR__ . '/reporte_facturas.pdf';
        $this->Output('F', $file_path);

        echo "PDF guardado en: " . $file_path;
    }

    function SetWidths($w)
    {
        // Set the array of column widths
        $this->widths = $w;
    }

    function SetAligns($a)
    {
        // Set the array of column alignments
        $this->aligns = $a;
    }

    function Row($data, $keysColumns)
    {

        // Calculate the height of the row
        $nb = 0;
        for($i=0;$i<count($data);$i++){
            foreach($keysColumns as $field){
                $nb = max($nb,$this->NbLines($this->widths[$i],$data[$field]));
            }
        }
           
        $h = 2*$nb;
        // Issue a page break first if needed
        $this->CheckPageBreak($h);
        // Draw the cells of the row
        for($i=0;$i<count($data);$i++)
        {
             
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'C';
            // Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            // Draw the border
            $this->Rect($x,$y,$w,$h);
            // Print the text
            $this->MultiCell($w,5,$data[$keysColumns[$i]],0,$a);
            // Put the position to the right of the cell
            $this->SetXY($x+$w,$y);
        }
        // Go to the next line
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        // If the height h would cause an overflow, add a new page immediately
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt)
    {
        // Compute the number of lines a MultiCell of width w will take
        if(!isset($this->CurrentFont))
            $this->Error('No font has been set');
        $cw = $this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r",'',(string)$txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i<$nb)
        {
            $c = $s[$i];
            if($c=="\n")
            {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep = $i;
            $l += $cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i = $sep+1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }
} 



?>