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

    // Si no hay un número de la pokedex (1ª gen - 5ª gen) aleatorio en sesión, genera uno
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
        $etapaSecreta = getEtapaEvolutiva($_SESSION["pokemonAleatorio"]);

        //guarda los datos en un array en sesión
        $_SESSION["pokemonSecreto"]=[

            "nombre"=>$nombre,
            "altura"=>$altura,
            "peso"=>$peso,
            "tipo1"=>$tipo1,
            "tipo2"=>$tipo2,
            "numero"=>$numero,
            "sprite"=>$sprite,
            "etapa" => $etapaSecreta

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
//de momento solo pokemons de gen 1 hasta gen 5 para que nos sea más sencillo
function getPokemonData($pokemon) {

    $url = "https://pokeapi.co/api/v2/pokemon/" . $pokemon . "/";
    $response = callAPI($url);

    // Error en la API
    if ($response < 0) {
        return null;
    }

    $poke = json_decode($response, true);

    //Obtener información del pokemon
    $speciesUrl = $poke["species"]["url"];
    $speciesResponse = callAPI($speciesUrl);
    $speciesData = json_decode($speciesResponse, true);
    
    //Obtener cadena evolutiva
    $evolutionUrl = $speciesData["evolution_chain"]["url"];
    $evolutionResponse = callAPI($evolutionUrl);
    $evolutionData = json_decode($evolutionResponse, true);

    // Determinar etapa evolutiva (forma base=1, primera evolución=2, segunda evolución=3)
    $etapa = getEtapaEvolutiva($poke["id"]);

    return [

        "nombre" => $poke["name"],
        "altura" => $poke["height"] / 10,
        "peso"   => $poke["weight"] / 10,
        "tipo1"  => $poke["types"][0]["type"]["name"],
        "tipo2"  => $poke["types"][1]["type"]["name"] ?? null,
        "numero" => $poke["id"],
        "sprite" => getSprite($poke["id"]),
        "etapa"  => $etapa

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
        "numero" => compararNumero($intento["numero"],$secreto["numero"]),
        "etapa" => compararNumero($intento["etapa"], $secreto["etapa"])
    ];

}


//Saca el sprite de un pokémon, pasas el número de pokédex a la función
function getSprite($idPokedex){

    $urlSprite="https://pokeapi.co/api/v2/pokemon-form/". $idPokedex ."/";
    $respSprite=callAPI($urlSprite);
    $sprite=json_decode($respSprite, true);
    return $sprite["sprites"]["front_default"];

}

//Función que obtiene el ID numérico desde una URL de la PokéAPI
function getIdFromUrl($url){
    return intval(basename(rtrim($url, "/")));
    //Elimina la barra final si existe y obtiene el último segmento
    
    //Es la solución que me ha dado chatgpt, porque al parecer la / al final de la url hace que se ralle y no devuelva bien la etapa, cuando leas este comentario puedes borrarlo en vrd
}

//Obtiene los datos de species de un pokémon
function getPokemonSpecies($pokemonId){

    $speciesUrl="https://pokeapi.co/api/v2/pokemon-species/".$pokemonId."/";
    $speciesResp=callAPI($speciesUrl);

    //Si hay algún error, devuelve null
    if($speciesResp<0){
        return null;
    }

    return json_decode($speciesResp, true);
}


//Obtiene la cadena evolutiva a partir de los datos de species
function getEvolutionChain($speciesData){

    if(!isset($speciesData["evolution_chain"]["url"])){
        return null;
    }

    $evolutionUrl=$speciesData["evolution_chain"]["url"];
    $evolutionResp=callAPI($evolutionUrl);

    if($evolutionResp<0){
        return null;
    }

    return json_decode($evolutionResp, true);
}


//Busca en la cadena evolutiva la etapa del pokémon (1, 2 o 3)
function buscarEtapaEnCadena($chain, $pokemonId){

    //Etapa 1, Pokémon base
    if(getIdFromUrl($chain["species"]["url"])==$pokemonId){
        return 1;
    }

    //Etapa 2 y 3
    foreach($chain["evolves_to"] as $evolution1){

        if(getIdFromUrl($evolution1["species"]["url"])==$pokemonId){
            return 2;
        }

        //Etapa 3
        foreach($evolution1["evolves_to"] as $evolution2){
            if(getIdFromUrl($evolution2["species"]["url"])==$pokemonId){
                return 3;
            }
        }
    }

    //Por defecto, etapa básica
    return 1;
}


//Devuelve la etapa evolutiva del pokémon (1, 2 o 3)
function getEtapaEvolutiva($pokemonId){

    //Obtener datos del pokemon (species)
    $speciesData=getPokemonSpecies($pokemonId);

    //Si hay algún error asume que está en etapa básica
    if($speciesData==null){
        return 1;
    }

    //Obtener cadena evolutiva
    $evolutionData=getEvolutionChain($speciesData);

    if($evolutionData==null){
        return 1;
    }

    //Buscar etapa en la cadena evolutiva
    return buscarEtapaEnCadena($evolutionData["chain"], $pokemonId);
}


?>