<?php
class MatrizController{
    public static function show(){

        
        $userExist = UsuarioController::userExist();
        if($userExist){
            $titulo = "Sello Sostenible";
            $ejes = MatrizModel::obtenerEjes();
            if(!empty( $ejes)){
                $tr = MatrizModel::obtenerTemasRequisitosSegunEje($ejes[0]["id"]);
                $temasRequisitos = self::procesarRespuestaTemasRequisito($tr);
    
              /*  $idTemaPorDefecto = $temas[0]["id"];
                $requisitos = MatrizModel::obtenerRequisitoSegunTema($idTemaPorDefecto);*/
    
                include VIEWS."/MatrizView.php";
            }else{
                echo "<div data-no-validate class='error-topic t-center'>No se encontraron resultados.</div>";
            }
           
        }else{
            Util::redirigir("/web");
        }
       
    }
    public static function procesarRespuestaTemasRequisito($info){
        $resultados = [];

        // Iterar sobre los datos de la base de datos
        foreach ($info as $dato) {
            $tema = $dato["DESC_TEMA"];
            $nro_tema = $dato["NRO_TEMA"];
            $nro_requisito = $dato["NRO_REQUISITO"];
            $desc_requisito = $dato["DESC_REQUISITO"];
            $descNivel1 = $dato["desc_nivel_1"];
            $descNivel2 = $dato["desc_nivel_2"];
            $descNivel3 = $dato["desc_nivel_3"];
            $descNivel4 = $dato["desc_nivel_4"];
            $descNivel5 = $dato["desc_nivel_5"];
            
            // Si el tema no está en la lista de resultados, añadirlo
            if (!isset($resultados[$nro_tema])) {
                $resultados[$nro_tema] = [
                    "NRO_TEMA" => $nro_tema,
                    "DESC_TEMA" => $tema,
                    "REQUISITOS" => []
                ];
            }
            $descripcionesNivel = [];

            if(!is_null($descNivel1)){
                $descripcionesNivel["Nivel 1"] =  $descNivel1;
            }
            if(!is_null($descNivel2)){
                $descripcionesNivel["Nivel 2"] =  $descNivel2;
            }
            if(!is_null($descNivel3)){
                $descripcionesNivel["Nivel 3"] =  $descNivel3;
            }
            if(!is_null($descNivel4)){
                $descripcionesNivel["Nivel 4"] =  $descNivel4;
            }
            if(!is_null($descNivel5)){
                $descripcionesNivel["Nivel 5"] =  $descNivel5;
            }
            $mostarDetalle = count($descripcionesNivel) > 0 ? true: false;
            // Añadir el requisito al tema
            $resultados[$nro_tema]["REQUISITOS"][] = [
                "NRO_REQUISITO"  => $nro_requisito,
                "DESC_REQUISITO" => $desc_requisito,
                "DESC_NIVELES"   => $descripcionesNivel,
                "SHOW_DETAIL"    => $mostarDetalle
            ];
        }

        return $resultados;
    }
    public static function getTopicRequirementById($eje){
        $userExist = UsuarioController::userExist();
        //$userExist = null;
        if(!$userExist){
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se encontro el usuario."));
            exit();
        }
        
        $tr = MatrizModel::obtenerTemasRequisitosSegunEje($eje);
        $temasRequisitos = self::procesarRespuestaTemasRequisito($tr);
        if(!empty($temasRequisitos)){
            include_once VIEWS."/TopicRequirementView.php";
        }else{
            echo "<div data-no-validate class='error-topic t-center'>No se encontraron resultados.</div>";
        }

        

    }
    public static function uploadFile() {
        global $nro_cliente;
        $userExist = UsuarioController::userExist();
        $paso = $_REQUEST["paso"] ?? null;
        $idInput = $_REQUEST["idInput"] ?? null;
        
        if (is_null($paso)) {
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se pudo validar el paso"));
            exit();
        }
        if (is_null($idInput)) {
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se pudo validar el requerimiento"));
            exit();
        }
    
        if (!$userExist) {
            echo json_encode(array("tipo"=>"error","mensaje"=> "Usuario no encontrado"));
            exit();
        }
    
        $sesion = UsuarioController::getSession();
        if(is_null($sesion)){
            echo json_encode(array("tipo"=>"error","mensaje"=> "Usuario no encontrado"));
            exit();
        }
        $directorio = PATH_FILES . "/$sesion";
        
        if (!is_dir($directorio)) {
            // Intenta crear el directorio si no existe
            if (!mkdir($directorio, 0777, true)) {
                echo json_encode(array("tipo"=>"error","mensaje"=> "Error al subir archivos, intente nuevamente"));
                exit();
            }
        }

        $dirPaso = "paso_$paso";
        $pasoDirectorio = "$directorio/$dirPaso";
        
    
        if (!is_dir($pasoDirectorio)) {
            // Intenta crear el directorio del paso si no existe
            if (!mkdir($pasoDirectorio, 0777, true)) {
                echo json_encode(array("tipo"=>"error","mensaje"=> "Error al subir archivos, intente nuevamente"));
                exit();
            }
        }
        

        // Aquí puedes agregar la lógica para el manejo de archivos
        $files = $_FILES["file"] ?? null;

        if(is_null( $files)){
            echo json_encode(array("tipo"=>"error", "mensaje"=>"No se econtraron archivos"));
            exit();
        }
        $arrayRequirement = explode(",", $idInput);
        //var_dump($files);
        foreach ($files['name'] as $index => $filename) {
            $fileNameToMove = utf8_decode($filename);
            $requirement = explode("_", $arrayRequirement[$index])[2]; // ej evidence_requirement_1
            $tmp_name = $files['tmp_name'][$index];
            $error = $files['error'][$index];
    
            if ($error === UPLOAD_ERR_OK) {
                $destination = "$pasoDirectorio/$fileNameToMove";
    
                if (move_uploaded_file($tmp_name, $destination)) {
                    $datos = array(
                        ":DF_NRO_REQUISITO" => $requirement,
                        ":DF_NOM_ARCHIVO" => $filename,
                        ":DF_SESION_CARPETA" => $dirPaso,
                        ":DF_SESION" => $sesion,
                        ":DF_NRO_CLIENTE" => $nro_cliente,
                        ":DF_IND_HABILITADO" => 'S',
                    );
                   MatrizModel::insertReferenceFile($datos);
                }
            }
        }
        echo json_encode(array("error"=>false));
    }
    public static function getReferencesFileByAxie($eje){

        global $nro_cliente;
        $userExist = UsuarioController::userExist();
        if (!$userExist) {
            echo json_encode(array("tipo"=>"error","mensaje"=> "Usuario no encontrado"));
            exit();
        }
        $sesion = UsuarioController::getSession();
        $datos = array(
            ":DF_NRO_EJE"=> $eje,
            ":DF_NRO_CLIENTE"=> $nro_cliente,
            ":DF_IND_HABILITADO"=> 'S',
        );
        $files = MatrizModel::getReferencesFileByAxie($datos);
        $files = self::processResponseFiles($files);

        echo json_encode(array('tipo'=>"loadFile", 'archivos'=>$files));
    }
    public static function processResponseFiles($info){
        $resultados = [];

        // Iterar sobre los datos de la base de datos
        foreach ($info as $dato) {
            
            $nro_requisito = $dato["NRO_REQUISITO"];
            $nom_archivos = $dato["NOM_ARCHIVO"];
            $nro_documento = $dato["NRO_DOCUMENTO"];

            $resultados[$nro_requisito][] = ["nom_archivo"=> $nom_archivos, "nro_documento" => $nro_documento];
        }

        return $resultados;
    }
    public static function getResults(){

        $datos = self::calculateResult();

        $name1 = self::getNameAxieById("paso_1");
        $name2 = self::getNameAxieById("paso_2");
        $name3 = self::getNameAxieById("paso_3");
        include_once VIEWS."/ResultView.php";
    }
    
