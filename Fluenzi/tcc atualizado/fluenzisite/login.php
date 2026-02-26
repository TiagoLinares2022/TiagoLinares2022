<?php 
// Localização: fluenzisite/login.php

// Inicia a sessão para lidar com mensagens de erro ou redirecionamento
session_start(); 

// Se o usuário já estiver logado, redireciona para a página principal de gestão (por exemplo, Clientes)
if (isset($_SESSION['usuario_id'])) {
    header('Location: clientes.php');
    exit;
}

// Configurações do banco de dados e includes básicos (se necessário, inclua conexao.php)
// Para o login, usaremos a autenticação.php, mas o layout precisa dos includes do header/footer.
// NOTA: header.php/footer.php precisam ser ligeiramente modificados (próximo passo)
include 'includes/header.php'; 

$mensagem_erro = isset($_SESSION['erro_login']) ? $_SESSION['erro_login'] : '';
// Limpa a variável de sessão de erro após exibir
unset($_SESSION['erro_login']);
?>

<div class="container content-page" style="max-width: 450px; margin-top: 50px; padding: 30px;">
    <h2>Acesso Restrito</h2>

    <?php if ($mensagem_erro): ?>
        <p class='error-message'><?php echo $mensagem_erro; ?></p>
    <?php endif; ?>

    <div class="form-section">
        <form method="POST" action="autenticar.php">
            
            <div class="form-group">
                <label for="username">Usuário (admin):</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Senha (123456):</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar</button>
        </form>
    </div>
</div>

<?php 
// O header.php original pode dar problema aqui se tentar iniciar a sessão novamente.
// Faremos o ajuste no header.php no próximo passo.
include 'includes/footer.php'; 
?>