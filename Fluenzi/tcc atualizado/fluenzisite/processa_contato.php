<?php
// Verifica se o método da requisição é POST (ou seja, se o formulário foi enviado)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário para evitar ataques XSS
    $nome = htmlspecialchars($_POST['nome']);
    $email = htmlspecialchars($_POST['email']);
    $mensagem = htmlspecialchars($_POST['mensagem']);
    
    // --- SIMULAÇÃO ---
    // Em um sistema real, aqui você faria o seguinte:
    // 1. Enviar um e-mail para o administrador (usando PHPMailer ou função mail())
    // 2. Salvar os dados do contato em um banco de dados
    // 3. Integrar com um CRM

    // Para este exemplo do TCC, apenas exibimos uma mensagem de sucesso.
    echo "<!DOCTYPE html>";
    echo "<html lang='pt-br'>";
    echo "<head><meta charset='UTF-8'><title>Mensagem Enviada</title><link rel='stylesheet' href='assets/css/style.css'></head>";
    echo "<body><main><div class='container' style='text-align: center; padding: 50px;'>";
    echo "<h1>Mensagem Recebida com Sucesso!</h1>";
    echo "<p>Olá, <strong>$nome</strong>. Agradecemos seu contato. Responderemos em breve para o e-mail <strong>$email</strong>.</p>";
    echo "<p>Sua mensagem: \"<em>$mensagem</em>\"</p>";
    echo "<a href=\"index.php\" class=\"btn\">Voltar para a página inicial</a>";
    echo "</div></main></body></html>";

} else {
    // Se alguém tentar acessar este script diretamente sem enviar o formulário,
    // redireciona para a página de contato.
    header("Location: contato.php");
    exit();
}
?>
