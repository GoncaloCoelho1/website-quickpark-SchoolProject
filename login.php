<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', 'error.log'); // Specify the path to your log file

session_start(); // iniciar uma sessao

//apagar qualquer sessao possivel existente quando volta para o menu de login
unset($_SESSION["email"]);
unset($_SESSION["senha"]);

$error = '';
$email = '';
$login = '';
$senha = '';

//limpar texto
function clean_text($string) {
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    return $string;
}

//se o botao login for clicado verifica os dados introduzidos do email e password
if (isset($_POST["login"])) {

    if (empty($_POST["email"])) {
        $error .= '<p><label style="color: red">Introduza um email no campo indicado.</label></p>';
    } else {
        $email = clean_text($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error .= '<p><label style="color: red">Email inválido.</label></p>';
        }
    }

    if (empty($_POST["senha"])) {
        $error .= '<p><label style="color: red">Introduza uma senha no campo indicado.</label></p>';
    } else {
        $senha = clean_text($_POST["senha"]);
    }
}

//se o botao login for clicado e o email e password tiverem preenchidos (depois de validados) com a query seleciona na base de dados o user e atribui-lhe uma sessao
if(isset($_POST["login"]) && !empty($_POST["email"]) && !empty($_POST["senha"])) 
{
    include_once("config.php");
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    //query para ir buscar a senha hashed da bd
    $query_senha= "SELECT * FROM user WHERE Email = '$email' LIMIT 1";

    $result = $conexao->query($query_senha);

    if($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $senha_hashed_bd = $row['Password'];
        $nivel_usuario = $row['User_Nivel']; // Adiciona a verificação do nível do usuário

        if (password_verify($senha, $senha_hashed_bd))
        {
            // Cria atribui à sessão criada o email e password e manda o cliente para a página correta
            $_SESSION["email"] = $email;
            $_SESSION["senha"] = $senha;

            // Verifica o nível do usuário e redireciona para a página correspondente
            if ($nivel_usuario == 2) {
                header("Location: cliente.php");
            } elseif ($nivel_usuario == 1) {
                header("Location: gestao.php");
            }

            exit(); // Importante sair para evitar que o código continue a ser executado
        } else {
            $error .= '<p><label style="color: red">Senha incorreta </label></p>';
        }
    } else {
        $error .= '<p><label style="color: red">Usuário não encontrado </label></p>';
    }
}


?>

<!DOCTYPE html>
<html lang="PT-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickPark-Login</title>
    <link rel="shortcut icon" href="imagens/pi_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="registo.css">
</head>
<body>
    <!-- menu superior da pagina -->
    <header class="menu-principal">
        <a href="index.html">
            <div name="logo-esquerda"></div>
            <img src="imagens/pi_logo2.png" alt="">
        </a>

        <p class="texto-principal">QuickPark</p>

        <div name="menu-direita">
            <a class="contact-us" href="contact_us.html">Contacte-nos</a>
        </div>
    </header>
        <!-- formulario para login com um background fade-->
        <div class="fade">
            <div class="registo">

                <h2 class="texto inicial">Login</h2>

                <form action="" method="post">

                    <?php echo $error; ?>

                    <label for="email">E-mail:</label>
                    <input type="email" class="registo-input" name= "email" id="email" placeholder="Digite o seu e-mail" value="<?php echo $email; ?>"><br>
                
                    <label for="senha">Senha:</label>
                    <input type="password" class="registo-input" name = "senha" id="senha" placeholder="Digite a sua senha" value="<?php echo $senha; ?>"><br>

                    <button type="submit" id = "login" name = "login" class="">Login</button> <br>

                    <label>Não tem conta? <a href="registo.php">Então Registe-se</a></label> 
                    <label>Não se lembra da senha? <a href="recuperacao.php">Faça recuperação</a></label>    
                </form>      
            </div>
        </div>
</body>
</html>