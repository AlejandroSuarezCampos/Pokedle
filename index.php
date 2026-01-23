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
                    <td><img src='<?= $intento["pokemon"]["sprite"] ?>'/></td>
                    
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
