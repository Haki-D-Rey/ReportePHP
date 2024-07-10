<?php

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class MinIOManager
{
    private $s3Client;
    private $bucket;

    public function __construct($endpoint, $accessKey, $secretKey, $bucket)
    {
        $this->s3Client = new S3Client([
            'version'     => 'latest',
            'region'      => 'us-east-1',
            'endpoint'    => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey
            ],
        ]);

        $this->bucket = $bucket;
    }

    public function listFiles($prefix = '')
    {
        try {
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            if (!empty($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    echo $object['Key'] . PHP_EOL;
                }
            } else {
                echo "No hay archivos en la carpeta especificada." . PHP_EOL;
            }
        } catch (AwsException $e) {
            echo "Error al listar archivos: " . $e->getMessage() . PHP_EOL;
        }
    }

    public function uploadFile($key, $sourceFilePath)
    {
        if (!file_exists($sourceFilePath) || !is_readable($sourceFilePath)) {
            die("El archivo local no existe o no es legible.");
        }

        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'SourceFile' => $sourceFilePath,
            ]);

            echo "Archivo subido exitosamente. URL: " . $result['ObjectURL'] . PHP_EOL;
        } catch (AwsException $e) {
            echo "Error al subir el archivo: " . $e->getMessage() . PHP_EOL;
        }
    }

    public function downloadFile($key, $destinationFilePath)
    {
        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);

            file_put_contents($destinationFilePath, $result['Body']);

            if (filesize($destinationFilePath) === $result['ContentLength']) {
                echo "Archivo descargado exitosamente." . PHP_EOL;
            } else {
                echo "El archivo descargado podría estar corrupto." . PHP_EOL;
            }
        } catch (AwsException $e) {
            echo "Error al descargar el archivo: " . $e->getMessage() . PHP_EOL;
        }
    }


    public function fileExists($key)
    {
        try {
            $result = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);
            return true; // El archivo existe
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() === 'NoSuchKey') {
                return false; // El archivo no existe
            } else {
                echo "Error al verificar la existencia del archivo: " . $e->getMessage() . PHP_EOL;
                return false;
            }
        }
    }

    public function uploadFileIfNotExists($key, $sourceFilePath)
    {
        if ($this->fileExists($key)) {
            echo "El archivo '$key' ya existe en el bucket." . PHP_EOL;
            return;
        }

        if (!file_exists($sourceFilePath) || !is_readable($sourceFilePath)) {
            die("El archivo local no existe o no es legible.");
        }

        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'SourceFile' => $sourceFilePath,
            ]);

            echo "Archivo subido exitosamente. URL: " . $result['ObjectURL'] . PHP_EOL;
        } catch (AwsException $e) {
            echo "Error al subir el archivo: " . $e->getMessage() . PHP_EOL;
        }
    }
}

// Uso de la clase MinIOManager
$minioManager = new MinIOManager(
    'http://10.0.30.177:9000',
    'FYb1TybN4DdIBENRt2v0',
    'OLBsjexxSskVzhNe6qnMgfFWCmTvwpsY4O70tZHe',
    'eventos-internaciones'
);

// Listar archivos en la carpeta 'reportes/'
$list_reportes = $minioManager->listFiles('reportes-facturas/');

// Subir un archivo
$minioManager->uploadFile('reportes/reporte02.pdf', './.pdf');

// // Descargar un archivo
// $minioManager->downloadFile('reportes/reporte02.pdf', './descargado_reporte_factura_1030.pdf');