    public static function restartFlow(){
        global $currentConfig;
        UsuarioController::deleteSteps();
        UsuarioController::disableStepsDb();
        UsuarioController::disableEvidencesDb();
        echo json_encode(array("tipo"=>"redirect","url"=> $currentConfig["urlRestart"]));
        exit();
    }

    public static function getNameAxieById($param){
        $step = Util::obtenerValorDescomprimidoCookieCodificado($param);
        $axisId = isset($step[count($step) - 1]['eje']) ? $step[count($step) - 1]['eje'] : null;
        if(is_null($axisId)){
            echo json_encode(array("tipo"=>"error","mensaje"=> "Eje no encontrado.."));
            exit();
        }
        $name = MatrizModel::getNameAxieById($axisId);

        if(!$name){
            echo json_encode(array("tipo"=>"error","mensaje"=> "Eje no encontrado"));
            exit();
        }
        $name = explode(" ",$name)[1];

        return $name;
    }

    
    public static function calculateResult(){
        // Obtener los valores desde localStorage
        $paso1 = json_decode(file_get_contents("php://input"), true)["paso_1"] ?? null;
        $paso2 = json_decode(file_get_contents("php://input"), true)["paso_2"] ?? null;
        $paso3 = json_decode(file_get_contents("php://input"), true)["paso_3"] ?? null;
    
        // Verificar si el usuario existe
        $userExist = UsuarioController::userExist();
        $arrayInsert = [];
        
        if(!$userExist){
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se encontro el usuario."));
            exit();
        }
        if(is_null($paso1) || is_null($paso2) || is_null($paso3)){
            echo json_encode(array("tipo"=>"error","mensaje"=> "Debe completar la carga de los 3 ejes para visualizar el resultado."));
            exit();
        }
    
        // Obtener los ejes desde los pasos almacenados
        $eje1 = isset($paso1[count($paso1) - 1]['eje']) ? $paso1[count($paso1) - 1]['eje'] : null;
        $eje2 = isset($paso2[count($paso2) - 1]['eje']) ? $paso2[count($paso2) - 1]['eje'] : null;
        $eje3 = isset($paso3[count($paso3) - 1]['eje']) ? $paso3[count($paso3) - 1]['eje'] : null;
    
        if(is_null($eje1) || is_null($eje2) || is_null($eje3)){
            echo json_encode(array("tipo"=>"error","mensaje"=> "Eje no encontrado.."));
            exit();
        }
    
        $sesion = UsuarioController::getSession();
    
        // Eliminar el �ltimo elemento que contiene el eje
        array_pop($paso1);
        array_pop($paso2);
        array_pop($paso3);
    
        // Procesar el primer eje
        $cantReqPaso1 = count($paso1);
        $primerEje = [];
        for($i = 0 ; $i < $cantReqPaso1; $i++){
            if(strpos($paso1[$i]['id'], 'nivel_req') !== false){
                $nivelReq = $paso1[$i]['valor'];
                $nivel = substr($nivelReq, -1);
                $cumplimientoNivel = $paso1[$i+1]['valor'];
                $primerEje[] = array(
                    "nivel"=> $nivel,
                    "cumplimientoNivel"=> $cumplimientoNivel,
                );
            }
        }
        $nivelesPrimerEje = self::getScore($primerEje);
        $avgLevel1 = end($nivelesPrimerEje);
    
        $arrayInsert[] = array(
            ":DF_NRO_EJE"=> $eje1,
            ":DF_NIVEL_ALCANZADO"=> $avgLevel1,
            ":DF_SESION"=> $sesion
        );
    
        // Procesar el segundo eje
        $cantReqPaso2 = count($paso2);
        $segundoEje = [];
        for($i = 0 ; $i < $cantReqPaso2; $i++){
            if(strpos($paso2[$i]['id'], 'nivel_req') !== false){
                $nivelReq = $paso2[$i]['valor'];
                $nivel = substr($nivelReq, -1);
                $cumplimientoNivel = $paso2[$i+1]['valor'];
                $segundoEje[] = array(
                    "nivel"=> $nivel,
                    "cumplimientoNivel"=> $cumplimientoNivel,
                );
            }
        }
        $nivelesSegundoEje = self::getScore($segundoEje);
        $avgLevel2 = end($nivelesSegundoEje);
    
        $arrayInsert[] = array(
            ":DF_NRO_EJE"=> $eje2,
            ":DF_NIVEL_ALCANZADO"=> $avgLevel2,
            ":DF_SESION"=> $sesion
        );
    
        // Procesar el tercer eje
        $cantReqPaso3 = count($paso3);
        $tercerEje = [];
        for($i = 0 ; $i < $cantReqPaso3; $i++){
            if(strpos($paso3[$i]['id'], 'nivel_req') !== false){
                $nivelReq = $paso3[$i]['valor'];
                $nivel = substr($nivelReq, -1);
                $cumplimientoNivel = $paso3[$i+1]['valor'];
                $tercerEje[] = array(
                    "nivel"=> $nivel,
                    "cumplimientoNivel"=> $cumplimientoNivel,
                );
            }
        }
        $nivelesTercerEje = self::getScore($tercerEje);
        $avgLevel3 = end($nivelesTercerEje);
    
        $arrayInsert[] = array(
            ":DF_NRO_EJE"=> $eje3,
            ":DF_NIVEL_ALCANZADO"=> $avgLevel3,
            ":DF_SESION"=> $sesion
        );
    
        // Guardar los resultados calculados
        self::deleteResultCalculatedBySesion($sesion);
        self::insertResultCalculated($arrayInsert);
    
        $respuesta = array(
            "firstAxis" =>$nivelesPrimerEje,
            "secondAxis"=>$nivelesSegundoEje,
            "thirdAxis" =>$nivelesTercerEje,
        );
    
        return $respuesta;
    }
    
