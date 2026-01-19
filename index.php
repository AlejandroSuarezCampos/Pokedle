<?php
    session_start();
    require_once("funciones.php");


    //Reiniciar el juego
    if(isset($_POST["reiniciar"])){

        session_destroy();
        header("Location: ". $_SERVER["PHP_SELF"]);

    }

    
    //Genera el pokémon secreto y lo guarda en sesión
    generarPokemonSecreto();

    /*
    Genera las siguientes variables de sesión:
    $_SESSION["pokemonSecreto"] Array de info del pokémon secreto
    $_SESSION["intentos"] Array, por ahora vacío, donde irán todos los intentos
    $_SESSION["acertado"] Booleano que empieza en false e indica si el usuario ha acertado el poke secreto
    $_SESSION["pokemonAleatorio"] Id del pokémon aleatorio (ya lo tenemos en $_SESSION["pokemonSecreto"]["numero"])
    */


    //bucle para saber qué pokemon nos saca
    //esto lo borraremos, de momento es solo para pruebas
    echo "<img src='". $_SESSION["pokemonSecreto"]["sprite"] ."'/><br/>";
    foreach($_SESSION["pokemonSecreto"] as $clave => $valor){


        // Si tipo2 es null, muestra —
        if ($clave=="tipo2"&&$valor==null) {

            $valor="—";

        }

        echo $clave.": ".$valor."<br>";

    }

    if(isset($_POST["probar"])){

        procesarIntento();

    }

    

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pokémon Wordle</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>Pokémon Wordle</h1>

<?php if(!$_SESSION["acertado"]){
?>
    <h3>Adivina el Pokémon secreto</h3>

    <form method="POST" action="<?=$_SERVER["PHP_SELF"]?>">
        <input type="text" name="intento" placeholder="Nombre del Pokémon">
        <button type="submit" name="probar">Probar</button>
        <button type="submit" name="reiniciar">Reiniciar juego</button>
    </form>

<?php }else{
?>

    <h3>¡Has adivinado el pokémon!</h3>
    <form method="POST" action="<?=$_SERVER["PHP_SELF"]?>">
        <button type="submit" name="reiniciar">Reiniciar juego</button>
    </form>

    <!-- Ya borraremos esto grrrr -->
    <img src="data:image/jpeg;base64,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"/>

<?php } ?>

<hr>

<table>
    <thead>
        <tr>

            <th>Sprite</th>
            <th>Nombre</th>
            <th>Tipo 1</th>
            <th>Tipo 2</th>
            <th>Altura</th>
            <th>Peso</th>
            <th>Etapa</th>

        </tr>
    </thead>
    <?php if(!empty($_SESSION["intentos"])){
            foreach($_SESSION["intentos"] as $intento){ ?>
                <tr>
                    <td><img src='<?= $intento["pokemon"]["sprite"] ?>' height="50"/></td>
                    
                    <td class="<?=$intento["resultado"]["nombre"] ? 'green' : ''?>"><?=ucfirst($intento["pokemon"]["nombre"])?></td>
                    
                    <td class="<?=$intento["resultado"]["tipo1"]?>"><?=$intento["pokemon"]["tipo1"]?></td>
                    
                    <td class="<?= $intento["resultado"]["tipo2"]?>"><?=$intento["pokemon"]["tipo2"] ?? "—"?></td>
                    
                    <td class="<?= $intento["resultado"]["altura"]?>"><?=$intento["pokemon"]["altura"]?>m</td>
                    
                    <td class="<?=$intento["resultado"]["peso"]?>"><?=$intento["pokemon"]["peso"]?>kg</td>
                    
                    <td class="<?=$intento["resultado"]["etapa"]?>">
                        <?php
                            $etapaNum = $intento["pokemon"]["etapa"];
                            if ($etapaNum==1){
                                echo "1";
                            }elseif ($etapaNum==2){
                                echo "2";
                            }elseif ($etapaNum==3){
                                echo "3";
                            }else{
                                echo "Etapa $etapaNum";
                            }
                        ?>
                    </td>
                </tr>
        <?php }} ?>
    </tbody></table>

</body>
</html>
