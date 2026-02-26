<?php
// Arquivo: emissao_nfe.php
// Objetivo: Testar o carregamento do certificado e a comunicação com a Sefaz PR (ambiente de Homologação).

// Inclui o autoloader do Composer para carregar a biblioteca NFePHP
require 'vendor/autoload.php';

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;

// =========================================================
// 1. CONFIGURAÇÕES CRÍTICAS (ALTERE AQUI!)
// =========================================================
$config = [
    // Versão do XML
    'versao' => '4.00',
    // Ambiente: 1=Produção, 2=Homologação (TESTE)
    'tpAmb' => 2, 
    // Sigla do estado do emitente (PR, neste caso)
    'siglaUF' => 'PR', 
    'schemeVer' => 'PL_009_V4',
    // SEU CNPJ - SOMENTE NÚMEROS
    'cnpj' => 'SEU_CNPJ_AQUI_APENAS_NUMEROS', 
    
    // Configurações do Certificado
    'certificado' => [
        // O CAMINHO E NOME DO SEU ARQUIVO PFX
        'path' => 'includes/certificados/seu_certificado.pfx', 
        // A SENHA DO SEU ARQUIVO PFX
        'pass' => 'SUA_SENHA_DO_CERTIFICADO', 
    ],
    
    // Pastas onde os arquivos XML serão salvos (crie estas pastas!)
    'pastas' => [
        'xmltemp' => 'xml_temp', 
        'xmlvalid' => 'xml_validados',
        'xmlassinado' => 'xml_assinados',
        'xmlautorizado' => 'xml_autorizados',
    ]
];

// Cria as pastas de XML se não existirem
foreach ($config['pastas'] as $pasta) {
    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }
}

// =========================================================
// 2. INICIALIZAÇÃO E TESTE DO CERTIFICADO
// =========================================================
try {
    // Inicializa a classe Tools que se comunica com a Sefaz
    $tools = new Tools(json_encode($config), Standardize::class);
    $tools->model(55); // Define o modelo NF-e (55)
    
} catch (\Exception $e) {
    die("<h3>🚨 Erro Crítico no Certificado ou Configuração!</h3>" 
        . "Mensagem: " . $e->getMessage() 
        . "<br>Verifique se o caminho do PFX e a senha em \$config estão corretos.");
}

echo "<h3>✅ Certificado Carregado com Sucesso!</h3>";


