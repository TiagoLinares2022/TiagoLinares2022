<?php
// 1. SEGURANÇA E INCLUSÕES
include 'includes/header.php'; 
include 'includes/conexao.php'; 
// A segurança de sessão está em header.php, então está OK.

$historico_json = '[]';
$previsao_json = '[]';
$erro_ia = null;
$dados_para_python = [];

// 2. LÓGICA DE EXTRAÇÃO E AGREGAÇÃO DOS DADOS DO BANCO DE DADOS
// O modelo de IA precisa do fluxo de caixa líquido por dia.
// Usamos SUM com CASE para calcular (Receita - Despesa) por data de pagamento.
$sql_fluxo = "
    SELECT 
        data_pagamento AS data,
        SUM(CASE 
            WHEN tipo = 'receita' THEN valor 
            WHEN tipo = 'despesa' THEN -valor 
            ELSE 0 
        END) AS valor_liquido
    FROM transacoes
    WHERE liquidado = 1 AND data_pagamento IS NOT NULL
    GROUP BY data_pagamento
    ORDER BY data_pagamento ASC
";
$result_fluxo = $conn->query($sql_fluxo);

if ($result_fluxo && $result_fluxo->num_rows > 0) {
    while($row = $result_fluxo->fetch_assoc()) {
        // Formata a data e o valor para o JSON que o Python espera
        $dados_para_python[] = [
            'data' => $row['data'], 
            // O Python espera o valor líquido (Receita - Despesa)
            'valor_liquido' => (float)$row['valor_liquido'] 
        ];
    }
    
    // Converte os dados agregados para JSON string (com escape para shell_exec)
    $dados_json_shell = escapeshellarg(json_encode($dados_para_python));

    // 3. CHAMADA AO SCRIPT PYTHON (O CORAÇÃO DO MÓDULO)
    // Usamos 'python' ou 'python3' dependendo da sua instalação. Tente 'python' primeiro.
    $comando = "python prever_fluxo.py " . $dados_json_shell;
    
    // Executa o comando e captura a saída
    $output = shell_exec($comando);
    
    // 4. PROCESSAMENTO DA SAÍDA JSON DO PYTHON
    $resultado_ia = json_decode($output, true);
    
    if (isset($resultado_ia['erro'])) {
        $erro_ia = "Erro no Script Python: " . $resultado_ia['erro'];
    } elseif (isset($resultado_ia['previsao'])) {
        // Dados Históricos e Previsão formatados para Chart.js
        $historico_json = json_encode($resultado_ia['dados_grafico']);
        $previsao_json = json_encode($resultado_ia['previsao']);
    } else {
        $erro_ia = "O Python não retornou um formato JSON válido.";
    }

} else {
    $erro_ia = "Insira mais transações 'Liquidadas' no Fluxo de Caixa para gerar a previsão.";
}

// 5. HTML E VISUALIZAÇÃO
?>

<div class="container content-page">
    <h2>Previsão de Fluxo de Caixa (IA)</h2>
    <p>Utilizando o histórico de transações liquidadas, o sistema projeta o fluxo de caixa líquido para os próximos 30 dias com base em um modelo de Regressão Linear.</p>

    <?php if ($erro_ia): ?>
        <p class='error-message container'><?php echo $erro_ia; ?></p>
        <p>Verifique se o Python está instalado e se as bibliotecas **pandas** e **scikit-learn** estão na sua PATH.</p>
    <?php else: ?>
        <div class="graph-container">
            <canvas id="fluxoChart"></canvas>
        </div>
        <p class="success-message">Previsão gerada com sucesso! Analise a linha de tendência para auxiliar suas decisões.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Dados injetados pelo PHP
    const historicoData = <?php echo $historico_json; ?>;
    const previsaoData = <?php echo $previsao_json; ?>;
    
    // Prepara os dados para o Chart.js
    const labels = [];
    const historicoValores = [];
    const previsaoValores = [];
    
    // Processa os dados históricos (Base real)
    historicoData.forEach(item => {
        labels.push(item.data);
        historicoValores.push(item.valor_liquido);
        previsaoValores.push(null); // Zera o valor da previsão para os dados históricos
    });
    
    // Processa os dados de previsão
    previsaoData.forEach(item => {
        // Garante que não haja datas duplicadas no label se a previsão começar no dia seguinte ao último histórico
        if (!labels.includes(item.data)) {
            labels.push(item.data);
            historicoValores.push(null); // Zera o valor histórico para a previsão
        }
        previsaoValores.push(item.valor);
    });

    const ctx = document.getElementById('fluxoChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Fluxo de Caixa Líquido Histórico (R$)',
                data: historicoValores,
                borderColor: 'rgba(0, 123, 255, 1)', // Azul forte
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            },
            {
                label: 'Previsão IA (Próx. 30 dias) (R$)',
                data: previsaoValores,
                borderColor: 'rgba(40, 167, 69, 1)', // Verde de tendência
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5], // Linha tracejada para a previsão
                tension: 0.1,
                fill: false,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Valor (R$)'
                    }
                }
            }
        }
    });
});
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>