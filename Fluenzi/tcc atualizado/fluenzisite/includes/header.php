<?php
// Localização: fluenzisite/includes/header.php

// 1. Inicia a sessão no início de todas as páginas
// É crucial que isso seja a primeira coisa a ser feita.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
$logado = isset($_SESSION['usuario_id']);
$nome_usuario = $logado ? $_SESSION['usuario_nome'] : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fluenzi - Gestão Financeira Digital para PMEs</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <a href="index.php" class="logo">Fluenzi</a>
            <nav>
                <a href="index.php">Início</a>
                <a href="sobre.php">Sobre Nós</a>
                <a href="funcionalidades.php">Funcionalidades</a>
                <a href="precos.php">Preços</a>
                
                <?php if ($logado): ?>
                    <a href="transacoes.php">Fluxo de Caixa</a>
                    <a href="clientes.php">Clientes</a>
                    <a href="produtos.php">Produtos</a>
                    <a href="emitir_nfe.php">Emitir NF-e</a>
                    <a href="ia_previsao.php">Previsão IA</a>
                <?php endif; ?>

                <a href="contato.php">Contato</a>
                
                <?php if ($logado): ?>
                    <span style="margin-left: 10px; color: var(--primary-color); font-weight: 600;">Olá, <?php echo htmlspecialchars(explode(' ', $nome_usuario)[0]); ?>!</span>
                    <a href="logout.php" class="btn btn-secondary">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Login</a>
                <?php endif; ?>

            </nav>
        </div>
    </header>
    <main>