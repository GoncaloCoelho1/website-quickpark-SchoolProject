<?php
include_once("config.php"); //ficheiro conexao com bd


function clean_text($string) { // funcao para limpar texto
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    return $string;
}

session_start(); //inicia uma sessao para o cliente

// Se não existir sessão, redirecione para a página de login
if ((!isset($_SESSION["email"]) == true) and (!isset($_SESSION["senha"]) == true)) {
    header("Location: login.php");
}

$currentEmail = $_SESSION["email"]; //atribui o email da sessao criada a uma variavel
$query_nome = "SELECT Nome FROM user WHERE Email = '$currentEmail'"; //seleciona o nome da pessoa através do email
$result = $conexao->query($query_nome);

if ($result && $result->num_rows == 1) { //se for encontrada a pessoa entao atribui o nome a uma variavel
    $row = $result->fetch_assoc();
    $nomeDoUsuario = $row['Nome'];
} else {
    $nomeDoUsuario = "Usuário Desconhecido";
}

$errorN = "";
$errorM = "";
$errorARV = "";

//se o botao alterar dados for clicado
if (isset($_POST["alterarDados"])) {
    //atribui o que foi inserido pelo utilizador a variaveis
    $newNome = clean_text($_POST["newNome"]);
    $newEmail = clean_text($_POST["newEmail"]);
    $newTelefone = clean_text($_POST["newTelefone"]);
    $newSenha = clean_text($_POST["newSenha"]);

    //declara variaveis falsas e se posteriormente existirem no codigo passam a verdadeiras
    $emailExists = false;
    $telefoneExists = false;

    if (!empty($newEmail)) { //se o email estiver preenchido entao procura o email na bd
        $checkEmailQuery = "SELECT * FROM user WHERE Email = '$newEmail'";
        $resultEmail = $conexao->query($checkEmailQuery);

        if ($resultEmail && $resultEmail->num_rows > 0) {
            $emailExists = true; //o email existe
        }
    }

    if (!empty($newTelefone)) { //se o numero de telemovel estiver preenchido entao procura na bd
        $checkTelefoneQuery = "SELECT * FROM user WHERE Telefone = '$newTelefone'";
        $resultTelefone = $conexao->query($checkTelefoneQuery);

        if ($resultTelefone && $resultTelefone->num_rows > 0) {
            $telefoneExists = true; //o numero de telefone existe
        }
    }

    //caso nao tenham entrado na condiçao em que existem entao vai retornar erro
    if ($emailExists) {
        $errorN .= '<p><label style="color: red">O email já está registrado.</label></p>';
    }

    if ($telefoneExists) {
        $errorN .= '<p><label style="color: red">O número de telefone já está registrado.</label></p>';
    }

    if (!$emailExists && !$telefoneExists) { //se ambos forem encontrados

        $updateFields = array();

        //se os campos estiverem preenchidos entao entram na variavel
        if (!empty($newNome)) {
            $updateFields[] = "Nome = '$newNome'";
        }
        if (!empty($newEmail)) {
            $updateFields[] = "Email = '$newEmail'";
            $_SESSION["email"] = $newEmail; // atualiza o email da sessao 
        }
        if (!empty($newTelefone)) {
            $updateFields[] = "Telefone = '$newTelefone'";
        }
        if (!empty($newSenha)) {
            $newSenhaHashed = password_hash($newSenha, PASSWORD_DEFAULT);
            $updateFields[] = "Password = '$newSenhaHashed'";
        }

        //se a variavel tiver informaçao de coisas para atualizar
        if (!empty($updateFields)) {
            $updateQuery = "UPDATE user SET " . implode(", ", $updateFields) . " WHERE Email = '$currentEmail'";
            if ($conexao->query($updateQuery)) {
                header("Location: cliente.php");
                exit();
            } else {
                $errorN .= '<p><label style="color: red">Erro na atualização dos dados: ' . $conexao->error . '</label></p>';
            }
        }
    }
}
//mostrar  os dados do cliente
$query_dados = "SELECT Nome, Email, Telefone FROM user WHERE Email = '$currentEmail'";
$result_dados = $conexao->query($query_dados);

if ($result_dados && $result_dados->num_rows == 1) {
    $query_dados = $result_dados->fetch_assoc();
    $nomeusuario = $query_dados['Nome'];
    $mailusuario = $query_dados['Email'];
    $telefoneusuario = $query_dados['Telefone'];
} else {
    $nomeDoUsuario = "Usuário Desconhecido";
    $emailDoUsuario = "";
    $telefoneDoUsuario = "";
}

