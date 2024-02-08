<?php
session_start(); // Inicie a sessão no início do script

// Se não existir sessão, redirecione para a página de login
if ((!isset($_SESSION["email"]) == true) and (!isset($_SESSION["senha"]) == true)) {
    header("Location: login.php");
}

// Report errors
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Variáveis de erros e dados
$error = '';
$errorARV = "";
$dados_cliente = array();
$dados_veiculo = array();
$dados_sessao = array(); // Adicionado para evitar possíveis erros de variável indefinida
$Email = ''; // Define $Email fora do bloco condicional
$matriculas = '';

// Diretório de imagens e imagem selecionada
$imageDirectory = "./physical/capturas/";
$selectedImage = "";

// Função para limpar texto
function clean_text($string) {
    $string = trim($string);
    $string = stripslashes($string);
    $string = htmlspecialchars($string);
    return $string;
}

// Função para exibir opções de imagens num dropdown
function displayImageDropdown($imageDirectory) {
    // Busca os arquivos de imagem no diretório especificado
    $imageFiles = glob($imageDirectory . "{NR_*,SU_*.jpg}", GLOB_BRACE);

    // Cria um array associativo com os paths dos arquivos como chaves e os tempos de modificação dos arquivos como valores
    $fileModTimes = array();
    foreach ($imageFiles as $imageFile) {
        // Usa filemtime() para obter o tempo de modificação do arquivo
        $fileModTimes[$imageFile] = filemtime($imageFile);
    }

    // Ordena o array pelo tempo de modificação em ordem decrescente
    arsort($fileModTimes);

    // Verifica se o array está vazio
    if (empty($fileModTimes)) {
        echo "<option>Não foram encontradas imagens</option>";
    } else {
        // Percorre o array ordenado e exibe cada nome de arquivo no dropdown
        foreach ($fileModTimes as $imageFile => $modTime) {
            $imageName = basename($imageFile);
            echo "<option value='$imageName'>$imageName</option>";
        }
    }
}


// Função para obter matrículas não associadas a um utilizador
function fetchMatriculas($conexao) {
    $sql = "SELECT vehicle.Matricula
            FROM parkingsession
            JOIN vehicle ON parkingsession.VehicleID = vehicle.VehicleID
            WHERE parkingsession.Horasaida IS NULL
              AND vehicle.UserID IS NULL
              AND parkingsession.ManualPaymentID IS NULL;";
    $result = $conexao->query($sql);
    $matriculas = [];
    while ($row = $result->fetch_assoc()) 
	{
        $matriculas[] = $row['Matricula'];
    }
    return $matriculas;
}

// A certeficar-se de que a conexão com o banco de dados foi estabelecida
include_once("config.php");

