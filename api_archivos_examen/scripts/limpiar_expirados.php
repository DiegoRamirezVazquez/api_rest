<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Archivo.php';

$database = new Database();
$db = $database->getConnection();

$archivoModel = new Archivo($db);

$archivosExpirados = $archivoModel->obtenerExpirados();

if (empty($archivosExpirados)) {
    echo "No hay archivos expirados para eliminar.\n";
    exit;
}

$eliminados = 0;
$errores = 0;

$directorio = dirname(__DIR__) . '/storage/files';

foreach ($archivosExpirados as $archivo) {

    $ruta = $directorio . '/' . $archivo['nombre_interno'];

    try {

        // Eliminar archivo físico
        if (file_exists($ruta)) {

            if (!unlink($ruta)) {
                throw new Exception(
                    "No se pudo eliminar el archivo físico"
                );
            }
        }

        // Eliminar registro de la base de datos
        if (!$archivoModel->eliminar($archivo['identificador'])) {
            throw new Exception(
                "No se pudo eliminar el registro de la base de datos"
            );
        }

        $eliminados++;

        echo "Eliminado: " .
             $archivo['nombre_original'] .
             " (" .
             $archivo['identificador'] .
             ")\n";

    } catch (Exception $e) {

        $errores++;

        echo "Error al eliminar " .
             $archivo['identificador'] .
             ": " .
             $e->getMessage() .
             "\n";
    }
}

echo "\n";
echo "Proceso terminado.\n";
echo "Archivos eliminados: " . $eliminados . "\n";
echo "Errores: " . $errores . "\n";
?>