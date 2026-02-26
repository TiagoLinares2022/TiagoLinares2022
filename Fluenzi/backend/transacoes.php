<?php 
// Localização: fluenzisite/transacoes.php

// ==========================================================
// CÓDIGO DE SEGURANÇA: RESTRIGE ACESSO A USUÁRIOS LOGADOS
// ==========================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['erro_login'] = "Você precisa estar logado para acessar a área de Fluxo de Caixa.";
    header('Location: login.php');
    exit;
}
// ==========================================================
// FIM DO CÓDIGO DE SEGURANÇA
// ==========================================================

include 'includes/header.php'; 
include 'includes/conexao.php'; 

$mensagem_sucesso = '';
$mensagem_erro = '';

// Variáveis para edição
$transacao_para_editar = null;
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'listar';
$id_transacao = isset($_GET['id']) ? intval($_GET['id']) : 0;

// -----------------------------------------------------------
// 1. Lógica de Exclusão (DELETE)
// -----------------------------------------------------------
if ($acao == 'excluir' && $id_transacao > 0) {
    $sql_delete = "DELETE FROM transacoes WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_transacao);
    
    if ($stmt_delete->execute()) {
        $mensagem_sucesso = "Transação ID #$id_transacao excluída com sucesso!";
        $acao = 'listar'; 
    } else {
        $mensagem_erro = "Erro ao excluir transação: " . $stmt_delete->error;
    }
    $stmt_delete->close();
}

// -----------------------------------------------------------
// 2. Lógica de Adição, Edição e Liquidação (CREATE / UPDATE)
// -----------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $valor = floatval($_POST['valor']);
    $tipo = $conn->real_escape_string($_POST['tipo']); // 'receita' ou 'despesa'
    $data_vencimento = $conn->real_escape_string($_POST['data_vencimento']);
    $liquidado = isset($_POST['liquidado']) ? 1 : 0;
    $post_acao = $_POST['acao'];

    // Ajusta o valor para ser negativo se for despesa
    if ($tipo == 'despesa' && $valor > 0) {
        $valor = -$valor;
    } elseif ($tipo == 'receita' && $valor < 0) {
        $valor = abs($valor);
    }
    
    if ($post_acao == 'adicionar_transacao') {
        // CREATE
        $sql = "INSERT INTO transacoes (descricao, valor, tipo, data_vencimento, liquidado) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdssi", $descricao, $valor, $tipo, $data_vencimento, $liquidado);
        
        if ($stmt->execute()) {
            $mensagem_sucesso = "Transação cadastrada com sucesso!";
        } else {
            $mensagem_erro = "Erro ao cadastrar transação: " . $stmt->error;
        }
        $stmt->close();
        $acao = 'listar'; 
        
    } elseif ($post_acao == 'atualizar_transacao') {
        // UPDATE
        $id_transacao_update = intval($_POST['id']);
        $sql = "UPDATE transacoes SET descricao = ?, valor = ?, tipo = ?, data_vencimento = ?, liquidado = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdssii", $descricao, $valor, $tipo, $data_vencimento, $liquidado, $id_transacao_update);
        
        if ($stmt->execute()) {
            $mensagem_sucesso = "Transação ID #$id_transacao_update atualizada com sucesso!";
        } else {
            $mensagem_erro = "Erro ao atualizar transação: " . $stmt->error;
        }
        $stmt->close();
        $acao = 'listar'; 
    }
}

// Lógica para liquidação rápida (GET)
if ($acao == 'liquidar' && $id_transacao > 0) {
    $sql_liquidar = "UPDATE transacoes SET liquidado = 1 WHERE id = ?";
    $stmt_liquidar = $conn->prepare($sql_liquidar);
    $stmt_liquidar->bind_param("i", $id_transacao);
    if ($stmt_liquidar->execute()) {
        $mensagem_sucesso = "Transação #$id_transacao liquidada com sucesso!";
    } else {
        $mensagem_erro = "Erro ao liquidar transação.";
    }
    $stmt_liquidar->close();
    $acao = 'listar'; // Volta para a listagem
}


// -----------------------------------------------------------
// 3. Lógica para buscar dados de uma transação para Edição
// -----------------------------------------------------------
if ($acao == 'editar' && $id_transacao > 0) {
    $sql_editar = "SELECT * FROM transacoes WHERE id = ?";
    $stmt_editar = $conn->prepare($sql_editar);
    $stmt_editar->bind_param("i", $id_transacao);
    $stmt_editar->execute();
    $result_editar = $stmt_editar->get_result();
    $transacao_para_editar = $result_editar->fetch_assoc();
    $stmt_editar->close();
    
    if (!$transacao_para_editar) {
        $mensagem_erro = "Transação não encontrada para edição.";
        $acao = 'listar';
    } else {
        // Se for despesa, converte o valor de volta para positivo para exibir no input
        if ($transacao_para_editar['tipo'] == 'despesa') {
            $transacao_para_editar['valor'] = abs($transacao_para_editar['valor']);
        }
    }
}


