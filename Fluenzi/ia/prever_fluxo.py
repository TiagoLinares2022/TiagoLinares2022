# Arquivo: prever_fluxo.py

import sys
import json
import pandas as pd
from sklearn.linear_model import LinearRegression
import numpy as np
from datetime import timedelta, date

# Função principal para prever o fluxo de caixa
def prever_fluxo(dados_historicos_raw, num_dias_futuro=30):
    try:
        # Tenta converter os dados brutos JSON em um DataFrame do Pandas
        df = pd.DataFrame(dados_historicos_raw)
    except Exception as e:
        # Captura erro se o DataFrame não puder ser criado
        raise ValueError(f"Falha ao criar DataFrame com os dados fornecidos: {e}")
    
    # Se não houver dados históricos ou menos que 2 pontos, retorna vazio
    if df.empty or len(df) < 2:
        return dados_historicos_raw, []
    
    # Preparação dos dados
    df['data'] = pd.to_datetime(df['data'])
    # O valor numérico da data é crucial para a Regressão Linear
    df['data_ordinal'] = df['data'].apply(lambda x: x.toordinal())
    
    # Define as variáveis do modelo
    X = df[['data_ordinal']] # Variável independente (data)
    y = df['valor_liquido']  # Variável dependente (valor do fluxo)
    
    # Treina o modelo
    modelo = LinearRegression()
    modelo.fit(X, y)
    
    # ------------------ Geração de Previsão ------------------
    
    # 1. Datas futuras
    ultima_data = df['data'].max()
    datas_futuras = [ultima_data + timedelta(days=d) for d in range(1, num_dias_futuro + 1)]
    datas_futuras_ordinal = np.array([d.toordinal() for d in datas_futuras]).reshape(-1, 1)
    
    # 2. Realiza a previsão
    previsoes = modelo.predict(datas_futuras_ordinal)
    
    # 3. Formata o resultado para o PHP/JSON
    previsao_formatada = [{
        'data': str(d.date()),
        'valor': round(v, 2)
    } for d, v in zip(datas_futuras, previsoes)]

    # Retorna os dados
    return dados_historicos_raw, previsao_formatada

# Bloco principal que é executado
if __name__ == "__main__":
    if len(sys.argv) > 1:
        dados_historicos_json_str = sys.argv[1] 
        try:
            # 🛑 CORREÇÃO CRÍTICA: Remove as aspas duplas externas que o shell do Windows adiciona.
            if dados_historicos_json_str.startswith('"') and dados_historicos_json_str.endswith('"'):
                dados_historicos_json_str = dados_historicos_json_str[1:-1]
                
            # Decodifica o JSON
            dados_historicos = json.loads(dados_historicos_json_str)
            
            # Chama a função principal
            dados_grafico, previsao_final = prever_fluxo(dados_historicos)
            
            # Imprime o JSON final (única coisa que deve ir para o stdout)
            print(json.dumps({
                'previsao': previsao_final,
                'dados_grafico': dados_grafico
            }))
            
        except Exception as e:
            # Em caso de erro, retorna o JSON de erro e imprime no stderr para depuração.
            erro_msg = f"Erro de processamento no Python: {str(e)}. String JSON recebida: {dados_historicos_json_str}"
            json_error = json.dumps({'erro': erro_msg})
            
            print(json_error, file=sys.stderr)
            print(json_error) # Retorna o erro no stdout para o PHP capturar
    else:
        # Erro de argumento
        json_error = json.dumps({'erro': 'Nenhum dado histórico fornecido para a IA.'})
        print(json_error, file=sys.stderr)
        print(json_error)