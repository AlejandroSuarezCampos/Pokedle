<?php


//Función que realiza la llamada a la API usando cURL
function callAPI($url) {
    
    // Inicializamos cURL con la URL indicada
    $ch = curl_init($url);

    // Indicamos que queremos obtener la respuesta como string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // Ejecutamos la petición y guardamos la respuesta
    $resp = curl_exec($ch);
    
    // Comprobamos si NO ha habido errores en cURL
    if (curl_errno($ch) == 0) {
    
        // Obtenemos el código de respuesta HTTP (200, 404, 500, etc.)
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Si el código HTTP es 200 (OK), devolvemos la respuesta
        if ($http_code == 200) {
            return $resp;
        } else {
            // Error HTTP
            return -1;
        }
    } else {
        // Error en la ejecución de cURL
        return -2;
    }
    
    // Cerramos la sesión cURL
    curl_close($ch);

}


//Genera un pokémon aleatorio y devuelve un array con sus datos
function generarPokemonSecreto(){

    // Si no hay un número de la pokedex (1ª gen) aleatorio en sesión, genera uno
    if(!isset($_SESSION["pokemonAleatorio"])){

        $_SESSION["pokemonAleatorio"]=random_int(1, 649);

    }

    if(!isset($_SESSION["acertado"])){

        $_SESSION["acertado"]=false;

    }
    
    $url="https://pokeapi.co/api/v2/pokemon/". $_SESSION["pokemonAleatorio"] ."/";

    $resp=callAPI($url);

    if($resp==-1){

        echo "Error HTTP al conectar con la PokéAPI.";
        verError($url);
        return -1;

    }else if($resp==-2){

        echo "Error en la ejecución de cURL.";
        return -2;

    }else if($resp>0){

        // Decodificar JSON
        $pokemon=json_decode($resp, true);


        //Hacer otra llamada a la pokeapi para sacar el sprite del pokémon aleatorio
        $sprite=getSprite($_SESSION["pokemonAleatorio"]);


        // Extraer datos importantes
        $nombre=ucfirst($pokemon["name"]);//ucfirst() devuelve la primera letra en mayus, puramente estetico
        $altura=$pokemon["height"] / 10; // decímetros → metros
        $peso=$pokemon["weight"] / 10;   // hectogramos → kg
        $tipo1=$pokemon["types"][0]["type"]["name"];
        $tipo2=$pokemon["types"][1]["type"]["name"] ?? null;  //si no tiene segundo tipo, $tipo2 es null
        $numero=$pokemon["id"];


        //guarda los datos en un array en sesión
        $_SESSION["pokemonSecreto"]=[

            "nombre"=>$nombre,
            "altura"=>$altura,
            "peso"=>$peso,
            "tipo1"=>$tipo1,
            "tipo2"=>$tipo2,
            "numero"=>$numero,
            "sprite"=>$sprite

        ];


        //inicializa en sesión intentos solo si no existe
        if(!isset($_SESSION["intentos"])){

            $_SESSION["intentos"]=[];

        }

    }

    return true;

}


//Procesa un intento de adivinar el pokémon del usuario
function procesarIntento(){

    $intentoNombre=strtolower(trim($_POST["intento"])); //trim hace que si hay espacios los suprime
    $intentoArray=getPokemonData($intentoNombre);

    if($intentoArray==null){

        echo "Ese Pokémon no existe";

    }else{

        $resultado = compararPokemon($intentoArray,$_SESSION["pokemonSecreto"]);

        array_unshift($_SESSION["intentos"],[

                "pokemon" => $intentoArray,
                "resultado" => $resultado

            ]);


        if ($resultado["nombre"]) {

            $_SESSION["acertado"]=true;

        }

    }

}


//Esta función devuelve el error de http que haya, si es que hay alguno
function verError($url){

    echo "Error HTTP. Código recibido: ";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo $http_code;

}


//Función que según el pokemon introducido devuelve sus datos
//de momento solo pokemons de gen 1 para que nos sea más sencillo
function getPokemonData($pokemon) {

    $url = "https://pokeapi.co/api/v2/pokemon/" . $pokemon . "/";
    $response = callAPI($url);

    // Error en la API
    if ($response < 0) {

        return null;

    }

    $poke = json_decode($response, true);

    return [

        "nombre" => $poke["name"],
        "altura" => $poke["height"] / 10,
        "peso"   => $poke["weight"] / 10,
        "tipo1"  => $poke["types"][0]["type"]["name"],
        "tipo2"  => $poke["types"][1]["type"]["name"] ?? null,
        "numero" => $poke["id"],
        "sprite" => getSprite($poke["id"])

    ];

}


//Función para comparar los tipos según el slot 1 y 2, si es correcto devuelve verde, si es correcto en otro slot devuelve amarillo, y si es incorrecto o null devuelve rojo
function compararTipo($tipoIntento, $tipoSecreto1, $tipoSecreto2) {

    //ambos no tienen tipo
    if($tipoIntento==null && $tipoSecreto1==null){

        return "green"; //wazaaaaaa

    }

    //intento no tiene tipo pero el secreto sí
    if($tipoIntento==null){

        return "red"; //wazaaaaaan't

    }

    //mismo slot
    if($tipoIntento==$tipoSecreto1){

        return "green"; //wazaaaaaa

    }

    //otro slot
    if($tipoIntento==$tipoSecreto2){

        return "yellow"; //wazaaaaaan't?

    }

    //no coincide
    return "red"; //wazaaaaaan't

}


//Esta función la podemos usar cualquier campo númerico (let me cocks), altura, peso, y número de pokedex 
function compararNumero($intento, $secreto) { 

    if($intento==$secreto){ 

        return "green"; //wazaaaaaa 

    }

    if($intento>$secreto){ 

        return "down"; //wazaaaaaan't 

    }

    if($intento<$secreto){ 

        return "up"; //wazaaaaaan't 

    } 

}


//La función hace uso de las otras para 
function compararPokemon($intento, $secreto) {

    return [
        "nombre" => (strtolower($intento["nombre"])==strtolower($secreto["nombre"])),
        "tipo1" => compararTipo($intento["tipo1"],$secreto["tipo1"],$secreto["tipo2"]),
        "tipo2" => compararTipo($intento["tipo2"],$secreto["tipo2"],$secreto["tipo1"]),
        "altura" => compararNumero($intento["altura"],$secreto["altura"]),
        "peso" => compararNumero($intento["peso"],$secreto["peso"]),
        "numero" => compararNumero($intento["numero"],$secreto["numero"])
    ];

}


//Saca el sprite de un pokémon, pasas el número de pokédex a la función
function getSprite($idPokedex){

    $urlSprite="https://pokeapi.co/api/v2/pokemon-form/". $idPokedex ."/";
    $respSprite=callAPI($urlSprite);
    $sprite=json_decode($respSprite, true);
    return $sprite["sprites"]["front_default"];

}


?>