//query para mostrar o saldo do cliente
$query_saldo = "SELECT Saldo FROM user WHERE Email = '$currentEmail'";
$result_saldo = $conexao->query($query_saldo);

if ($result_saldo && $result_saldo->num_rows == 1) {
    $row_saldo = $result_saldo->fetch_assoc();
    $saldoDoUsuario = $row_saldo['Saldo'];
}else {
    $saldoDoUsuario = 0; // Se não encontrar saldo, define um valor padrao
}

//se o botao para adicionar saldo for clicado
if (isset($_POST["adicionar_saldo"])) {
    // Verifica se o saldo_adicional é numérico e positivo
    $saldo_adicional = $_POST["saldo_adicional"];
    if (is_numeric($saldo_adicional) && $saldo_adicional > 0) {
        // Atualiza o saldo do usuário na base de dados
        $query_atualizar_saldo = "UPDATE user SET Saldo = Saldo + $saldo_adicional WHERE Email = '$currentEmail'";
        if ($conexao->query($query_atualizar_saldo)) {
            header("Location: cliente.php");
            exit();
        } else {
            $errorM .= '<p><label style="color: red">Erro ao atualizar o saldo: ' . $conexao->error . '</label></p>';
        }
    } else {
        $errorM .= '<p><label style="color: red">Digite um valor válido e positivo.</label></p>';
    }
}

//vai buscar o user id
$query_id = "SELECT UserID FROM user WHERE Email = '$currentEmail'";
$result_id = $conexao->query($query_id);

if ($result_id && $result_id->num_rows == 1) {
    $userID = $result_id->fetch_assoc();

    // Obter o ID do veículo a partir do user id
    $query_id_veiculo = "SELECT VehicleID FROM vehicle WHERE UserID = '" . $userID['UserID'] . "'";
    $result_veiculo = $conexao->query($query_id_veiculo);

    if ($result_veiculo !== false) {
        if ($result_veiculo->num_rows > 0) {
            // obter a tabela do historico de entradas e saidas
            $query_tabela = "SELECT ps.HoraEntrada, ps.HoraSaida, ps.Valor, TIMEDIFF(ps.HoraSaida, ps.HoraEntrada) AS TempoEstacionado, v.Matricula 
            FROM parkingsession ps LEFT JOIN vehicle v ON ps.VehicleID = v.VehicleID WHERE ps.VehicleID IN (SELECT VehicleID FROM vehicle WHERE UserID = '" . $userID['UserID'] . "')";
            $tabela = $conexao->query($query_tabela);
        } else {
            echo "Sem veículos associados a esta conta.";
        }
    } else {
        echo "Erro ao obter veículos: " . $conexao->error;
    }
} else {
    echo "Erro ao obter ID do cliente: " . $conexao->error;
}



$query_veiculos = "SELECT VehicleID, Matricula FROM vehicle WHERE UserID = '" . $userID['UserID'] . "'";
$result_veiculos = $conexao->query($query_veiculos);

//se o botao adicionar veiculo for clicado
if (isset($_POST["adicionarVeiculo"])) {
    
    //corrige a inserecao caso esteja mal feita
    $newMatricula = strtoupper(str_replace(' ', '-', $_POST["newMatricula"]));

    //se a matricula for preenchida faz a insereçao
    if (!empty($newMatricula)) {

        $insertVehicleQuery = "INSERT INTO vehicle (UserID, Matricula) VALUES ('" . $userID['UserID'] . "', '$newMatricula')";
        if ($conexao->query($insertVehicleQuery)) {
            header("Location: cliente.php");
            exit();
        } else {
            $errorARV .= '<p><label style="color: red">Erro ao adicionar veículo: ' . $conexao->error . '</label></p>';
        }
    }
}

//se o botao remover veiculo for clicado
if (isset($_POST["removerVeiculo"])) {
    $selectedVehicle = $_POST["selectedVehicle"];

    //se a variavel estiver preenchida apaga o veiculo
    if (!empty($selectedVehicle)) {
        $deleteVehicleQuery = "DELETE FROM vehicle WHERE VehicleID = '$selectedVehicle'";
        if ($conexao->query($deleteVehicleQuery)) {
            header("Location: cliente.php");
            exit();
        } else {
            $errorARV .= '<p><label style="color: red">Erro ao remover veículo: ' . $conexao->error . '</label></p>';
        }
    }
}

//vai buscar veiculos sem user associado
$query_veiculos_nao_associados = "SELECT VehicleID, Matricula FROM vehicle WHERE UserID IS NULL";
$result_veiculos_nao_associados = $conexao->query($query_veiculos_nao_associados);

