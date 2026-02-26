<?php
// Configurações do banco de dados
$servername = "localhost";
$username = "root"; // Altere se o seu usuário do MySQL for diferente
$password = "";     // Altere se você tiver uma senha para o MySQL
$dbname = "fluenzisite_db"; // O nome do banco de dados que criaremos

// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Conexão com o banco de dados falhou: " . $conn->connect_error);
}
?>