// =========================================================
// 3. MONTAGEM DO XML DA NOTA (ESQUELETO SIMPLES)
// =========================================================
try {
    $make = new Make();
    $std = new \stdClass();

    // A. Identificação (Tag ide)
    $std->cUF = 41; // Código da UF (Paraná)
    $std->cNF = rand(10000000, 99999999); 
    $std->natOp = 'VENDA DE MERCADORIA';
    $std->indPag = 0; // 0=à vista
    $std->mod = 55;
    $std->serie = 1;
    $std->nNF = rand(100, 500); // Número aleatório para evitar duplicidade em testes
    $std->dhEmi = date("Y-m-d\TH:i:sP"); // Data de emissão
    $std->tpNF = 1; // 1=Saída
    $std->idDest = 1; // 1=Operação Interna
    $std->cMunFG = 4106902; // Código do município do Fato Gerador (exemplo Curitiba)
    $std->tpImp = 1; // 1=Retrato
    $std->tpEmis = 1; // 1=Emissão normal
    $std->cDV = 0; // Será calculado automaticamente
    $std->tpAmb = $config['tpAmb']; // Ambiente de Homologação (2)
    $std->finNFe = 1; // 1=NF-e normal
    $std->indFinal = 1; // 1=Consumidor final
    $std->indPres = 1; // 1=Operação presencial
    $std->procEmi = 0; 
    $std->verProc = '1.0'; 
    $make->tagide($std);


    // B. Emitente (Tag emit) - Dados devem ser idênticos aos do certificado
    $std = new \stdClass();
    $std->xNome = 'SEU NOME OU RAZÃO SOCIAL'; 
    $std->CNPJ = $config['cnpj']; 
    $std->xLgr = 'Rua Principal';
    $std->nro = '100';
    $std->xBairro = 'Centro';
    $std->cMun = 4106902;
    $std->xMun = 'Curitiba';
    $std->UF = $config['siglaUF'];
    $std->CEP = '80000000';
    $std->cPais = 1058;
    $std->xPais = 'BRASIL';
    $std->IE = 'SUA INSCRICAO ESTADUAL';
    $std->CRT = 1; // 1=Simples Nacional (AJUSTE CONFORME SEU REGIME)
    $make->tagemit($std);


    // C. Destinatário (Tag dest) - Cliente de teste (use dados válidos)
    $std = new \stdClass();
    $std->xNome = 'CLIENTE DE TESTE';
    $std->CPF = '00000000000'; // CPF VÁLIDO para teste (Homologação aceita CPF/CNPJ)
    $std->indIEDest = 9; // 9=Não contribuinte
    $make->tagdest($std);


    // D. Produto (Tag det - item 1)
    $std = new \stdClass();
    $std->item = 1;
    $std->cProd = '123';
    $std->xProd = 'TESTE DE EMISSAO - SERVICO';
    $std->NCM = '85011000'; // NCM deve ser válido
    $std->CFOP = '5102'; // CFOP correto para venda dentro do estado (AJUSTE)
    $std->uCom = 'UN';
    $std->qCom = 1.0000;
    $std->vUnCom = 100.00;
    $std->vProd = 100.00;
    $std->uTrib = 'UN';
    $std->qTrib = 1.0000;
    $std->vUnTrib = 100.00;
    $make->tagprod($std);

    // E. Imposto (ICMS/IPI/PIS/COFINS) - CRÍTICO!
    // Exemplo Simples Nacional (CSOSN 102 - Sem permissão de crédito)
    $std = new \stdClass();
    $std->item = 1;
    $std->orig = 0; // 0=Nacional
    $std->CSOSN = 102; 
    $make->tagICMSSN($std); 

    // PIS e COFINS (CST 07 - Isenta/Não tributada - AJUSTE)
    $std = new \stdClass();
    $std->item = 1;
    $std->CST = '07'; 
    $make->tagPIS($std);
    $make->tagCOFINS($std);


    // F. Totais da Nota
    $std = new \stdClass();
    $std->vProd = 100.00; // Soma do valor dos produtos
    $std->vNF = 100.00; // Valor total da nota (vProd + vOutro - vDesc)
    // Os demais valores (ICMS, IPI, PIS, COFINS, Frete, etc.) devem ser 0.00 se não aplicáveis
    $make->tagICMSTot($std);
    
    // G. Transporte
    $std = new \stdClass();
    $std->modFrete = 9; // 9=Sem frete
    $make->tagtransp($std);

    // H. Pagamento
    $std = new \stdClass();
    $std->vTroco = 0.00;
    $make->tagpag($std);

    $std = new \stdClass();
    $std->indPag = '0'; // 0=À vista
    $std->tPag = '01'; // 01=Dinheiro
    $std->vPag = 100.00;
    $make->tagdetPag($std);


    // --- 4. GERAÇÃO E ENVIO PARA A SEFAZ ---
    
    $xml = $make->montaNFe();
    // Salva o XML (opcional)
    file_put_contents("{$config['pastas']['xmltemp']}/temp.xml", $xml); 
    
    $assinado = $tools->assinaXml($xml);
    
    // Envia o lote para a Sefaz (lote de 1 nota)
    $lote = str_pad(rand(1, 999999999999999), 15, '0', STR_PAD_LEFT);
    $resp = $tools->sefazEnviaLote([$assinado], $lote);

    // Trata a resposta da Sefaz
    $st = new Standardize($resp);
    $stdResp = $st->toArray();

    echo "<hr><h2>Resultado da SEFAZ PR (Homologação):</h2>";
    
    if (isset($stdResp['cStat'])) {
        $cStat = $stdResp['cStat'];
        $xMotivo = $stdResp['xMotivo'];
        
        if ($cStat == 103) {
            echo "<h3 style='color:orange;'>Aguardando Processamento:</h3> O lote foi recebido com sucesso (cStat 103). É necessário fazer uma consulta para saber o resultado final (cStat 100).";
        } elseif ($cStat == 100) {
            echo "<h3 style='color:green;'>✅ NF-e Autorizada em Homologação!</h3>";
            echo "<p>Chave de Acesso: {$stdResp['protNFe']['infProt']['chNFe']}</p>";
        } else {
            echo "<h3 style='color:red;'>Rejeição/Erro:</h3> Código: **$cStat** - Motivo: **$xMotivo**";
        }
    } else {
        echo "<h3 style='color:red;'>Erro Desconhecido ou Falha na Comunicação!</h3>";
    }

} catch (\Exception $e) {
    die("<h3>🚨 Erro Crítico durante a Montagem ou Envio:</h3>" . $e->getMessage());
}
?>