    public static function calculateResult_gabriel(){
        
        $paso1 = Util::obtenerValorDescomprimidoCookieCodificado("paso_1");
        $paso2 = Util::obtenerValorDescomprimidoCookieCodificado("paso_2");
        $paso3 = Util::obtenerValorDescomprimidoCookieCodificado("paso_3");
        

        $userExist = UsuarioController::userExist();
        $arrayInsert = [];
        

       
        if(!$userExist){
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se encontro el usuario."));
            exit();
        }
        if(is_null($paso1) || is_null($paso2) || is_null($paso3)){
            echo json_encode(array("tipo"=>"error","mensaje"=> "Debe completar la carga de los 3 ejes para visualizar el resultado."));
            exit();
        }

        $eje1 = isset($paso1[count($paso1) - 1]['eje']) ? $paso1[count($paso1) - 1]['eje'] : null;
        $eje2 = isset($paso2[count($paso2) - 1]['eje']) ? $paso2[count($paso2) - 1]['eje'] : null;
        $eje3 = isset($paso3[count($paso3) - 1]['eje']) ? $paso3[count($paso3) - 1]['eje'] : null;

        if(is_null($eje1) || is_null($eje2) || is_null($eje3)){
            echo json_encode(array("tipo"=>"error","mensaje"=> "Eje no encontrado.."));
            exit();
        }

        $sesion = UsuarioController::getSession();
        //le quito el ultimo elemento que se, es el eje para evitar problemas en el armado del resultado
        array_pop($paso1);
        array_pop($paso2);
        array_pop($paso3);
       
        $cantReqPaso1 = count($paso1);
        $primerEje = [];
        
        for($i = 0 ; $i < $cantReqPaso1; $i++){

            if(strpos($paso1[$i]['id'], 'nivel_req') !== false){
                $nivelReq = $paso1[$i]['valor'];
                $nivel = substr($nivelReq, -1);
                $cumplimientoNivel = $paso1[$i+1]['valor'];
                $primerEje[] = array(
                    "nivel"=> $nivel,
                    "cumplimientoNivel"=> $cumplimientoNivel,
                );
                
            }
        }
        $nivelesPrimerEje = self::getScore($primerEje);
        $avgLevel1 = end($nivelesPrimerEje);

        $arrayInsert[] = array(
            ":DF_NRO_EJE"=> $eje1,
            ":DF_NIVEL_ALCANZADO"=> $avgLevel1,
            ":DF_SESION"=> $sesion
        );
        
        $cantReqPaso2 = count($paso2);
        $segundoEje = [];
        for($i = 0 ; $i < $cantReqPaso2; $i++){

            if(strpos($paso2[$i]['id'], 'nivel_req') !== false){
                $nivelReq = $paso2[$i]['valor'];
                $nivel = substr($nivelReq, -1);
                $cumplimientoNivel = $paso2[$i+1]['valor'];
                $segundoEje[] = array(
                    "nivel"=> $nivel,
                    "cumplimientoNivel"=> $cumplimientoNivel,
                );
                
            }
        }
        $nivelesSegundoEje = self::getScore($segundoEje);
        $avgLevel2 = end($nivelesSegundoEje);
        
        $arrayInsert[] = array(
            ":DF_NRO_EJE"=> $eje2,
            ":DF_NIVEL_ALCANZADO"=> $avgLevel2,
            ":DF_SESION"=> $sesion
        );
        
        $cantReqPaso3 = count($paso3);
        $tercerEje = [];
        for($i = 0 ; $i < $cantReqPaso3; $i++){

            if(strpos($paso1[$i]['id'], 'nivel_req') !== false){
                $nivelReq = $paso3[$i]['valor'];
                $nivel = substr($nivelReq, -1);
                $cumplimientoNivel = $paso3[$i+1]['valor'];
                $tercerEje[] = array(
                    "nivel"=> $nivel,
                    "cumplimientoNivel"=> $cumplimientoNivel,
                );
                
            }
        }
        $nivelesTercerEje = self::getScore($tercerEje);
        $avgLevel3 = end($nivelesTercerEje);
        
        $arrayInsert[] = array(
            ":DF_NRO_EJE"=> $eje3,
            ":DF_NIVEL_ALCANZADO"=> $avgLevel3,
            ":DF_SESION"=> $sesion
        );
        
        
        self::deleteResultCalculatedBySesion($sesion);
        self::insertResultCalculated($arrayInsert);
        

        $respuesta = array(
            "firstAxis" =>$nivelesPrimerEje,
            "secondAxis"=>$nivelesSegundoEje,
            "thirdAxis" =>$nivelesTercerEje,
        );

        return $respuesta;


    }
    public static function getScore($datos){

        $niveles = array(
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0
        );

        $total = count($datos);
        $porcentajeNiveles = [];

        foreach ($datos as $key => $value) {
            $nivel = $value["nivel"];
            $cumplimientoNivel = $value["cumplimientoNivel"];

            // Si el nivel es 0, lo descontamos del total para no tenerlo en cuenta
            if($nivel == 0){
               $total --;
            }else{
                for($i = 1 ; $i <= $nivel; $i++){

                    if($i != $nivel){
                        $niveles[$i] ++;
                    }else{
                        if($cumplimientoNivel == "parcial"){
                            $niveles[$nivel] += 0.5;
                        }else if($cumplimientoNivel == "total"){
                            $niveles[$nivel] ++;
                        }
                    }
                }
            }

            

        }

        //si son todos no aplica
        $total = $total == 0 ? 1 : $total;

        for($i = 1 ; $i <= count($niveles); $i++){
            $porcentaje = ($niveles[$i] * 100) / $total;
            $porcentajeNiveles[$i] = Util::redondeoPersonalizado($porcentaje);
        }

        // determino el nivel
        $nivelEje = 0;
        
        if($porcentajeNiveles[5] >= 80 && $porcentajeNiveles[4] >= 90 && $porcentajeNiveles[3] >= 100 && $porcentajeNiveles[2] >= 100 && $porcentajeNiveles[1] >= 100){
            $nivelEje = 5;
        }elseif($porcentajeNiveles[4] >= 80 && $porcentajeNiveles[3] >= 90 && $porcentajeNiveles[2] >= 100 && $porcentajeNiveles[1] >= 100){
            $nivelEje = 4 ;
        }elseif($porcentajeNiveles[3] >= 80 && $porcentajeNiveles[2] >= 90 && $porcentajeNiveles[1] >= 100){
            $nivelEje = 3 ;
        }elseif($porcentajeNiveles[2] >= 80 && $porcentajeNiveles[1] >= 90){
            $nivelEje = 2 ;
        }elseif($porcentajeNiveles[1] >= 80){
            $nivelEje = 1 ;
        }
        
        $porcentajeNiveles[]=  $nivelEje;

        return $porcentajeNiveles;
        
    }
    public static function getColour($porcentaje){
        $colour = "";
        switch (true) {
            
            case $porcentaje <= 80:
                # code...
                $colour = "red";
                break;
            case $porcentaje > 80 && $porcentaje <= 99:
                # code...
                $colour = "yellow";
                break;
            case $porcentaje == 100:
                # code...
                $colour = "green";
                break;
           
            
            default:
                # code...
                break;
        }

        return $colour;
    }
    public static function showReport(){
        //en este caso forzamos el error 400 para que no se genere el pdf y al abrir este dañado

        $userExist = UsuarioController::userExist();
        if(!$userExist){
            http_response_code(400);
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se encontro el usuario."));
            exit();
        }

        $report = $_REQUEST["report"] ?? false;
        $reportI = $_REQUEST["reportI"] ?? false;
        //$report = "df0f07ce12b920b21ccf7fade1f40cbc1fd404f49ff8adb46dfba5d4236d3b2d";
        include_once UTIL."/PDFGenerator.class.php";
        //var_dump($_REQUEST);
        //var_dump($_REQUEST);exit();
        if(!$report){
            http_response_code(400);
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se pudo obtener el reporte."));
            exit();
        }
        $assesment = self::getAssessment($report, $reportI);
        $calculatedResult = self::getCalculatedReult($report);
        $infoCompany = self::getInfoCompany($report, $reportI);
        if(empty( $assesment) || empty( $calculatedResult) || empty( $infoCompany) ){
            http_response_code(400);
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se pudo obtener el reporte."));
            exit();
        }

        $body = self::getBodyPDF($assesment, $calculatedResult, $infoCompany);

        $pdf = new PDFGenerator();    
        $fileName = "prueba.pdf";
        $pdf->generatePDF($body);

       
       // echo json_encode(array("tipo"=>"PDF", "info"=>$data));
    }

