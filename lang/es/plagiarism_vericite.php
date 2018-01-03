<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package   plagiarism_vericite
 * @copyright 2015 VeriCite, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'VeriCite';
$string['studentdisclosuredefault']  = 'Las presentaciones de texto y archivos se subirán a un servicio de detección de plagio para que el instructor las revise.';
$string['studentdisclosure'] = 'Descargo de responsabilidad del alumno';
$string['studentdisclosure_help'] = '';
$string['vericiteexplain'] = 'VeriCite es un servicio basado en la nube que detecta el plagio mediante la comparación de los trabajos presentados con una base de datos cada vez más extensa que contiene fuentes. VeriCite es un servicio comercial que requiere una suscripción válida. Hay versiones de prueba de 60 días disponibles en <a href="https://www.vericite.com" target="_blank">www.VeriCite.com</a>.<br/><br/>VeriCite es monitoreado constantemente y <a href="http://status.vericite.com/" target="_blank"">hay actualizaciones de estado disponibles</a>.<br/><br/>Las novedades y actualizaciones de sistema están disponibles en <a href="https://updates.vericite.com" target="_blank"/>updates.vericite.com</a>.<br/><hr/>';
$string['vericite'] = 'Complemento para plagio de VeriCite';
$string['usevericite'] = 'Activar VeriCite';
$string['savedconfigsuccess'] = 'Configuraciones de plagio guardadas';
$string['vericiteaccountid'] = 'Identificación de la cuenta';
$string['vericiteaccountid_help'] = 'La identificación proporcionada como parte del contrato de prueba con VeriCite';
$string['vericitesecretkey'] = 'Clave secreta';
$string['vericitesecretkey_help'] = 'El secreto proporcionado como parte del contrato de prueba con VeriCite';
$string['vericiteapi'] = 'URL de la API';
$string['vericiteapi_help'] = 'La URL de la API proporcionada como parte del contrato de prueba con VeriCite';
$string['similarity'] = 'Similitud';
$string['vericitedefaultsettings'] = 'Configuraciones predeterminadas para las tareas nuevas:';
$string['vericitedefaultsettingsforums'] = 'Configuraciones predeterminadas para los foros nuevos:';
$string['usevericite_help'] = 'Activar si desea que las tareas nuevas tengan VeriCite activado de manera predeterminada.';
$string['usevericite'] = 'Activar el servicio de plagio de VeriCite';
$string['studentscorevericite'] = 'Permitir que los alumnos vean los puntajes';
$string['studentscorevericite_help'] = 'Activar para permitir que los alumnos vean su puntaje de similitud de VeriCite. Los puntajes de similitud van de 0 a 100 y representan la cantidad de contenido que coincide con los trabajos de otros alumnos o contenido web.';
$string['studentreportvericite'] = 'Permitir que los alumnos vean los informes';
$string['studentreportvericite_help'] = 'Activar para permitir que los alumnos vean el informe de VeriCite completo, lo que incluye contexto sobre las coincidencias encontradas.';
$string['preliminaryreportvericite'] = 'Mostrar el puntaje de similitud preliminar';
$string['preliminaryreportvericite_help'] = 'Activar para mostrar el puntaje de similitud preliminar (no definitivo). Si no se activa, el puntaje no se mostrará hasta que el puntaje final se haya determinado, lo que puede tomar varias horas.';
$string['advanced_settings'] = 'Configuraciones avanzadas';
$string['disable_dynamic_inline'] = 'Desactivar las presentaciones dinámicas en línea';
$string['disable_dynamic_inline_help'] = 'Desactivar las presentaciones dinámicas en línea dará como resultado presentaciones de única vez solamente. Las modificaciones al texto en línea del alumno no serán presentadas nuevamente a VeriCite.';
$string['enable_debugging'] = 'Activar limpieza';
$string['enable_debugging_help'] = 'Activar limpieza del módulo VeriCite. Los errores se imprimirán en un archivo de registro vericite.log en el directorio donde se encuentran los datos de Moodle.';
$string['excludequotesvericite'] = 'Excluir citas';
$string['excludequotesvericite_help'] = 'Establecer la opción predeterminada para todos los informes presentados para esta tarea. Para reducir la cantidad de coincidencias falsas, recomendamos excluir las citas predeterminadas. Los instructores seguirán teniendo la capacidad de cambiar esta opción para cada informe individual después de la presentación.';
$string['excludequotesvericite_hideinstructor'] = 'Ocultar la configuración "Excluir citas" del instructor.';
$string['excludequotesvericite_hideinstructor_help'] = 'Bloquear las configuraciones de Excluir citas, de manera que al crear una nueva tarea el instructor no pueda ver o cambiar la configuración.';
$string['excludeselfplagvericite'] = 'Excluir autoplagio';
$string['excludeselfplagvericite_help'] = 'Establecer la opción predeterminada para todos los informes presentados para esta tarea. Para reducir la cantidad de coincidencias falsas, recomendamos excluir el autoplagio predeterminado en el mismo curso. Los instructores seguirán teniendo la capacidad de cambiar esta opción para cada informe individual después de la presentación. Siempre se comprobará el autoplagio contra los informes del usuario en otros cursos.';
$string['excludeselfplagvericite_hideinstructor'] = 'Ocultar la configuración "Excluir autoplagio" del instructor';
$string['excludeselfplagvericite_hideinstructor_help'] = 'Bloquear la configuración de Excluir autoplagio, de manera que al crear una nueva tarea el instructor no pueda ver o cambiar la configuración.';
$string['storeinstindexvericite'] = 'Guardar en el índice institucional';
$string['storeinstindexvericite_help'] = 'Establecer la opción predeterminada para todos los informes presentados para esta tarea. Si elige no almacenar los informes en su índice institucional, los informes no se utilizarán para comprobar plagio contra otros informes del alumno en su institución. Una vez que se presenta un informe, no se puede cambiar esta opción para ese informe.';
$string['storeinstindexvericite_hideinstructor'] = 'Ocultar configuración "Guardar en el índice institucional" del instructor';
$string['storeinstindexvericite_hideinstructor_help'] = 'Bloquear la configuración de Guardar en el índice institucional, de manera que al crear una nueva tarea el instructor no pueda ver o cambiar la configuración.';
$string['enablestupreviewvericite'] = 'Habilitar vista previa del alumno';
$string['enablestupreviewvericite_help'] = 'Establecer la opción predeterminada para todos los informes presentados para esta tarea. Si elige habilitar la vista previa del alumno, los usuarios podrán revisar y volver a presentar sus documentos hasta la fecha límite. Los instructores solo podrán ver la presentación final, pero se mostrará un registro de los puntajes para cada documento en los registros de informe. Si la tarea tiene una fecha límite, se presentará automáticamente el borrador más reciente del alumno si este no tiene presentaciones.';
$string['enablestupreviewvericite_hideinstructor'] = 'Ocultar la configuración "Activar la vista previa del alumno" del instructor';
$string['enablestupreviewvericite_hideinstructor_help'] = 'Bloquear la configuración de "Activar la vista previa del alumno", de manera que al crear una nueva tarea el instructor no pueda ver o cambiar la configuración.';
$string['sendfiles'] = 'Cron Job de VeriCite para presentar archivos';
