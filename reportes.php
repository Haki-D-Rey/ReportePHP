<?php

require_once 'generar_facturas.php';

try {
    $generador = new GeneradorFacturas();
    $generador->generarFacturas();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}

?>