    public static function insertResultCalculated($datos){
       
        for($i = 0; $i<count($datos); $i++){
            MatrizModel::insertResultCalculated($datos[$i]);
        }   
        
    }

    public static function deleteResultCalculatedBySesion($sesion){
       
        MatrizModel::deleteResultCalculatedBySesion($sesion);
    }

    public static function generateReport(){

        $paso1 = Util::obtenerValorDescomprimidoCookieCodificado("paso_1");
        $paso2 = Util::obtenerValorDescomprimidoCookieCodificado("paso_2");
        $paso3 = Util::obtenerValorDescomprimidoCookieCodificado("paso_3");
        $userExist = UsuarioController::userExist();
        $arrayInsert = [];
        
        //$userExist = null;
       
        if(!$userExist){
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se encontro el usuario."));
            exit();
        }
        if(is_null($paso1) || is_null($paso2) || is_null($paso3)){
            echo json_encode(array("tipo"=>"error","mensaje"=> "Debe completar la carga de los 3 ejes para visualizar el resultado."));
            exit();
        }

        $sesion = UsuarioController::getSession();
        //le quito el ultimo elemento que se, es el eje para evitar problemas en el armado del resultado
        array_pop($paso1);
        array_pop($paso2);
        array_pop($paso3);


        $secureCode = Util::generateSecureCode();
        global $nro_cliente;
        $responsibles = UsuarioController::getResponsibles();
        $userPhone = UsuarioController::getUserPhone();
        $userEmail = UsuarioController::getUserEmail();
        $revise = isset($_REQUEST["revisar"]) && ($_REQUEST["revisar"] === "true") ? 'S' : 'N';

        
        $data = array(
            ":DF_COD_SEGURIDAD" => $secureCode,
            ":DF_NRO_CLIENTE" => $nro_cliente,
            ":DF_TELEFONO" => $userPhone,
            ":DF_DESC_RESPONSABLE" => $responsibles,
            ":DF_E_MAIL" => $userEmail,
            ":DF_IND_ENVIAR_LATU" => $revise,
            ":DF_SESION" => $sesion,
        );

        $id = MatrizModel::saveReport($data);
        MatrizModel::saveReferenceDocumentReport($id);
        if(!$id){
            echo json_encode(array("tipo"=>"error","mensaje"=> "No se pudo generar el informe"));
            exit();
        }else{
           
            $insert1 = self::processInfoInsertRequirement($paso1, $id);
            $insert2 = self::processInfoInsertRequirement($paso2, $id);
            $insert3 = self::processInfoInsertRequirement($paso3, $id);

            self::insertInfoInsertRequirement($insert1);
            self::insertInfoInsertRequirement($insert2);
            self::insertInfoInsertRequirement($insert3);
            
        }
        UsuarioController::changeSession();
        UsuarioController::deleteSteps();
        include_once VIEWS."/FinalReport.php";
    }

