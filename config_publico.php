<?php
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$protocol = $isSecure ? "https" : "http";
define("WEB_ROOT", "/web");
//hay que adaptar esta parte segun el arlbol de carpetas donde se suba el proyecto
//define("RUTA_RAIZ" , "latu/practia/web/index.php/");
define("RUTA_RAIZ" , "latu/sigla/2.0/tecnologia_gestion/sello_sostenible/web/principal.php/");

$localConfig = array(
    'base_url' => "$protocol://$_SERVER[HTTP_HOST]".WEB_ROOT,
    'db' => 'MYSQL',
    'urlRestart' => '/web/carga-matriz',
    'urlLogout' => 'logout',
    'inicio' => '/web'
);

// Configuración para el servidor remoto
$remoteConfig = array(
    'base_url' => "$protocol://$_SERVER[HTTP_HOST]/latu/sigla/2.0/tecnologia_gestion/sello_sostenible".WEB_ROOT,
    'db' => 'SQLSERVER',
    'urlRestart' => "$protocol://$_SERVER[HTTP_HOST]/latu/sigla/2.0/tecnologia_gestion/sello_sostenible/web/principal.php/carga-matriz",
    'urlLogout' => "$protocol://$_SERVER[HTTP_HOST]/latu/sigla/2.0/tecnologia_gestion/sello_sostenible/web/principal.php/logout/",
    'inicio' => "$protocol://$_SERVER[HTTP_HOST]/latu/sigla/2.0/tecnologia_gestion/sello_sostenible/web/principal.php/"
);


// Seleccionar la configuración adecuada según el entorno
$currentConfig = (isLocal()) ? $localConfig : $remoteConfig;

function isLocal() {
    // Lógica para determinar si el servidor es local o remoto
    return ($_SERVER['HTTP_HOST'] === 'localhost');
}

if(!isLocal()){
    $ruta_func_generales = "/var/www/html/latu/conf/";
    include_once($ruta_func_generales."db_sas_session.php"); //incluye la obligatoriedad de login para usar el programa
    include_once($ruta_func_generales."db_principal.php"); // SQL SERVER $dbSql
    $nro_cliente = getNroClienteLogueado();
}else{
    include_once("db/DB.class.php");
    //en local es mysql por practicidad
    $dbSql = new MySQLDB("localhost","LATU_USUARIO","root","");
    $nro_cliente = 210276180011; //Conaprole  
}

//descomentar esto en el sv latu
header('Content-Type: text/html; charset=ISO-8859-1');



define("PATH", $currentConfig["base_url"]);
define("ESTILOS_CSS", PATH."/public/css/estilos.css");
define("IMAGES", PATH."/public/images");
define("ESTILOS_SLICK", PATH."/public/css/slick.css");
define("ESTILOS_SLICK_THEME", PATH."/public/css/slick-theme.css");
define("JQUERY", PATH."/public/libs/jQuery-min.JS");
define("SLICK", PATH."/public/libs/slick.JS");
define("SWEET_ALERT", PATH."/public/libs/sweet-alert.js");
define("PAKO", PATH."/public/libs/pako.min.js");
define("FUNCIONES_JS", PATH."/public/js/funciones.js");
define("HOME_JS", PATH."/public/js/home.js");
define("MATRIZ_JS", PATH."/public/js/matriz.js");
define("CARROUSEL_JS", PATH."/public/js/carrousel.js");
define("VIEWS", "view");
define("MODEL", "model");
define("CONTROLLER", "controller");
define("UTIL", "util");
define("SRC", "src");

define("PATH_FILES", "files");
define("PATH_REPORTS", __DIR__. "/../reports/");


include_once(CONTROLLER."/HomeController.class.php");
include_once(CONTROLLER."/MatrizController.class.php");
include_once(CONTROLLER."/UsuarioController.class.php");
include_once(MODEL."/MatrizModel.class.php");
include_once(MODEL."/UsuarioModel.class.php");
include_once(UTIL."/Util.class.php");
include_once(SRC."/tcpdf/tcpdf.php");
