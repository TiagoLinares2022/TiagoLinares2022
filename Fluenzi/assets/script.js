// script.js - Para interatividade futura, como validação de formulário ou animações.
document.addEventListener('DOMContentLoaded', () => {
    console.log('Site Fluenzi carregado com sucesso!');

    // Exemplo de interatividade simples para o formulário de contato
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', (event) => {
            // Aqui você pode adicionar validações de JavaScript mais complexas
            // antes de enviar o formulário, se os atributos 'required' não forem suficientes.
            console.log('Formulário de contato enviado.');
        });
    }
});

// Esta função é chamada na página 'emitir_nfe.php' para adicionar novos itens na NF-e
let item_counter = 1; // Contador global para itens da NF-e
function adicionarItem() {
    item_counter++; // Incrementa o contador para o novo item
    const container = document.getElementById('itens_container'); // Container dos itens
    const novoItem = document.createElement('div'); // Cria um novo div para o item
    novoItem.classList.add('item-nfe', 'form-group'); // Adiciona classes CSS
    
    // HTML do novo item, incluindo um select para produtos
    // Note que o PHP precisa ser executado novamente para popular as opções de produto
    novoItem.innerHTML = `
        <label for="produto_${item_counter}">Produto:</label>
        <select id="produto_${item_counter}" name="produtos[${item_counter}][id]" required>
            <?php 
            // Esta parte PHP precisa ser executada quando 'emitir_nfe.php' é carregado.
            // Para adicionar itens dinamicamente via JS, você precisaria de uma API ou carregar todos os produtos no início.
            // Aqui, estamos re-populando as opções, o que funcionaria se a página fosse recarregada ou se todos os produtos fossem carregados inicialmente em um JS array.
            // Vamos assumir que para cada novo item, as opções são as mesmas, e o PHP já listou elas na página inicial.
            // O ideal para um sistema real seria fazer uma requisição AJAX para buscar os produtos.
            $produtos_result_for_js = $conn->query("SELECT * FROM produtos"); // Supondo que $conn esteja disponível
            $produtos_result_for_js->data_seek(0); // Resetar o ponteiro
            while($produto_js = $produtos_result_for_js->fetch_assoc()): ?>
                <option value="<?php echo $produto_js['id']; ?>"><?php echo $produto_js['nome']; ?> (R$ <?php echo number_format($produto_js['preco'], 2, ',', '.'); ?>)</option>
            <?php endwhile; ?>
        </select>
        <label for="quantidade_${item_counter}">Quantidade:</label>
        <input type="number" id="quantidade_${item_counter}" name="produtos[${item_counter}][quantidade]" min="1" value="1" required>
    `;
    container.appendChild(novoItem); // Adiciona o novo item ao container
}
