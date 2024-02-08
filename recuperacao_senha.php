<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', 'error.log'); 

$error = '';
$senha = '';
$csenha = '';

function clean_text($string) {
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    return $string;
}

include_once("config.php");

//recebe o token da pagina
$token = $_GET["token"];

// Verifica se o token está ativo
$query_verifica_token = "SELECT * FROM tokens WHERE token_gerado = '$token' AND token_ativo = 1";
$result_verifica_token = $conexao->query($query_verifica_token);

//caso ja nao esteja ativo redireciona para a pagina de login
if (!$result_verifica_token || $result_verifica_token->num_rows !== 1) {
    header("Location: login.php");
}

//caso passe na verificaçao e o token estiver ativo
if (isset($_POST["recuperar"]) && $result_verifica_token->num_rows == 1) {

    $row_token = $result_verifica_token->fetch_assoc();
    $user_id = $row_token["token_user_id"];

    // Recupere o e-mail associado ao user_id do token
    $query_email = "SELECT Email FROM user WHERE UserID = '$user_id'";
    $result_email = $conexao->query($query_email);

    if (!$result_email || $result_email->num_rows !== 1) {
        $error .= '<p><label style="color: red">Erro ao receber o e-mail associado ao token.</label></p>';
    } else {
        $row_email = $result_email->fetch_assoc();
        $email = $row_email["Email"];

        $senha = $_POST["senha"];

        // Atualizar a senha na tabela
        $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);
        $query_atualiza_senha = "UPDATE user SET Password = '$senha_hashed' WHERE Email = '$email'";
        $conexao->query($query_atualiza_senha);

        // Desativar o token
        $query_desativa_token = "UPDATE tokens SET token_ativo = 0 WHERE token_gerado = '$token'";
        $result = $conexao->query($query_desativa_token);

        if($result){
            $error .= '<p><label style="color: blue">Senha atualizada com sucesso!</label></p>';
            header("Location: login.php");
        }

    }  
}


?>

<!DOCTYPE html>
<html lang="PT-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickPark-Recuperação de Senha</title>
    <link rel="shortcut icon" href="imagens/pi_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="registo.css">
</head>
<body>
    <header class="menu-principal">
        <a href="index.php">
            <div name="logo-esquerda">
                <img src="pi_logo2.png" alt="">
            </div>
        </a>
        <p class="texto-principal">QuickPark</p>
        <div name="menu-direita">
            <a class="contact-us" href="url">Contacte-nos</a>
        </div>
    </header>
    <div class="fade">
        <div class="registo">
            <h2 class="texto inicial">Recuperação de Senha</h2>
            <!-- Adicione o formulário de recuperação de senha aqui -->
            <form method="post">

                <label for="senha">Senha:</label>
                <input type="password" class="registo-input" name="senha" id="senha" placeholder="Digite sua senha"><br>

                <label for="confirmar-senha">Confirmar Senha:</label>
                <input type="password" class="registo-input" name="csenha" id="csenha" placeholder="Confirme sua senha"><br>

                <button type="submit" id="recuperar" name= "recuperar" class="botao_login">Salvar nova senha</button> <br>

            </form>
            <a href="recuperacao.php">Voltar para trás</a>
        </div>
    </div>
</body>
</html>
