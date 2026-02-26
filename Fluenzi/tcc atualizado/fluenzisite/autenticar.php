<?php
// Localização: fluenzisite/autenticar.php
session_start();
include 'includes/conexao.php'; // Conexão com o BD

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Coleta os dados do formulário
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // 2. Busca o usuário no banco de dados
    $sql = "SELECT id, username, password, nome FROM usuarios WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // 3. Verifica a senha
        // NOTA IMPORTANTE: Para o TCC (senha de texto simples), usamos comparação direta:
        if ($password === $usuario['password']) { 
            // Senha correta: Inicia a sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_username'] = $usuario['username'];

            // Redireciona para uma página de gestão (ex: Fluxo de Caixa)
            header('Location: transacoes.php');
            exit;
        } else {
            // Senha incorreta
            $_SESSION['erro_login'] = "Usuário ou senha inválidos. Tente novamente.";
            header('Location: login.php');
            exit;
        }
    } else {
        // Usuário não encontrado
        $_SESSION['erro_login'] = "Usuário ou senha inválidos. Tente novamente.";
        header('Location: login.php');
        exit;
    }

    $stmt->close();
} else {
    // Acesso direto ao script sem POST
    header('Location: login.php');
    exit;
}
$conn->close();
?>