//se o botao adicionar vciuclo for clicado
if (isset($_POST["associarVeiculo"])) {

    //recebe o valor selecionado
    $selectedVehicleNaoAssociado = $_POST["selectedVehicleNaoAssociado"];

    //se a variel estiver preenchida faz o update e adiciona o user id a essa matricula
    if (!empty($selectedVehicleNaoAssociado)) {
        // Atualize o UserID do veículo para associá-lo ao usuário
        $associarVeiculoQuery = "UPDATE vehicle SET UserID = '" . $userID['UserID'] . "' WHERE VehicleID = '$selectedVehicleNaoAssociado'";
        if ($conexao->query($associarVeiculoQuery)) {
            header("Location: cliente.php");
            exit();
        } else {
            $errorARV .= '<p><label style="color: red">Erro ao associar veículo: ' . $conexao->error . '</label></p>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickPark-Cliente</title>
    <link rel="shortcut icon" href="imagens/pi_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="cliente.css">
</head>
<body>
    <header class="menu-principal">
        <a href="index.html">
            <div name="logo-esquerda">
                <img src="pi_logo2.png" alt="">
            </div>
        </a>

        <?php
            //usar a variavel logado para dar as boas vindas à pessoa
            echo '<a class="texto-principal">Bem vindo <u>' . $nomeDoUsuario .'</u></a>';
        ?>

        <div class="menu-direita">
            <a href="login.php">
                <button class="logout"><strong>Logout</strong></button>
            </a>
        </div>
    </header>
    <div class="wrapper">

        <div class="fade">

            <div class= "top">
                
            <div class="dados">
                <div class="DADOS">
                    <strong>DADOS</strong>
                </div>
                <div class="dados1">
                    <div class="nome"><strong>Nome:</strong> <span id="nome"></span><?php echo $nomeusuario; ?><br></div>
                    <div class="email"><strong>E-mail:</strong> <span id="email"><?php echo $mailusuario; ?></span></div>
                </div>
                <div class="dados2">
                    <div class="telefone"><strong>Telefone:</strong> <span id="telefone"><?php echo $telefoneusuario; ?></span><br></div>
                </div>
            </div>

                <div class="saldo">
                <p id="saldoText">
                    <strong>Saldo:</strong> 
                    <!-- mostrar a cor do saldo se for negatio ou positivo -->
                    <span id="saldoValue" class="<?php echo ($saldoDoUsuario < 0) ? 'saldo-negativo' : 'saldo-positivo'; ?>">
                        <?php echo $saldoDoUsuario; ?>
                    </span> 
                    <strong>€</strong>
                </p>
                </div>
                <div class = "modalB">
                    <button class= "MB" id="myBtn"><strong>Open Modal</strong></button>
                    <!-- Modal -->
                    <div id="myModal" class="modal">
                        <!-- Modal content -->
                        <div class="modal-content">
                            <span class="close">&times;</span>
                            <form action="" method="post">
                                <label for="saldo_adicional">Adicionar saldo:</label>
                                <input type="text" name="saldo_adicional" id="saldo_adicional" placeholder="Quantia em €" required>
                                <button type="submit" name="adicionar_saldo">Enviar</button>
                                <?php echo $errorM; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class = "void"></div>

            </div>
        </div>

        <!-- botoes para abrir as tabs para alteracoes dos dados do cliente -->
        <div class="tab">
            <div class = "tab1"><button class="tablinks" onclick="openTab(event, 'alterar')"><strong>Alterar Dados</strong></button></div>
            <div class = "tab2"><button class="tablinks" onclick="openTab(event, 'adicionar_remover')"><strong>Adicionar/Remover Veículo</strong></button></div>
            <div class = "tab3"><button class="tablinks" onclick="openTab(event, 'historico')"><strong>Histórico</strong></button></div>
        </div>

        <!-- Tab content -->
        <div id="alterar" class="tabcontent" style="display:block;">

        <div class="alterar-dados">
            <strong>Alterar Dados</strong>
        </div>

            <form action="" method="post">
            <?php echo $errorN; ?>

            <div class="ND">
                <!-- Campos para os novos valores dos dados -->
                <div class="NN">
                    <label for="newNome"><strong>Novo Nome:</strong></label>
                    <input class = "nn" type="text" name="newNome" id="newNome" placeholder="Novo nome"><br>
                </div>

                <div class="NE">
                    <label for="newEmail"><strong>Novo E-mail:</strong></label>
                    <input class = "ne" type="email" name="newEmail" id="newEmail" placeholder="Novo e-mail">
                </div>

                <div class="NT">
                    <label for="newTelefone"><strong>Novo Telefone:</strong></label>
                    <input class = "nt" type="text" name="newTelefone" id="newTelefone" placeholder="Novo telefone"><br>
                </div>

                <div class="NS">
                    <label for="newSenha"><strong>Nova Senha:</strong></label>
                    <input class = "ns" type="password" name="newSenha" id="newSenha" placeholder="Nova senha">
                </div>
            </div>

            <div class="alterar2">
                <button class="botao" type="submit" name="alterarDados"><strong>Alterar Dados</strong></button>
            </div>

            </form>
        </div>

        <div id="adicionar_remover" class="tabcontent">

            <div class="AdicionarRemover">
                <strong>Adicionar/Remover Veículo</strong>
            </div>

            <?php echo $errorARV; ?>

            <form action="" method="post">
                <div class="veiculos">

                    <!-- Dropdown para veículos associados ao usuário -->
                    <div class="SV">
                        <label for="selectVeiculo"><strong>Selecionar Veículo:</strong></label>
                        <select class="sv" name="selectedVehicle" id="selectVeiculo">
                            <?php
                            while ($veiculo = mysqli_fetch_assoc($result_veiculos)) {
                                echo "<option value='" . $veiculo['VehicleID'] . "'>" . $veiculo['Matricula'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Dropdown para veículos não associados ao usuário -->
                    <div class="AM">
                        <label for="selectVeiculoNaoAssociado"><strong>Associar Veículo:</strong></label>
                        <select class="am" name="selectedVehicleNaoAssociado" id="selectVeiculoNaoAssociado">
                            <?php
                            while ($veiculoNaoAssociado = mysqli_fetch_assoc($result_veiculos_nao_associados)) {
                                echo "<option value='" . $veiculoNaoAssociado['VehicleID'] . "'>" . $veiculoNaoAssociado['Matricula'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="NM">
                        <label for="newMatricula"><strong>Nova Matrícula:</strong></label>
                        <input class="nm" type="text" name="newMatricula" id="newMatricula" placeholder="Nova matrícula">
                    </div>

                    <div class="AV">
                        <button class="botao" type="submit" name="adicionarVeiculo"><strong>Adicionar Veículo</strong></button>
                    </div>

                    <div class="RV">
                        <button class="botao" type="submit" name="removerVeiculo"><strong>Remover Veículo</strong></button>
                    </div>

                    <!-- Botão para associar veículo -->
                    <div class="AS">
                        <button class="botao" type="submit" name="associarVeiculo"><strong>Associar Veículo</strong></button>
                    </div>

                </div>
            </form>

        </div>
        <!-- mostrar a tabela historico -->
        <div id="historico" class="tabcontent">

            <div class="historico">
                <strong>Histórico</strong>
            </div>
            
            <table class="historico-table">
                <thread>
                    <tr>
                        <th>Matrícula</th>
                        <th>Hora de Entrada</th>
                        <th>Hora de Saída</th>
                        <th>Total de Tempo Estacionado</th>
                        <th>Preço de Estacionamento</th>
                    </tr>
                </thread>
                <tbody>
                    <?php
                    
                        while($entrada = mysqli_fetch_assoc($tabela)){
                            echo "<tr>";
                            echo "<td>" . $entrada['Matricula'] . "</td>";
                            echo "<td>" . $entrada['HoraEntrada'] . "</td>";
                            echo "<td>" . $entrada['HoraSaida'] . "</td>";
                            echo "<td>" . $entrada['TempoEstacionado'] . "</td>";
                            echo "<td>" . $entrada['Valor'] . " €</td>";
                            echo "</tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>


    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("myModal");

        // Get the button that opens the modal
        var btn = document.getElementById("myBtn");

        // Get the <span> element that closes the modal
        var span = document.querySelector(".close");

        // Check if modal, button, and span exist
        if (modal && btn && span) {
        // When the user clicks the button, open the modal
            btn.onclick = function() {
                modal.style.display = "block";
            }

            // When the user clicks on <span> (x), close the modal
            span.onclick = function() {
                modal.style.display = "none";
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                modal.style.display = "none";
                }
            }
        } else {
            console.error("Modal elements not found. Make sure the HTML structure is correct.");
        }

        function openTab(evt, tabName) {
            // Declare all variables
            var i, tabcontent, tablinks;

            // Get all elements with class="tabcontent" and hide them
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }

            // Get all elements with class="tablinks" and remove the class "active"
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }

            // Show the current tab, and add an "active" class to the button that opened the tab
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

    </script>

</body>
</html