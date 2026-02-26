<?php
// Código de Segurança: Garante que apenas usuários autenticados acessem esta página.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    // Armazena a mensagem de erro para que o login.php a exiba
    $_SESSION['erro_login'] = "Você precisa estar logado para acessar a área de gestão.";
    header('Location: login.php');
    exit;
}
?>
<?php 
// Localização: fluenzisite/produtos.php
include 'includes/header.php'; 
include 'includes/conexao.php'; 

$mensagem_sucesso = '';
$mensagem_erro = '';
$produto_para_editar = null;
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'listar';
$id_produto = isset($_GET['id']) ? intval($_GET['id']) : 0;

// -----------------------------------------------------------
// 1. Lógica de Exclusão (DELETE)
// -----------------------------------------------------------
if ($acao == 'excluir' && $id_produto > 0) {
    // Atenção: Se houver NF-e vinculada a este produto, a exclusão falhará (Foreign Key).
    // Em um sistema real, você bloquearia a exclusão ou faria uma exclusão em cascata.
    $sql_delete = "DELETE FROM produtos WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_produto);
    
    if ($stmt_delete->execute()) {
        $mensagem_sucesso = "Produto ID #$id_produto excluído com sucesso!";
        $acao = 'listar'; // Volta para a listagem
    } else {
        $mensagem_erro = "Erro ao excluir produto. Certifique-se de que não está vinculado a uma Nota Fiscal.";
    }
    $stmt_delete->close();
}

// -----------------------------------------------------------
// 2. Lógica de Criação e Edição (CREATE / UPDATE)
// -----------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $conn->real_escape_string($_POST['nome']);
    $preco = $conn->real_escape_string($_POST['preco']);
    $ncm = $conn->real_escape_string($_POST['ncm']);
    $post_acao = $_POST['acao'];

    if ($post_acao == 'adicionar_produto') {
        // CREATE
        $sql = "INSERT INTO produtos (nome, preco, ncm) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sds", $nome, $preco, $ncm); // 'sds' para string, decimal, string
        
        if ($stmt->execute()) {
            $mensagem_sucesso = "Produto cadastrado com sucesso!";
        } else {
            $mensagem_erro = "Erro ao cadastrar produto: " . $stmt->error;
        }
        $stmt->close();
        $acao = 'listar'; // Volta para a listagem
        
    } elseif ($post_acao == 'atualizar_produto') {
        // UPDATE
        $id_produto_update = intval($_POST['id']);
        $sql = "UPDATE produtos SET nome = ?, preco = ?, ncm = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdsi", $nome, $preco, $ncm, $id_produto_update);
        
        if ($stmt->execute()) {
            $mensagem_sucesso = "Produto ID #$id_produto_update atualizado com sucesso!";
        } else {
            $mensagem_erro = "Erro ao atualizar produto: " . $stmt->error;
        }
        $stmt->close();
        $acao = 'listar'; // Volta para a listagem
    }
}

// -----------------------------------------------------------
// 3. Lógica para buscar dados de um produto para Edição
// -----------------------------------------------------------
if ($acao == 'editar' && $id_produto > 0) {
    $sql_editar = "SELECT * FROM produtos WHERE id = ?";
    $stmt_editar = $conn->prepare($sql_editar);
    $stmt_editar->bind_param("i", $id_produto);
    $stmt_editar->execute();
    $result_editar = $stmt_editar->get_result();
    $produto_para_editar = $result_editar->fetch_assoc();
    $stmt_editar->close();
    
    if (!$produto_para_editar) {
        $mensagem_erro = "Produto não encontrado para edição.";
        $acao = 'listar';
    }
}

// Lógica para listar todos os produtos (READ)
$sql_listar_produtos = "SELECT * FROM produtos ORDER BY nome ASC";
$result_produtos = $conn->query($sql_listar_produtos);
?>

<div class="container content-page">
    <h2>Gerenciamento de Produtos</h2>

    <?php if ($mensagem_sucesso): ?>
        <p class='success-message'><?php echo $mensagem_sucesso; ?></p>
    <?php endif; ?>
    <?php if ($mensagem_erro): ?>
        <p class='error-message'><?php echo $mensagem_erro; ?></p>
    <?php endif; ?>

    <div class="form-section">
        <h3><?php echo ($acao == 'editar') ? 'Editar Produto' : 'Adicionar Novo Produto'; ?></h3>
        <form method="POST" action="produtos.php">
            <input type="hidden" name="acao" value="<?php echo ($acao == 'editar') ? 'atualizar_produto' : 'adicionar_produto'; ?>">
            
            <?php if ($acao == 'editar'): ?>
                <input type="hidden" name="id" value="<?php echo $produto_para_editar['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nome_produto">Nome:</label>
                <input type="text" id="nome_produto" name="nome" required 
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars($produto_para_editar['nome']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="preco_produto">Preço:</label>
                <input type="number" id="preco_produto" name="preco" step="0.01" min="0.01" required
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars($produto_para_editar['preco']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="ncm_produto">NCM:</label>
                <input type="text" id="ncm_produto" name="ncm" required maxlength="8"
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars($produto_para_editar['ncm']) : ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <?php echo ($acao == 'editar') ? 'Salvar Alterações' : 'Salvar Produto'; ?>
            </button>
            <?php if ($acao == 'editar'): ?>
                <a href="produtos.php" class="btn btn-secondary">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <?php if ($acao != 'editar'): ?>
    <div class="list-section">
        <h3>Produtos Cadastrados</h3>
        <?php if ($result_produtos->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Preço</th>
                        <th>NCM</th>
                        <th>Ações</th> </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_produtos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td>R$ <?php echo number_format($row['preco'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($row['ncm']); ?></td>
                            <td class="action-buttons">
                                <a href="produtos.php?acao=editar&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="produtos.php?acao=excluir&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este produto?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum produto cadastrado ainda.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php 
$conn->close(); 
include 'includes/footer.php'; 
?>