    public static function getBodyPDF($assesmentP, $calculatedResultP, $infoCompanyP){

        /**
         * ir al modelo  a buscar la info
         */
        $assesment = $assesmentP;
        $calculatedResult = $calculatedResultP;
        $infoCompany = $infoCompanyP[0];

        $companyName = "Prueba Gabi";
        ob_start(); // Inicia el almacenamiento en búfer de salida
        include VIEWS."/ContentPDF.php";
        $html = ob_get_clean();

        return $html;


    }

    public static function getComplianceEquivalence($param){

        $equivalence = "";
        switch ($param) {
            case 'total':
                $equivalence = "C";
                break;
            case 'parcial':
                $equivalence = "P";
                break;
            case 'noCumple':
                $equivalence = "NC";
                break;
            
            default:
                $equivalence = null;
                break;
        }


        return $equivalence;
    }

    public static function processInfoInsertRequirement($datos, $id){
        $cantDatos = count($datos);
        $infoInsert = [];
        
        for($i = 0 ; $i < $cantDatos; $i++){

            if(strpos($datos[$i]['id'], 'cumplimiento_requirement') !== false){
                $requisito = explode("_",$datos[$i]["id"])[2];
                $descripcion = $datos[$i]['valor'];
                $nivel = substr($datos[$i+1]['valor'], -1);
                $cumplimientoNivel = self::getComplianceEquivalence($datos[$i+2]['valor']);
                $cumplimientoNivel = $nivel == 0 ? "NA" : $cumplimientoNivel;
                $infoInsert[] = array(
                    ":DF_NRO_REPORTE"=> $id,
                    ":DF_DESC_EVIDENCIA"=> $descripcion,
                    ":DF_NRO_NIVEL"=> $nivel,
                    ":DF_CUMPLIMIENTO"=> $cumplimientoNivel,
                    ":DF_NRO_REQUISITO"=> $requisito
                );
                
            }
        }

        return $infoInsert;
    }


