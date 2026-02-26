<?php
// Arquivo: ia_previsao.php
// Versão: API Flask (cURL) + Gráfico de Linhas Minimalista/Source

// 1. SEGURANÇA E INCLUSÕES
include 'includes/header.php'; 
include 'includes/conexao.php'; 

// URL da API Flask. Deve estar rodando em http://localhost:5000/
$url_api = 'http://localhost:5000/prever'; 

$historico_json = '[]';
$previsao_json = '[]';
$erro_ia = null;
$dados_para_python = [];

// 2. LÓGICA DE EXTRAÇÃO DOS DADOS DO BANCO DE DADOS
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
        $dados_para_python[] = [
            'data' => $row['data'], 
            'valor_liquido' => (float)$row['valor_liquido'] 
        ];
    }
    
    // Converte os dados para JSON para enviar no corpo da requisição HTTP POST.
    $payload = json_encode(['dados_historicos' => $dados_para_python]);

    // 3. CHAMADA À API PYTHON (FLASK) VIA cURL
    
    $ch = curl_init($url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    
    $output = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // 4. PROCESSAMENTO DA RESPOSTA
    
    if ($curl_error) {
        $erro_ia = "Falha de Conexão: O Servidor Python (API Flask) não está rodando na porta 5000. Erro: " . $curl_error;
    } else {
        $resultado_ia = json_decode($output, true);
        
        if ($http_code == 200 && isset($resultado_ia['previsao'])) {
            // Sucesso na resposta
            $historico_json = json_encode($resultado_ia['dados_grafico']);
            $previsao_json = json_encode($resultado_ia['previsao']);
        } elseif (isset($resultado_ia['erro'])) {
            // Erro retornado pela API (código 400 ou 500)
            $erro_ia = "Erro da API de IA (HTTP $http_code): " . $resultado_ia['erro'];
        } else {
            // Outro erro de formato/conexão
            $erro_ia = "API indisponível ou formato inesperado (HTTP $http_code). Saída: " . $output;
        }
    }

} else {
    $erro_ia = "Insira mais transações 'Liquidadas' no Fluxo de Caixa (com Data de Pagamento) para gerar a previsão.";
}

// 5. HTML E VISUALIZAÇÃO
?>
---

<div class="container content-page">
    <h2>Previsão de Fluxo de Caixa (IA)</h2>
    <p>Utilizando o histórico de transações liquidadas, o sistema projeta o fluxo de caixa líquido para os próximos 30 dias com base em um modelo de Regressão Linear.</p>

    <?php if ($erro_ia): ?>
        <p class='error-message container'>
            <strong>Falha na Execução da IA:</strong> <?php echo $erro_ia; ?>
        </p>
        <p>Ação: **Deixe o Prompt de Comando aberto** e execute: **`python api.py`**</p>
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
    // Para garantir a visualização do novo estilo, use Ctrl+Shift+R para recarregar.
    const historicoData = <?php echo $historico_json; ?>;
    const previsaoData = <?php echo $previsao_json; ?>;
    
    const labels = [];
    const historicoValores = [];
    const previsaoValores = [];
    
    // Processamento de dados
    historicoData.forEach(item => {
        labels.push(item.data);
        historicoValores.push(item.valor_liquido);
        previsaoValores.push(null); 
    });
    
    previsaoData.forEach(item => {
        if (!labels.includes(item.data)) {
            labels.push(item.data);
            historicoValores.push(null); 
        }
        previsaoValores.push(item.valor);
    });

    const ctx = document.getElementById('fluxoChart').getContext('2d');
    
    // ----------------------------------------------------
    // CONFIGURAÇÃO DO ESTILO MINIMALISTA/SOURCE
    // ----------------------------------------------------

    new Chart(ctx, {
        type: 'line', 
        data: {
            labels: labels,
            datasets: [{
                label: 'Fluxo de Caixa Líquido Histórico (R$)',
                data: historicoValores,
                borderColor: 'rgba(0, 123, 255, 1)', // Azul Forte
                backgroundColor: 'transparent',
                borderWidth: 2.5,
                tension: 0.4,           // Linha Suave
                fill: false,            // Sem preenchimento de área (Estilo Source)
                pointRadius: 4,         // Pontos visíveis
                pointBackgroundColor: 'white',
                pointBorderColor: 'rgba(0, 123, 255, 1)',
                pointBorderWidth: 2
            },
            {
                label: 'Previsão IA (Próx. 30 dias) (R$)',
                data: previsaoValores,
                borderColor: 'rgba(40, 167, 69, 1)', // Verde de Previsão
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [6, 4],     // Linha tracejada
                tension: 0.4,
                fill: false,
                pointRadius: 4,
                pointBackgroundColor: 'white',
                pointBorderColor: 'rgba(40, 167, 69, 1)',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#F5F5F5', 
                    bodyColor: '#A9A9A9',
                    borderColor: '#4DA6FF',
                    borderWidth: 1,
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false // Remove linhas verticais para visual limpo
                    }
                },
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Valor (R$)',
                        color: '#666'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)', // Linhas horizontais bem suaves
                        borderDash: [3, 3]           // Linhas tracejadas no grid
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