// Lógica para listar todas as transações e calcular o saldo (READ)
$sql_listar_transacoes = "SELECT * FROM transacoes ORDER BY data_vencimento DESC";
$result_transacoes = $conn->query($sql_listar_transacoes);

// Cálculo do saldo total
$sql_saldo = "SELECT SUM(valor) as saldo FROM transacoes WHERE liquidado = 1";
$result_saldo = $conn->query($sql_saldo);
$saldo_total = $result_saldo->fetch_assoc()['saldo'] ?? 0;
?>

<div class="container content-page">
    <h2>Fluxo de Caixa (Transações)</h2>

    <?php if ($mensagem_sucesso): ?>
        <p class='success-message'><?php echo $mensagem_sucesso; ?></p>
    <?php endif; ?>
    <?php if ($mensagem_erro): ?>
        <p class='error-message'><?php echo $mensagem_erro; ?></p>
    <?php endif; ?>

    <div class="saldo-total">
        <strong>SALDO ATUAL (Liquidado):</strong> 
        <span class="<?php echo $saldo_total >= 0 ? 'text-success' : 'text-danger'; ?>">
            R$ <?php echo number_format($saldo_total, 2, ',', '.'); ?>
        </span>
    </div>

    <div class="form-section">
        <h3><?php echo ($acao == 'editar') ? 'Editar Transação' : 'Adicionar Nova Transação'; ?></h3>
        <form method="POST" action="transacoes.php">
            <input type="hidden" name="acao" value="<?php echo ($acao == 'editar') ? 'atualizar_transacao' : 'adicionar_transacao'; ?>">
            
            <?php if ($acao == 'editar'): ?>
                <input type="hidden" name="id" value="<?php echo $transacao_para_editar['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <input type="text" id="descricao" name="descricao" required 
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars($transacao_para_editar['descricao']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="valor">Valor (Apenas o número positivo):</label>
                <input type="number" id="valor" name="valor" step="0.01" min="0.01" required
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars($transacao_para_editar['valor']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required>
                    <option value="receita" <?php echo ($acao == 'editar' && $transacao_para_editar['tipo'] == 'receita') ? 'selected' : ''; ?>>Receita</option>
                    <option value="despesa" <?php echo ($acao == 'editar' && $transacao_para_editar['tipo'] == 'despesa') ? 'selected' : ''; ?>>Despesa</option>
                </select>
            </div>

            <div class="form-group">
                <label for="data_vencimento">Data de Vencimento/Previsão:</label>
                <input type="date" id="data_vencimento" name="data_vencimento" required
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars(date('Y-m-d', strtotime($transacao_para_editar['data_vencimento']))) : date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <input type="checkbox" id="liquidado" name="liquidado" 
                       <?php echo ($acao == 'editar' && $transacao_para_editar['liquidado']) ? 'checked' : ''; ?>>
                <label for="liquidado" style="display: inline;">Esta transação está liquidada/paga?</label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <?php echo ($acao == 'editar') ? 'Salvar Alterações' : 'Salvar Transação'; ?>
            </button>
            <?php if ($acao == 'editar'): ?>
                <a href="transacoes.php" class="btn btn-secondary">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <?php if ($acao != 'editar'): ?>
    <div class="list-section">
        <h3>Transações (Pendentes e Liquidadas)</h3>
        <?php if ($result_transacoes->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vencimento</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_transacoes->fetch_assoc()): ?>
                        <tr class="<?php echo $row['liquidado'] ? 'liquidada' : 'pendente'; ?>">
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['data_vencimento'])); ?></td>
                            <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                            <td><?php echo ucfirst($row['tipo']); ?></td>
                            <td class="<?php echo $row['valor'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                R$ <?php echo number_format(abs($row['valor']), 2, ',', '.'); ?>
                            </td>
                            <td>
                                <?php if ($row['liquidado']): ?>
                                    <span class="text-success">LIQUIDADA</span>
                                <?php else: ?>
                                    <span class="text-danger">PENDENTE</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="transacoes.php?acao=editar&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                <?php if (!$row['liquidado']): ?>
                                    <a href="transacoes.php?acao=liquidar&id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Confirmar liquidação desta transação?');">Liquidar</a>
                                <?php endif; ?>
                                <a href="transacoes.php?acao=excluir&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta transação?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhuma transação cadastrada ainda.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php 
$conn->close(); 
include 'includes/footer.php'; 
?>