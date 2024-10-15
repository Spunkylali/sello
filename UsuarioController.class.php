<?php
class UsuarioController{
    public static function login(){

        $telefonoContacto   = isset($_REQUEST["telefonoContacto"]) ? $_REQUEST["telefonoContacto"] : null;
        $personasResponsables = isset($_REQUEST["personasResponsables"]) ? $_REQUEST["personasResponsables"] : null;
        $emailContacto = isset($_REQUEST["emailContacto"]) ? $_REQUEST["emailContacto"] : null;
        $personasResponsables = utf8_encode($personasResponsables);

        $ip = $_SERVER['REMOTE_ADDR'];
        $salt = Util::generateRandomString();
        $hash = hash('sha256', $salt . $ip . time());
        $tiempoExpiracion = time() + (86400 * 7); // 86400 segundos = 1 día


        $objUsuario = array(
            "telefonoContacto"     => $telefonoContacto,
            "emailContacto"        => $emailContacto,
            "personasResponsables" => $personasResponsables,
            "sesion"               => $hash
        );

        
        Util::generarCookie("usuario", $objUsuario, $tiempoExpiracion);
        
        header("Location: carga-matriz");

        exit(); 
    }
    public static function logout(){
        global $currentConfig;
        Util::eliminarCookie("paso_1");
        Util::eliminarCookie("paso_2");
        Util::eliminarCookie("paso_3");
        Util::eliminarCookie("paso_4");
        Util::eliminarCookie("usuario");
        Util::redirigir($currentConfig["inicio"]);
    }
    public static function userExist(){
        $usuario = Util::obtenerValorCookie("usuario");
        if(is_null($usuario)){
            return false;
        }

        return true;
    }
    public static function getSession(){
        $usuario = Util::obtenerValorCookie("usuario");
        $session = null;
        if(!is_null($usuario)){ 
            $session = isset($usuario["sesion"]) ? $usuario["sesion"] : null;
        }

        return $session;
    }
    public static function getUserEmail(){
        $usuario = Util::obtenerValorCookie("usuario");
        $emailContacto = null;
        if(!is_null($usuario)){ 
            $emailContacto = isset($usuario["emailContacto"]) ? $usuario["emailContacto"] : null;
        }

        return $emailContacto;
    }
    public static function getUserPhone(){
        $usuario = Util::obtenerValorCookie("usuario");
        $telefonoContacto = null;
        if(!is_null($usuario)){ 
            $telefonoContacto = isset($usuario["telefonoContacto"]) ? $usuario["telefonoContacto"] : null;
        }

        return $telefonoContacto;
    }
    public static function getResponsibles(){
        $usuario = Util::obtenerValorCookie("usuario");
        $personasResponsables = null;
        if(!is_null($usuario)){ 
            $personasResponsables = isset($usuario["personasResponsables"]) ? $usuario["personasResponsables"] : null;
        }

        return $personasResponsables;
    }

    public static function changeSession(){

        $usuario = Util::obtenerValorCookie("usuario");
        $telefonoContacto = isset($usuario["telefonoContacto"]) ? $usuario["telefonoContacto"] : null;
        $emailContacto = isset($usuario["emailContacto"]) ? $usuario["emailContacto"] : null;
        $personasResponsables = isset($usuario["personasResponsables"]) ? $usuario["personasResponsables"] : null;

        $ip = $_SERVER['REMOTE_ADDR'];
        $salt = Util::generateRandomString();
        $hash = hash('sha256', $salt . $ip . time());
        $tiempoExpiracion = time() + (86400 * 7); // 86400 segundos = 1 día
       // $hash = "a0299444ffb4f14601ee07ae617bec6c2f9ba9c086c5cbc25039cc4fe4722dba";
        $objUsuario = array(
            "telefonoContacto"   => $telefonoContacto,
            "emailContacto"        => $emailContacto,
            "personasResponsables" => $personasResponsables,
            "sesion"               => $hash
        );

        
        Util::generarCookie("usuario", $objUsuario, $tiempoExpiracion);


    }

    public static function deleteSteps(){
        Util::eliminarCookie("paso_1");
        Util::eliminarCookie("paso_2");
        Util::eliminarCookie("paso_3");
    }

    public static function saveStepInDB($step){

        $datos = Util::obtenerValorCookiePasos("paso_$step");
        UsuarioModel::saveStepInBd($step, $datos);
    }

    public static function getStepsByDb(){

        $resultados = UsuarioModel::getStepsByDb();
        self::deleteSteps();
        foreach ($resultados as $key => $value) {
            $dato_encriptado = trim($value["datos_encriptados"]);

            $step = $value["paso"];
            $nameCookie = "paso_$step";
            Util::generarCookieTexto($nameCookie, $dato_encriptado);
        }
        

    }

    public static function disableStepsDb(){
        $res = UsuarioModel::disableStepsDb();
    }

    public static function disableEvidencesDb(){
        $res = UsuarioModel::disableEvidencesDb();
    }
}