// Lógica de pesquisa
if (isset($_POST["pesquisar"])) {
    // Captura e limpa o email fornecido
    $Email = clean_text($_POST['email']);
    // Query para obter informações do cliente e sessões de estacionamento associadas
    $query = "SELECT 
            u.Nome, 
            u.Email, 
            u.Telefone, 
            u.Saldo, 
            v.VehicleID, 
            v.Matricula,
            ps.HoraEntrada,
            ps.HoraSaida,
            ps.Valor,
            TIMEDIFF(ps.HoraSaida, ps.HoraEntrada) AS TempoEstacionado
          FROM user u
          LEFT JOIN vehicle v ON u.UserID = v.UserID
          LEFT JOIN parkingsession ps ON v.VehicleID = ps.VehicleID
          WHERE u.Email = '$Email'";

    // Executa a query
    $result = $conexao->query($query);

    if ($result) {
        if ($result->num_rows > 0) {
            // Limpe os dados_sessao existentes, se houver
            $_SESSION['dados_sessao'] = array();
        
            while ($row = $result->fetch_assoc()) {
                // Armazena temporariamente os dados na sessão (User)
                $_SESSION['dados_cliente'] = array(
                    'Nome' => $row['Nome'],
                    'Email' => $row['Email'],
                    'Telefone' => $row['Telefone'],
                    'Saldo' => $row['Saldo'],
                );
        
                // Armazena temporariamente os dados da sessão (Parkingsession)
                $dados_sessao[] = array(
                    'Matricula' => $row['Matricula'],
                    'HoraEntrada' => $row['HoraEntrada'],
                    'HoraSaida' => $row['HoraSaida'],
                    'Valor' => $row['Valor'],
                    'TempoEstacionado' => $row['TempoEstacionado'],
                );
        
                // Armazena todos os veículos associados ao cliente (Vehicle)
                $dados_veiculo[] = array(
                    'VehicleID' => $row['VehicleID'],
                    'Matricula' => $row['Matricula']
                );
            }
        
            // Atualiza a sessão com os dados dos veículos
            $_SESSION['dados_veiculo'] = $dados_veiculo;
        
            // Atualiza a sessão com os dados da sessão
            $_SESSION['dados_sessao'] = $dados_sessao;
        } else {
            // Limpa os resultados existentes da pesquisa anterior
            $_SESSION['dados_cliente'] = array();
            $_SESSION['dados_veiculo'] = array();
            $_SESSION['dados_sessao'] = array();
            $_SESSION['sem_resultados'] = "Sem resultados para o email fornecido.";
        }
    } else {
        // Se houver um erro na consulta SQL, exibe a mensagem de erro
        echo "Erro na consulta: " . $conexao->error;
    }
}

// Query para obter veículos não associados a um utilizador
$query_veiculos_nao_associados = "SELECT VehicleID, Matricula FROM vehicle WHERE UserID IS NULL";
$result_veiculos_nao_associados = $conexao->query($query_veiculos_nao_associados);

// Lógica para efetuar um pagamento manual
if (isset($_POST["efetuarPagamento"])) {
    $matriculas = $_POST["matriculas"];
    print_r($matriculas);
    // Query para inserir pagamento manual
    $insertManualpaymentQuery = "INSERT INTO manualpayment (VehicleID, HoraPagamento)
                                SELECT v.VehicleID, NOW())
                                FROM vehicle v
                                WHERE v.Matricula = '$matriculas'";

    $conexao->query($insertManualpaymentQuery);

} else {
    $errorARV .= '<p><label style="color: red">Erro ao efetuar pagamento: ' . $conexao->error . '</label></p>';         
}

// Obtém a lista de matrículas
$matriculasList = fetchMatriculas($conexao);

// Lógica para registar pagamento manual
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registerPayment'])) {
    $selectedMatricula = $_POST['matricula'];
    $selectedImage = $_POST['image'];

    // Regista o pagamento manual na base de dados
    $insertSql = "INSERT INTO manualpayment (VehicleID, HoraPagamento)
                  SELECT vehicle.VehicleID, NOW()
                  FROM vehicle
                  WHERE vehicle.Matricula = '$selectedMatricula';";
    if ($conexao->query($insertSql)) {
        echo "<p>Pagamento manual registado para $selectedMatricula.</p>";

        // Renomeia a imagem selecionada
        $selectedImagePath = $imageDirectory . $selectedImage;
        $newImageName = preg_replace('/^(NR_|SU_)/', 'PM_', $selectedImage);
        $newImagePath = $imageDirectory . $newImageName;
        if (rename($selectedImagePath, $newImagePath)) {
            echo "<p>Imagem renomeada para PM com sucesso.</p>";
        } else {
            echo "<p>Erro: Não foi possível renomear a imagem para PM_.</p>";
        }
    } else {
        echo "<p>Erro: " . $conexao->error . "</p>";
    }
}

$renameSuccess = false;

