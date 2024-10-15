<?php
class HomeController{
    public static function show(){
        $usuario = Util::obtenerValorCookie("usuario");
        if(empty($usuario)){
            $titulo = "Sello Sostenible";
            include VIEWS."/HomeView.php";
        }else{
            Util::redirigir("carga-matriz");
        }
    }
}