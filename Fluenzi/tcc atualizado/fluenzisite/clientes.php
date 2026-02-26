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
// Localização: fluenzisite/clientes.php
include 'includes/header.php'; 
include 'includes/conexao.php'; 

$mensagem_sucesso = '';
$mensagem_erro = '';
$cliente_para_editar = null;
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'listar';
$id_cliente = isset($_GET['id']) ? intval($_GET['id']) : 0;

// -----------------------------------------------------------
// 1. Lógica de Exclusão (DELETE)
// -----------------------------------------------------------
if ($acao == 'excluir' && $id_cliente > 0) {
    // É importante deletar qualquer registro relacionado (como notas fiscais) primeiro,
    // ou configurar o FOREIGN KEY ON DELETE CASCADE no BD.
    // Para simplicidade do TCC, faremos a exclusão direta do cliente.
    $sql_delete = "DELETE FROM clientes WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_cliente);
    
    if ($stmt_delete->execute()) {
        $mensagem_sucesso = "Cliente ID #$id_cliente excluído com sucesso!";
        $acao = 'listar'; // Volta para a listagem
    } else {
        $mensagem_erro = "Erro ao excluir cliente: " . $stmt_delete->error;
    }
    $stmt_delete->close();
}

// -----------------------------------------------------------
// 2. Lógica de Criação e Edição (CREATE / UPDATE)
// -----------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $conn->real_escape_string($_POST['nome']);
    $cpf_cnpj = $conn->real_escape_string($_POST['cpf_cnpj']);
    $email = $conn->real_escape_string($_POST['email']);
    $post_acao = $_POST['acao'];

    if ($post_acao == 'adicionar_cliente') {
        // CREATE
        $sql = "INSERT INTO clientes (nome, cpf_cnpj, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nome, $cpf_cnpj, $email);
        
        if ($stmt->execute()) {
            $mensagem_sucesso = "Cliente cadastrado com sucesso!";
        } else {
            $mensagem_erro = "Erro ao cadastrar cliente: " . $stmt->error;
        }
        $stmt->close();
        $acao = 'listar'; // Volta para a listagem
        
    } elseif ($post_acao == 'atualizar_cliente') {
        // UPDATE
        $id_cliente_update = intval($_POST['id']);
        $sql = "UPDATE clientes SET nome = ?, cpf_cnpj = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nome, $cpf_cnpj, $email, $id_cliente_update);
        
        if ($stmt->execute()) {
            $mensagem_sucesso = "Cliente ID #$id_cliente_update atualizado com sucesso!";
        } else {
            $mensagem_erro = "Erro ao atualizar cliente: " . $stmt->error;
        }
        $stmt->close();
        $acao = 'listar'; // Volta para a listagem
    }
}

// -----------------------------------------------------------
// 3. Lógica para buscar dados de um cliente para Edição
// -----------------------------------------------------------
if ($acao == 'editar' && $id_cliente > 0) {
    $sql_editar = "SELECT * FROM clientes WHERE id = ?";
    $stmt_editar = $conn->prepare($sql_editar);
    $stmt_editar->bind_param("i", $id_cliente);
    $stmt_editar->execute();
    $result_editar = $stmt_editar->get_result();
    $cliente_para_editar = $result_editar->fetch_assoc();
    $stmt_editar->close();
    
    if (!$cliente_para_editar) {
        $mensagem_erro = "Cliente não encontrado para edição.";
        $acao = 'listar';
    }
}

// Lógica para listar todos os clientes (READ)
$sql_listar_clientes = "SELECT * FROM clientes ORDER BY nome ASC";
$result_clientes = $conn->query($sql_listar_clientes);
?>

<div class="container content-page">
    <h2>Gerenciamento de Clientes</h2>

    <?php if ($mensagem_sucesso): ?>
        <p class='success-message'><?php echo $mensagem_sucesso; ?></p>
    <?php endif; ?>
    <?php if ($mensagem_erro): ?>
        <p class='error-message'><?php echo $mensagem_erro; ?></p>
    <?php endif; ?>

    <div class="form-section">
        <h3><?php echo ($acao == 'editar') ? 'Editar Cliente' : 'Adicionar Novo Cliente'; ?></h3>
        <form method="POST" action="clientes.php">
            <input type="hidden" name="acao" value="<?php echo ($acao == 'editar') ? 'atualizar_cliente' : 'adicionar_cliente'; ?>">
            
            <?php if ($acao == 'editar'): ?>
                <input type="hidden" name="id" value="<?php echo $cliente_para_editar['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nome_cliente">Nome:</label>
                <input type="text" id="nome_cliente" name="nome" required 
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars($cliente_para_editar['nome']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="cpf_cnpj_cliente">CPF/CNPJ:</label>
                <input type="text" id="cpf_cnpj_cliente" name="cpf_cnpj" required
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars($cliente_para_editar['cpf_cnpj']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email_cliente">E-mail:</label>
                <input type="email" id="email_cliente" name="email" required
                       value="<?php echo ($acao == 'editar') ? htmlspecialchars($cliente_para_editar['email']) : ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <?php echo ($acao == 'editar') ? 'Salvar Alterações' : 'Salvar Cliente'; ?>
            </button>
            <?php if ($acao == 'editar'): ?>
                <a href="clientes.php" class="btn btn-secondary">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <hr>

    <?php if ($acao != 'editar'): ?>
    <div class="list-section">
        <h3>Clientes Cadastrados</h3>
        <?php if ($result_clientes->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CPF/CNPJ</th>
                        <th>E-mail</th>
                        <th>Ações</th> </tr>
                </thead>
                <tbody>
                    <?php while($row = $result_clientes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['cpf_cnpj']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="action-buttons">
                                <a href="clientes.php?acao=editar&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="clientes.php?acao=excluir&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este cliente?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum cliente cadastrado ainda.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php 
$conn->close(); 
include 'includes/footer.php'; 
?>