// Lógica para renomear imagens
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['image'])) {
        $selectedImage = $_POST['image'];
        $selectedImagePath = $imageDirectory . $selectedImage;

        if (isset($_POST['rename'])) {
            // Lógica de renomear para "Não é veículo"
            $newImageName = str_replace("NR_", "NV_", $selectedImage);
            $newImagePath = $imageDirectory . $newImageName;
            if (rename($selectedImagePath, $newImagePath)) {
                $renameSuccess = true;
            } else {
                echo "<p>Error: Unable to rename the image to NV.</p>";
            }
            // Redireciona para a mesma página para evitar a resubmissão do formulário
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parque de Estacionamento - Gestão do Parque</title>
    <link rel="shortcut icon" href="imagens/pi_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="gestao.css">
    

    <a href="login.php">
        <button class="logout"><strong>Logout</strong></button>
    </a>
</head>
<body>
    <header>
        <h1 class="h1" id="h1">Gestão do Parque</h1>
    </header>

    <hr class="linha-divisoria-cima">

    <div class="lista_topicos">
        <img class="imagem-estacionamento" src="imagens\parking_gestao.jpg" alt="">    
        <div class="conteudo-wrapper">
            <div class="caixa-conteudo">
                <h2>Bem Vindo</h2>
                <p>Esta página realiza a pesquisa e exibição das informações dos clientes com base no email fornecido. O site de gestão também permite inserir registros de transações manuais no banco de dados por meio de um formulário para os utilizadores não familiarizados com o nosso serviço.</p>
                <a href="registoadministrador.php"><button type="submit" name="registo" class="regi">Inserir novo administrador</button></a> 
            </div>
        </div>
    </div>

    <hr class="linha-divisoria-baixo">
    
    <div class="perfil" id="perfil">
        <h2>Perfil do Cliente</h2>
        <div class="conteudo-wrapper">
            <div class="caixa-conteudo-perfil perfil-conteudo">
                <div class="conteudo-esquerda">
                    <form action="gestao.php" method="post" class="barra-de-pesquisa <?php echo isset($_SESSION['sem_resultados']) ? 'error' : ''; ?>">
                        <input type="text" name="email" placeholder="Pesquisar por Email">
                        <button type="submit" name="pesquisar">Pesquisar</button>
                        <div class="error-message"><?php echo isset($_SESSION['sem_resultados']) ? $_SESSION['sem_resultados'] : ''; ?></div>
                    </form>
                        <?php
                        unset($_SESSION['sem_resultados']);
                        ?>
                        <?php if (!empty($_SESSION['dados_cliente'])) : ?>
                            <h4><span class="coluna-nome">Nome Completo:</span> <span class="dados"><?php echo $_SESSION['dados_cliente']['Nome']; ?></span></h4>
                            <h4><span class="coluna-nome">Email:</span> <span class="dados"><?php echo $_SESSION['dados_cliente']['Email']; ?></span></h4>
                            <h4><span class="coluna-nome">Telefone:</span> <span class="dados"><?php echo $_SESSION['dados_cliente']['Telefone']; ?></span></h4>
                            <h4><span class="coluna-nome">Saldo:</span> <span class="dados"><?php echo $_SESSION['dados_cliente']['Saldo']; ?> €</span></h4>
                        <?php else : ?>
                            <h4>Nome Completo:</h4>
                            <h4>Email:</h4>
                            <h4>Telefone:</h4>
                            <h4>Saldo:</h4>
                        <?php endif; ?>
                </div>
                <div class="linha-vertical"></div>
                <div class="conteudo-direita">                        
                    <div class="table-container">
                        <table id="historico-table" class="historico-table">
                            <thead>
                                <tr>
                                    <th>Matrícula</th>
                                    <th>Hora de Entrada</th>
                                    <th>Hora de Saída</th>
                                    <th>Tempo Estacionado</th>
                                    <th>Custo pelo Estacionamento</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            if (isset($_SESSION['dados_sessao']) && is_array($_SESSION['dados_sessao'])) {
                                foreach ($_SESSION['dados_sessao'] as $entrada) : ?>
                                    <tr>
                                        <td><?php echo $entrada['Matricula']; ?></td>
                                        <td><?php echo $entrada['HoraEntrada']; ?></td>
                                        <td><?php echo $entrada['HoraSaida']; ?></td>
                                        <td><?php echo $entrada['TempoEstacionado']; ?></td>
                                        <td><?php echo isset($entrada['Valor']) ? $entrada['Valor'] . ' €' : ''; ?></td>
                                    </tr>
                                <?php endforeach; 
                            } else {
                                echo "<tr><td colspan='5'>Não existem dados disponíveis.</td></tr>";
                            }
                            ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>    

                <div class="titulo-container">
                    <h2 class="utilizador">Pagamentos Manuais</h2>
                </div>
                <div class="conteudo-matriculas">
                    <div class="image-container">
                            <h2>Visualizador de imagens</h2>
                            <form action="" method="post">
                                <label for="image-select">Seleccione uma Imagem:</label>
                                <select name="image" id="image-select">
                                    <?php displayImageDropdown($imageDirectory); ?>
                                </select>
                                <input type="submit" name="refresh" value="Atualizar" class="button"> <!-- Refresh button -->
                                <input type="submit" name="view" value="Ver Imagem">
                                <br><br>
                                <input type="submit" name="rename" value="Não é veículo" class="button">
                            </form>
                            <br>
                            <?php
                            if (!empty($selectedImage) && file_exists($selectedImagePath)) {
                                echo "<img src='$selectedImagePath' alt='$selectedImage' style='max-width: 800px; max-height: 600px;'>";
                            } elseif (!empty($selectedImage)) {
                                echo "<p>Image not found.</p>";
                            }
                            ?>
                        </div>

                        <div class="form-container">
                            <h2>Registo manual de movimentos</h2>
                            <form action="/quickpark/physical/matriculas.php" method="post">
                                <div>
                                    <label for="licensePlate">Matrícula:</label>
                                    <input type="text" id="licensePlate" name="licensePlate">
                                </div>
                                <br>
                                <div>
                                    <label for="origin">Origem:</label>
                                    <select id="origin" name="origin">
                                        <option value="entrada">Entrada</option>
                                        <option value="saida">Saida</option>
                                    </select>
                                </div>
                                <br>
                                <div class="buttons">
                                    <input type="submit" class="button" value="Inserir Matricula">
                                    <input type="hidden" name="selectedImage" value="<?php echo htmlspecialchars($selectedImage); ?>">
                                </div>
                            </form>
                        </div>

                        <div class="payment-container">
                            <h2>Registar Pagamento Manual</h2>
                            <form action="" method="post">
                                <!-- Exibe a lista de matrículas no formulário de registro de pagamento manual -->
                                <select name="matricula" id="matricula">
                                    <option value="">Selecione uma matrícula</option>
                                    <?php foreach ($matriculasList as $matricula): ?>
                                        <option value="<?= htmlspecialchars($matricula) ?>"><?= htmlspecialchars($matricula) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <br><br>
                                <input type="submit" name="refreshMatriculas" value="Refresh Matriculas" class="button"> <!-- Limpa o conteúdo com o botão Matriculas -->
                                <input type="hidden" name="image" value="<?= htmlspecialchars($selectedImage) ?>"> <!-- Inclui o nome da imagem selecionada -->
                                <input type="submit" name="registerPayment" value="Registar Pagamento Manual" class="button"> <!-- Faz o registo manual -->
                            </form>

                        </div>
                        <!-- Exibe uma mensagem de sucesso se a imagem foi renomeada com sucesso -->
                        <?php
                        if ($renameSuccess) {
                            echo "<p>Imagem renameda para NV com sucesso.</p>";
                        }
                        ?>
                    </div>
                </div>

    <footer>
        <p>&copy; 2023 Parque de Estacionamento "QuickPark" SA. Todos os direitos reservados.</p>
    </footer>
</body>
</html>