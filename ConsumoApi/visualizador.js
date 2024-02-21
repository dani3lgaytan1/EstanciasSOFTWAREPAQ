const archivoInput = document.getElementById('archivoXML');
const cargarBoton = document.getElementById('cargarBoton');
const limpiarBoton = document.getElementById('limpiarBoton');
//const API_URL = "https://test.softwarepaq1.com/apiPDF/apiPDF.php";
const API_URL = "http://localhost/estancias/apiPDF/apiPDF/apiPDF.php";


// Obtener los elementos de tipo color
var colorPickerCell = document.getElementById('colorpickerLine');
var colorPickerBCell = document.getElementById('colorpickerBCell');
var colorPickerTextCell = document.getElementById('colorpickerTextCell');
let SizeLine = document.getElementById('SizeLine');
let SizeLineCell = document.getElementById('SizeLineCell');
let imagenBase64=''; 
var pdfViewer = document.getElementById('pdf-viewer');
var  imagenSeleccionada = 'no';
var imagenPrevisualizada= document.getElementById('imagenPrevisualizada');
const loader = document.getElementById("loader");
// Obtener referencia al elemento input de tipo file donde el usuario seleccionará la imagen
let inputImagen = document.getElementById('inputImagen');

// Cuando el iframe se ha cargado completamente, oculta la animación de carga
loader.style.display = "none";

const valorOriginalColor = "#D4533C"; // valor por default

// Agregar un evento para escuchar cuando el usuario selecciona una imagen
inputImagen.addEventListener('change', function () {
    // Verificar si se seleccionó una imagen
    if (inputImagen.files && inputImagen.files[0]) {
        imagenSeleccionada = inputImagen.files[0];
            var lector = new FileReader();

            // Cuando se cargue la imagen, mostrarla en la imagen de previsualización
            lector.onload = function (e) {
                imagenPrevisualizada.src = e.target.result;
                imagenPrevisualizada.style.display = 'block'; // Mostrar la imagen
            };

            // Leer el archivo de imagen como una URL de datos (data URL)
            lector.readAsDataURL(imagenSeleccionada);
        }

        const lectorImagen = new FileReader();
        // Configurar la acción a realizar cuando se complete la lectura
        lectorImagen.onload = function (evento) {
            // El resultado contiene la imagen en base64
             imagenBase64 = evento.target.result;
            // console.log(imagenBase64);
        };

            lectorImagen.readAsDataURL(imagenSeleccionada);

        

    
});

cargarBoton.addEventListener('click', function () {
    const archivoSeleccionado = archivoInput.files[0];
    if (archivoSeleccionado) {
        const nombreArchivo = archivoSeleccionado.name;
        // Verificar que el archivo sea un XML válido
        if (nombreArchivo.endsWith('.xml')) {
             // Muestra la animación de carga
            loader.style.display = "block";
            pdfViewer.style.display = "none"; // Oculta el iframe mientras se carga
            const lector = new FileReader();
            // Obtener el valor hexadecimal del color
            let valorHexadecimal = colorPickerCell.value;
            // Convertir el valor hexadecimal a formato RGB
            let ColorLine = hexToRGB(valorHexadecimal);
            // Obtener el valor hexadecimal del color
            let valorHexadecimal2 = colorPickerBCell.value;
            // Convertir el valor hexadecimal a formato RGB
            let BackgroundCell = hexToRGB(valorHexadecimal2);

            // Obtener el valor hexadecimal del color
            let valorHexadecimal3 = colorPickerTextCell.value;
            // Convertir el valor hexadecimal a formato RGB
            let TextCell = hexToRGB(valorHexadecimal3);

            // Leer el archivo como texto
            lector.readAsText(archivoSeleccionado);

            lector.onload = function (e) {
                // Contenido del archivo en base64
                let contenidoBase64 = btoa(lector.result);
                ///       console.log('Contenido del archivo XML en base64:', contenidoBase64);
                var DataJson = {
                    xml64: contenidoBase64,
                    logo64: imagenBase64,
                    colorLine: ColorLine,
                    sizeLine: SizeLine.value,
                    backgroundCell:BackgroundCell,
                    sizeLineCell: SizeLineCell.value,
                    colorTextCell:TextCell
                };
                // Crear una instancia de XMLHttpRequest
                const xhr = new XMLHttpRequest();
                // Configurar la solicitud
                xhr.open('POST', API_URL, true);

                // Manejar la respuesta de la solicitud
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        //console.log(xhr.responseText);
                        try {
                            var responseData = JSON.parse(xhr.responseText);
                            
                            var b64 = responseData.pdf64;
                            pdfViewer.src = 'data:application/pdf;base64,' + b64;
                        } catch (error) {
                            console.error('Error al analizar la respuesta JSON:', error);
                        }
                    } else {
                        // La solicitud falló
                        console.error('Error en la solicitud:', xhr.statusText);
                    }
                };
                // Enviar el Json
                xhr.send(JSON.stringify(DataJson));
            };
        } else {
            alert('El archivo no tiene la extensión .xml.');
        }
    } else {
        alert('Selecione un archivo.');
    }
});


pdfViewer.onload = function() {
    // Cuando el iframe se ha cargado completamente, oculta la animación de carga
    loader.style.display = "none";
    pdfViewer.style.display = "block";

};


limpiarBoton.addEventListener('click',function(){

    archivoInput.value = null; // Esto restablece el valor del input y elimina el archivo seleccionado
    //limpiar el visor de pdf : 
    pdfViewer.src = "";
    //limpiar la imagen
    inputImagen.value = ''; 
    imagenBase64 = "";
    SizeLine.value = '0.5';
    SizeLineCell.value ='0.2';

    //limpiar la previsualizacopn de la imagen :
    imagenPrevisualizada.src = ''; // Eliminar la imagen de previsualización
    imagenPrevisualizada.style.display = 'none'; // Ocultar la imagen
    colorPickerBCell.value = 0.3;

    colorPickerBCell.value = "#D4533C"; // valor por defecto
    colorPickerCell.value =   "#D4533C";// valor por defecto
    colorPickerTextCell.value = "#FFFFFF";
});


// Función para convertir un valor hexadecimal a formato RGB
function hexToRGB(hex) {
    // Eliminar el '#' si está presente
    hex = hex.replace('#', '');
    // Obtener los componentes R, G y B
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    // Retornar el valor RGB en formato "rgb(r, g, b)"
    return `${r},${g},${b}`;
}





