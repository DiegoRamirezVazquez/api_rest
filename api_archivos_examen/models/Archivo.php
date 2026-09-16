<?php

class Archivo
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Crear registro del archivo
    public function crear(
        $identificador,
        $nombre_original,
        $nombre_interno,
        $tamano,
        $mime_type,
        $fecha_expiracion,
        $max_downloads,
        $token_admin_hash
    ) {
        $sql = "INSERT INTO archivos
                (
                    identificador,
                    nombre_original,
                    nombre_interno,
                    tamano,
                    mime_type,
                    fecha_expiracion,
                    max_downloads,
                    token_admin_hash
                )
                VALUES
                (
                    :identificador,
                    :nombre_original,
                    :nombre_interno,
                    :tamano,
                    :mime_type,
                    :fecha_expiracion,
                    :max_downloads,
                    :token_admin_hash
                )";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'identificador' => $identificador,
            'nombre_original' => $nombre_original,
            'nombre_interno' => $nombre_interno,
            'tamano' => $tamano,
            'mime_type' => $mime_type,
            'fecha_expiracion' => $fecha_expiracion,
            'max_downloads' => $max_downloads,
            'token_admin_hash' => $token_admin_hash
        ]);
    }

    // Obtener un archivo por su identificador
    public function obtenerPorIdentificador($identificador)
    {
        $sql = "SELECT *
                FROM archivos
                WHERE identificador = :identificador";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'identificador' => $identificador
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Incrementar el contador de descargas de forma segura
    public function incrementarDescargas($identificador)
    {
        $sql = "UPDATE archivos
                SET descargas_realizadas = descargas_realizadas + 1
                WHERE identificador = :identificador
                AND descargas_realizadas < max_downloads";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'identificador' => $identificador
        ]);

        return $stmt->rowCount();
    }

    // Eliminar el registro del archivo
    public function eliminar($identificador)
    {
        $sql = "DELETE FROM archivos
                WHERE identificador = :identificador";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'identificador' => $identificador
        ]);
    }

    // Obtener archivos expirados
    public function obtenerExpirados()
    {
        $sql = "SELECT *
                FROM archivos
                WHERE fecha_expiracion <= NOW()";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function limpiarExpirados()
    {
        $archivosExpirados = $this->obtenerExpirados();

        $directorio = dirname(__DIR__) . '/storage/files';

        foreach ($archivosExpirados as $archivo) {

            $ruta = $directorio . '/' .
                    $archivo['nombre_interno'];

            // Eliminar archivo físico
            if (file_exists($ruta)) {
                unlink($ruta);
            }

            // Eliminar registro de la base de datos
            $this->eliminar(
                $archivo['identificador']
            );
        }
    }

    public function verificarRateLimit($ip, $limite = 20)
    {
        // Buscar el registro de la IP
        $sql = "SELECT solicitudes, ventana_inicio
                FROM rate_limits
                WHERE ip = :ip";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'ip' => $ip
        ]);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        // Primera solicitud de esta IP
        if (!$registro) {

            $sql = "INSERT INTO rate_limits
                    (ip, ventana_inicio, solicitudes)
                    VALUES
                    (:ip, NOW(), 1)";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'ip' => $ip
            ]);

            return true;
        }

        // Verificar si ya pasó la ventana de 1 minuto
        $sql = "SELECT TIMESTAMPDIFF(
                    SECOND,
                    ventana_inicio,
                    NOW()
                ) AS segundos
                FROM rate_limits
                WHERE ip = :ip";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'ip' => $ip
        ]);

        $tiempo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Reiniciar ventana después de 60 segundos
        if ($tiempo['segundos'] >= 60) {

            $sql = "UPDATE rate_limits
                    SET ventana_inicio = NOW(),
                        solicitudes = 1
                    WHERE ip = :ip";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'ip' => $ip
            ]);

            return true;
        }

        // Verificar límite
        if ($registro['solicitudes'] >= $limite) {
            return false;
        }

        // Aumentar contador
        $sql = "UPDATE rate_limits
                SET solicitudes = solicitudes + 1
                WHERE ip = :ip";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'ip' => $ip
        ]);

        return true;
    }
}
?>