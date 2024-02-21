
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require('lib/BASICPDF.php');
require('lib/BASICPDF_RET.php');
require('lib/FACTURA40/FACTURA40.php');
require('lib/PHPUTIL.php');
require('lib/RETENCIONES20/RETENCIONES20.php');
header('Access-Control-Allow-Origin: *');

?>
<?php

$dirname = dirname(__DIR__);
$path_logo = "logos\logo-horizontal.png";
//verifica si hay una solicitud json con post :

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer los datos JSON del cuerpo de la solicitud
    $datosJSON = file_get_contents('php://input');

    // Decodificar los datos JSON en un objeto PHP
    $datos = json_decode($datosJSON);

    // Verificar si la decodificación fue exitosa
    if ($datos !== null) {
        $xml64 = $datos->xml64;

        if (isset($datos->logo64)) {
            $imagen64 = $datos->logo64;
        } else {
            $logoPath = "";
        }

        $colorLine = $datos->colorLine;
        $sizeLine = $datos->sizeLine;
        $backgroundCell = $datos->backgroundCell;
        $sizeLineCell = $datos->sizeLineCell;
        $colorTextCell = $datos->colorTextCell;

        if ($imagen64 != null) { //si existe la imagen en base 64

            $extension = explode('/', substr($imagen64, 5, strpos($imagen64, ';') - 5));
            if (count($extension) > 1) {
                $extension = $extension[1]; // Obtener la extensión de la imagen
            }
            $data = explode(',', $imagen64); //obtener solamente la cadena en base 64
            if (count($data) > 1) {
                $imagenDecodificada = base64_decode($data[1]); //decodificar
            } else {
                $imagenDecodificada = base64_decode($imagen64);
            }
            @$imagen1 = imagecreatefromstring($imagenDecodificada);
            if ($imagen1 == false) {
                $logoPath = '';
            } else {

                $nombreArchivo = "logos/mi_imagen." . $extension; // Nombre del archivo con la extensión correcta
                $archivo = fopen($nombreArchivo, 'wb'); // 'wb' para escribir en modo binario
                if (fwrite($archivo, $imagenDecodificada) !== false) {
                    fclose($archivo);
                    $logoPath = $nombreArchivo; //logo listo para usarse

                } else {
                    $logoPath = ''; //no se pudo crear el archivo
                }
            }
        } else {

            $logoPath = ''; //no hay imagen
        }
        if ($xml64 != null) {
            //echo "   XML en 64 recibido \n";
            $xmlContent = base64_decode($xml64);

            $content = iconv('UTF-8', 'UTF-8//IGNORE', $xmlContent);

            @$xml = simplexml_load_string($content);
        } else {
            respuestaJson('3', 'Dato correspondiente al XML nulo', '');
        }

        if ($colorLine == null) {
            $colorLine = '212,83,60';
        }
        if ($sizeLine == null) {
            $sizeLine =  '0.1';
        }
        if ($backgroundCell == null) {
            $backgroundCell =  '212,83,60';
        }
        if ($sizeLineCell == null) {
            $sizeLineCell =  '0.1';
        }
        if ($colorTextCell == null) {
            $colorTextCell =  '255,255,255';
        }
    } else {
        respuestaJson('2', 'Datos recibidos del JSON nulos', '');
    }
} else {
    respuestaJson('1', 'No hay un JSON', '');
}

if (isset($xml)) {

    $util = new PHPUTIL();
    //iniciando con factura40 : 
    try {
        $factura40 = FACTURA40::crearFactura($xml);

        //iniciando con el tipo de factura
        $pdf   = new BASICPDF('P', 'mm', 'Letter');
        $pdf->setUtil($util);
        $pdf->SetFont('arial', '', 8);

        $pdf->setPathLogo($logoPath);
        $pdf->setConfigPDF($sizeLineCell, $sizeLine, $colorTextCell, $backgroundCell, $colorLine);

        $pdf->setFactura40($factura40);

        $pdf->AliasNbPages();
        $pdf->paintCFDI();

        $pdfString = $pdf->Output('S'); //guardar el pdf en una varible string: 
        if (is_string($pdfString) && $pdfString != null) {
            $pdfBase64 = base64_encode($pdfString);
            respuestaJson('', '', $pdfBase64);
            //$pdf->output('I');
            borrarArchivos();
        } else {
            respuestaJson('7', 'Error al crear el pdf en base64', '');
        }
    } catch (Exception $e) {
        // Manejar la excepción si el XML no es válid

        try {

            $retenciones20 = RETENCIONES20::crearRetencion($xml); //es una factura de retenciones: 
            //iniciando con el tipo de factura
            $pdfR   = new BASICPDF_RET('P', 'mm', 'Letter');
            $pdfR->setUtil($util);
            $pdfR->SetFont('arial', '', 8);

            $pdfR->setPathLogo($logoPath);
            $pdfR->setConfigPDF($sizeLineCell, $sizeLine, $colorTextCell, $backgroundCell, $colorLine);

            $pdfR->setRetenciones20($retenciones20);

            $pdfR->AliasNbPages();
            $pdfR->paintRETENCIONES();

            $pdfString = $pdfR->Output('S'); //guardar el pdf en una varible string: 
            if (is_string($pdfString) && $pdfString != null) {
               $pdfBase64 = base64_encode($pdfString);
                respuestaJson('', '', $pdfBase64);
               //$pdfR->output('I');
                borrarArchivos();
            } else {
                respuestaJson('7', 'Error al crear el pdf en base64', '');
            }
        } catch (Exception $a) {
            respuestaJson('6', 'El XML no pertenece a factura, no es valido', '');
        }
    }
} else {
    respuestaJson('5', 'Error en BASICPDF O FACTURA40', '');
}

function respuestaJson($error, $descripcion, $pdf)
{
    $respuesta = array(
        "Error" => $error,
        "DescripError" => $descripcion,
        "pdf64" => $pdf
    );

    echo json_encode($respuesta);
}


function imagen_esCorrupta($imagenD)
{

    // Intenta crear una imagen a partir de los bytes decodificados.
    try {
        $imagen = imagecreatefromstring($imagenD);

        // Si se pudo crear la imagen, entonces no está corrupta.
        if ($imagen) {
            return false;
        }
    } catch (Exception $e) {
        // Si no se pudo crear la imagen, entonces está corrupta.
        return true;
    }

    return true;
}


function borrarArchivos()
{
    //Eliminar los qrchivos qr
    $files = glob('lib/qr/*.png'); //obtenemos todos los nombres de los ficheros
    foreach ($files as $file) {
        if (is_file($file))
            unlink($file); //elimino el fichero
    }
    //eliminar los archivos txt de qr
    $files = glob('lib/phpqrcode/*.txt'); //obtenemos todos los nombres de los ficheros
    foreach ($files as $file) {
        if (is_file($file))
            unlink($file); //elimino el fichero
    }

    //eliminar las imagenes creadas

    $files = glob('logos/*'); //obtenemos todos los nombres de los ficheros
    foreach ($files as $file) {
        if (is_file($file))
            unlink($file); //elimino el fichero
    }
}

?>
