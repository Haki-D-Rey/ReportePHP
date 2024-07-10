<?php

class ConexionBD
{
    private $host = 'c98055.sgvps.net'; // Cambiar por tu host
    private $usuario = 'udeq5kxktab81'; // Cambiar por tu usuario
    private $password = 'clmjsfgcrt5m'; // Cambiar por tu contraseña
    private $nombre_bd = 'db2gdg4nfxpgyk'; // Cambiar por tu base de datos
    private $conexion;

    public function __construct()
    {
        try {
            $this->conexion = new mysqli($this->host, $this->usuario, $this->password, $this->nombre_bd);
            if ($this->conexion->connect_error) {
                throw new Exception("Error de conexión a la base de datos: " . $this->conexion->connect_error);
            }

            $this->conexion->set_charset("utf8");
        } catch (Exception $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function getConexion()
    {
        return $this->conexion;
    }

    public function cerrarConexion()
    {
        if ($this->conexion) {
            $this->conexion->close();
        }
    }

    public function __destruct()
    {
        $this->cerrarConexion();
    }
}
