<?php
//definir as variaveis com a conexao ao servidor
$host = "www.quickpark.pt";
$user = "quickparkdb";
$password = "Mafra#2023";
$base_dados = "db_quickpark";

//efetua a conexao
$conexao = mysqli_connect($host, $user, $password, $base_dados);

//se nao existir uma conexao entao mostra erro
if (!$conexao) {
    die("Erro na conexão com a base de dados: " . mysqli_connect_error());
}

?>