    public static function insertInfoInsertRequirement($datos){
       
        for($i = 0; $i<count($datos); $i++){
            MatrizModel::insertInfoInsertRequirement($datos[$i]);
        }   
        
    }

    public static function getAssessment($sesion, $idReporte){

        $datos = MatrizModel::getAssessment($sesion, $idReporte);

        $datosAgrupados = [];

        // Iterar sobre los datos de la base de datos
        foreach ($datos as $dato) {
            $descEje = $dato["DESC_EJE"];
            $descTema = $dato["DESC_TEMA"];

            if(!isset($datosAgrupados[$descEje])){
                $datosAgrupados[$descEje] = [];
            }


            if(!isset($datosAgrupados[$descEje][$descTema])){
                $datosAgrupados[$descEje][$descTema] = [];
            }

            unset($dato["DESC_EJE"]);
            unset($dato["DESC_TEMA"]);
            $datosAgrupados[$descEje][$descTema][] = $dato;
                       
        }



        return $datosAgrupados;

    }


    public static function getInfoCompany($sesion, $reportI){

        $datos = MatrizModel::getInfoCompany($sesion, $reportI);

        return $datos;
    }
    public static function getCalculatedReult($sesion){

        $datos = MatrizModel::getCalculatedReult($sesion);

        return $datos;
    }

    public static function deleteFile($idArchivo){

        global $nro_cliente;
        $data = MatrizModel::fileExistInDb($idArchivo);

        if(!$data){
            echo json_encode(array("tipo"=>"error","mensaje"=> "No existe ese archivo"));
            exit();
        }else{
            $clienteArchivo = $data["NRO_CLIENTE"];
            $habilitado = $data["IND_HABILITADO"];

            if($clienteArchivo != $nro_cliente){
                echo json_encode(array("tipo"=>"error","mensaje"=> "El archivo no corresponde al usuario logueado"));
                exit();
            }
            if($habilitado == "N"){
                echo json_encode(array("tipo"=>"error","mensaje"=> "Archivo eliminado anteriormente"));
                exit();
            }

            //mando a eliminar- baja logica
            $update = MatrizModel::deleteFile($idArchivo);
            if($update){
                echo json_encode(array("tipo"=>"deleteFile","mensaje"=> "Archivo eliminado con &eacute;xito", "file"=>$idArchivo));
                exit();
            }else{
                echo json_encode(array("tipo"=>"error","mensaje"=> "No se pudo elminar, reintente nuevamente"));
                exit();
            }
        }




    }
    
   
    
}