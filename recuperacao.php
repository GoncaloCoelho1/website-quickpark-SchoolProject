<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', 'error.log'); 


$error = '';
$email = '';

include_once('config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exeption;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';


function clean_text($string) {
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    return $string;
}

if (isset($_POST["recuperar"])) {

    if (empty($_POST["email"])) {
        $error .= '<p><label style="color: red">Introduza um email no campo indicado.</label></p>';
    } else {
        $email = clean_text($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error .= '<p><label style="color: red">Email inválido.</label></p>';
        } else {
            // Consulta para verificar a existência do e-mail na base de dados
            $query_nome = "SELECT UserID, Nome FROM user WHERE email = '$email'";
            $result_nome = $conexao->query($query_nome);

            if (!$result_nome) {
                // Erro na execução da consulta
                $error .= '<p><label style="color: red">Erro na consulta SQL: ' . $conexao->error . '</label></p>';
            } elseif ($result_nome->num_rows == 1) {
                // E-mail encontrado na base de dados

                $row_nome = $result_nome->fetch_assoc();
                $nome_da_pessoa = $row_nome["Nome"];
                $user_id = $row_nome["UserID"];  

                // Criar um token único
                $token = bin2hex(random_bytes(32)); 
            
                // Inserir o token na base de dados
                $query_insere_token = "INSERT INTO tokens (token_user_id, token_gerado, token_hora, token_ativo) VALUES ('$user_id', '$token', NOW(), 1)";

                $conexao->query($query_insere_token);
            
                $link = "http://www.quickpark.pt/quickpark/recuperacao_senha.php?token=$token";

                $mail = new PHPMailer;
                $mail->isSMTP();
                $mail->SMTPDebug = 2;
                $mail->Host = 'smtp-mail.outlook.com';
                $mail->Port = 587;
                $mail->SMTPAuth = true;
                $mail->Username = 'quickpark.management@hotmail.com';
                $mail->Password = 'P5um6Dr96m32';
                $mail->setFrom('quickpark.management@hotmail.com', 'QuickPark');
                $mail->addReplyTo('quickpark.management@hotmail.com', 'QuickPark');
                $mail->addAddress($_POST["email"]);
                $mail->Subject = 'Recuperar de senha quickpark';
                $mail->Body = "Olá $nome_da_pessoa,\n\nVocê solicitou a recuperação de senha. Clique no link a seguir para criar uma nova senha:\n$link\n\nSe não foi você quem solicitou isso, ignore este e-mail.";
                if (!$mail->send()) {
                    echo 'Mailer Error: ' . $mail->ErrorInfo;
                } else {

                    header("Location: login.php");
                }

            } else {
                // E-mail não encontrado na base de dados
                echo 'O e-mail fornecido não está registado.';
            }
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
        <a href="index.html">
            <div name="logo-esquerda">
                <img src="imagens/pi_logo2.png" alt="">
            </div>
        </a>
        <p class="texto-principal">QuickPark</p>
        <div name="menu-direita">
            <a class="contact-us" href="contact_us.html">Contacte-nos</a>
        </div>
    </header>
    <div class="fade">
        <div class="registo">
            <h2 class="texto inicial">Recuperação de Senha</h2>
            <!-- Adicione o formulário de recuperação de senha aqui -->
            <form method="post">

                <label for="email">E-mail da sua conta:</label>
                <input type="email" class="registo-input" id="email" name="email" placeholder="Digite seu e-mail" value="<?php echo $email; ?>"> <br>

                <button type="submit" id="recuperar" name= "recuperar" class="botao_login">Enviar Email</button> <br>

            </form>
            <label>Recordou-se da senha? <a href="login.php">Faça login</a></label>
        </div>
    </div>
</body>
</html>
