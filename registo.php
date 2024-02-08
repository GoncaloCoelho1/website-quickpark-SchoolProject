<?php

$error = '';
$nome = '';
$email = '';
$telefone = '';
$matricula = '';
$senha = '';
$csenha = '';

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

//funcao para validar se as matriculas estao no formato correto
function validarMatricula($matricula) {

    $matricula = strtoupper($matricula); //coloca as letras para maiusculas
    
    $padroes = array(
        "/^\d{2}-[A-Z]{2}-\d{2}$/",
        "/^[A-Z]{2}-\d{2}-[A-Z]{2}$/",
        "/^[A-Z]{2}-[A-Z]{2}-\d{2}$/",
        "/^\d{2}-[A-Z]{2}-[A-Z]{2}$/",
        "/^\d{2}-\d{2}-[A-Z]{2}$/",
        "/^[A-Z]{2}-\d{2}-\d{2}$/"
    );

    //verifica se a matricula inserida tem algum padrao correto
    foreach ($padroes as $padrao) {
        if (preg_match($padrao, $matricula)) {
            return true;
        }
    }

    return false;
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

    if (empty($_POST["matricula"])) { //se estiver vazio dá erro

        $error .= '<p><label style="color: red">Introduza a matrícula no campo indicado.</label></p>';
    
    } else {//limpa o texto

        $matricula = clean_text($_POST["matricula"]);

        if (!validarMatricula($matricula)) { //valida a matricula

            $error .= '<p><label style="color: red">Matrícula inválida. Deve ter o formato correto.</label></p>';
        
        } else { //verifica se a matricula ja esta associado a alguma conta
            
            include_once("config.php");
            
            //query para ir buscar o user id de uma matricula
            $query_verifica_matricula_user = "SELECT UserID FROM vehicle WHERE Matricula = '$matricula'";
            $verificar_matricula_existente = $conexao->query($query_verifica_matricula_user);
            
            //se o resultado da query retornar uma linha existente sem id nulo entao aparece erro
            if ($verificar_matricula_existente->num_rows == 1){

                //vai pesquisar a matricula e ver se existe algum ID associado a essa matricula
                $matricula_sem_user = $verificar_matricula_existente->fetch_assoc();
                $id = $matricula_sem_user['UserID'];
                //se o id for nulo entao faz a inserecao do novo utilizador e depois faz o update associando a matricula a esse utilizador que acabou de criar conta
                if ($id === NULL) {
                    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                
                    $query_inserir_user = "INSERT INTO user (Nome, Email, Telefone, Password, Saldo, User_nivel) 
                                           VALUES ('$nome', '$email', '$telefone', '$senha', 0, 2)";
                
                    $query_atribuir_user_id = "UPDATE vehicle 
                                               SET UserID = (SELECT UserID FROM user WHERE Email = '$email' AND Password = '$senha')
                                               WHERE Matricula = '$matricula'";
                
                    if ($conexao->query($query_inserir_user) && $conexao->query($query_atribuir_user_id)) {
                        //se tiver sucesso ao inserir entao redireciona para a pagina de login
                        header("Location: login.php");
                        exit();
                    }
                }else{
                    $error .= '<p><label style="color: red">Esta matricula ja esta associada a uma conta.</label></p>';
                }
                
            }else{
                $erro = 0;
                
            }
        }
    }
}

//se nao houver erros, se o botao registar for clicado e se nao houver nenhuma matricula no sistema
if (empty($error) && isset($_POST["registar"]) && $erro == 0) { 
    
    include_once("config.php"); //ligaçao à bd

    $email = $_POST['email'];

    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); //encripta a senha

    //insere o novo user
    $query_user = "INSERT INTO user (Nome, Email, Telefone, Password, Saldo, User_nivel) 
                VALUES ('$nome', '$email', '$telefone', '$senha', 0, 2)";

    if ($conexao->query($query_user)) { //se a inserção da query for bem sucedida entra nesta condiçao

        //caso a insereçao seja em sucedida, insere também a matricula associada ao user id dessa mesma pessoa
        $query_vehicle = "INSERT INTO vehicle (UserID, Matricula)
                    SELECT UserID, UPPER('$matricula') FROM user WHERE Email = '$email' AND Password = '$senha'";

        
        if ($conexao->query($query_vehicle)) { // se a segunda inserção também foi bem sucedida, redireciona para a página de login

            header("Location: login.php");
            exit(); 

        } else { // Se a segunda inserção falhar dá erro
            
            $error .= '<p><label style="color: red">Erro ao inserir na base de dados (vehicle).</label></p>';
        }
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="registo.css">
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

                <label for="matricula">Matrícula:</label>
                <input type="text" class="registo-input" name="matricula" id="matricula" placeholder="Digite a sua Matrícula" value="<?php echo $matricula; ?>"><br>

                <label for="senha">Senha:</label>
                <input type="password" class="registo-input" name="senha" id="senha" placeholder="Digite sua senha"><br>

                <label for="confirmar-senha">Confirmar Senha:</label>
                <input type="password" class="registo-input" name="csenha" id="csenha" placeholder="Confirme sua senha" ><br>

                <button type="submit" id="registar" name="registar" class="">Registar</button> <br>

            </form>

            <label>Já tem conta? <a href="login.php">Então faça login</a></label>
        </div>
    </div>
</body>
</html>