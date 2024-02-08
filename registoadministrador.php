<?php

$error = '';
$nome = '';
$email = '';
$telefone = '';
$senha = '';
$csenha = '';


session_start();
// Se não existir sessão, redirecione para a página de login, caso exista entra na pagina
if ((!isset($_SESSION["email"]) == true) and (!isset($_SESSION["senha"]) == true)) {
    header("Location: login.php");
}


// funcao limpar texto
function clean_text($string) {
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    return $string;
}

//funcao para limpar non-digit characters e validar se o numero inserido tem 9 digitos numericos
function validarTelefone($telefone) {

    $telefone = preg_replace('/\D/', '', $telefone);
    return (strlen($telefone) === 9 && is_numeric($telefone));
}

if (isset($_POST["registar"])) { //quando o botao for clicado inicia o ciclo

    if (empty($_POST["nome"])) {//se estiver vazio dá erro

        $error .= '<p><label style="color: red">Introduza o Nome no campo indicado.</label></p>';

    } else { //limpa o texto

        $nome = clean_text($_POST["nome"]);
    }

    if (empty($_POST["email"])) { //se estiver vazio dá erro

        $error .= '<p><label style="color: red">Introduza um email no campo indicado.</label></p>';
    
    } else { //limpa o texto

        $email = clean_text($_POST["email"]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {//valida o email introduzido, se nao estiver com o formato de email vai dar erro

            $error .= '<p><label style="color: red">Email inválido.</label></p>';

        } else { //verifica se o email ja esta associado a alguma conta
            include_once("config.php");

            $query_verifica_email = "SELECT * FROM user WHERE Email = '$email'";
            $verificar_email_existente = $conexao->query($query_verifica_email);

            //se o resultado na query retornar uma linha existente entao aparece erro
            if ($verificar_email_existente && $verificar_email_existente->num_rows == 1){

                $error .= '<p><label style="color: red">Este email ja está associado a uma conta.</label></p>';
            }
        }
    }

    if (empty($_POST["telefone"])) {//se estiver vazio dá erro

        $error .= '<p><label style="color: red">Introduza o número de telefone no campo indicado.</label></p>';
    
    } else { //limpa o texto

        $telefone = clean_text($_POST["telefone"]);

        if (!validarTelefone($telefone)) { //verifica se tem 9 digitos

            $error .= '<p><label style="color: red">Número de telefone inválido. Deve ter 9 dígitos.</label></p>';
        }
    }

    if (empty($_POST["senha"])) {//se estiver vazio dá erro

        $error .= '<p><label style="color: red">Introduza uma senha no campo indicado.</label></p>';
    } 

    if (empty($_POST["csenha"])) {//se estiver vazio dá erro

        $error .= '<p><label style="color: red">Introduza a mesma senha no campo indicado.</label></p>';

    } elseif($senha !== $csenha) //se a senha introduzida for diferente da confirmaçao de senha, dá erro
    {
        $error .= '<p><label style="color: red">As senhas não coincidem.</label></p>'; 
    }
   
}

//se nao houver erros, se o botao registar for clicado e se nao houver nenhuma matricula no sistema
if (empty($error) && isset($_POST["registar"])) { 
    
    include_once("config.php"); //ligaçao à bd

    $email = $_POST['email'];

    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); //encripta a senha

    //insere o novo user
    $query_user = "INSERT INTO user (Nome, Email, Telefone, Password, Saldo, User_nivel) 
                VALUES ('$nome', '$email', '$telefone', '$senha', 0, 1)";

        
        if ($conexao->query($query_user)) { // se a segunda inserção também foi bem sucedida, redireciona para a página de login

            header("Location: login.php");
            exit(); 

        
    } else { // Se a primeira inserção falhar dá erro

        $error .= '<p><label style="color: red">Erro ao inserir na base de dados (user).</label></p>';
    }
}

?>



<!DOCTYPE html>
<html lang="PT-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickPark-Registo</title>
    <link rel="shortcut icon" href="imagens/pi_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="registo.css">

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- menu superior -->
    <header class="menu-principal">
        <a href="index.html">
            <div name="logo-esquerda">
                <img src="pi_logo2.png" alt="">
            </div>
        </a>

        <p class="texto-principal">QuickPark</p>

        <div name="menu-direita">
            <a class="contact-us" href="contact_us.html">Contacte-nos</a>
        </div>
    </header>
    <!-- formulario de registo com background fade-->
    <div class="fade">
        <div class="registo">

            <h2 class="texto inicial">Registo</h2>

            <form action="" method="post">

                <?php echo $error; ?>

                <label for="nome">Nome:</label>
                <input type="text" class="registo-input" name="nome" id="nome" placeholder="Digite seu Primeiro e Último nome" value="<?php echo $nome; ?>"><br>

                <label for="email">E-mail:</label>
                <input type="email" class="registo-input" name="email" id="email" placeholder="Digite seu e-mail" value="<?php echo $email; ?>"><br>

                <label for="telefone">Telefone:</label>
                <input type="text" class="registo-input" name="telefone" id="telefone" placeholder="Digite seu telefone" value="<?php echo $telefone; ?>"><br>

                <label for="senha">Senha:</label>
                <input type="password" class="registo-input" name="senha" id="senha" placeholder="Digite sua senha"><br>

                <label for="confirmar-senha">Confirmar Senha:</label>
                <input type="password" class="registo-input" name="csenha" id="csenha" placeholder="Confirme sua senha" ><br>

                <button type="submit" id="registar" name="registar" class="" link="">Registar</button> <br>

            </form>

            <label><a href="gestao.php">Voltar</a></label>
        </div>
    </div>
</body>
</html>