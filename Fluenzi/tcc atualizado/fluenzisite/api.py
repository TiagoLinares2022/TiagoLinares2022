# Arquivo: api.py (Servidor Flask para Previsão de Fluxo)

from flask import Flask, request, jsonify
import pandas as pd
from sklearn.linear_model import LinearRegression
import numpy as np
from datetime import timedelta, date

app = Flask(__name__)

# A lógica central de previsão (praticamente a mesma do seu prever_fluxo.py)
def prever_fluxo(dados_historicos_raw, num_dias_futuro=30):
    
    df = pd.DataFrame(dados_historicos_raw)
    
    # Validação mínima
    if df.empty or len(df) < 2:
        return dados_historicos_raw, []
    
    # Converte e prepara dados para o modelo
    df['data'] = pd.to_datetime(df['data'])
    df['data_ordinal'] = df['data'].apply(lambda x: x.toordinal())
    
    X = df[['data_ordinal']]
    y = df['valor_liquido']
    
    # Treina o modelo
    modelo = LinearRegression()
    modelo.fit(X, y)
    
    # Gera as previsões futuras
    ultima_data = df['data'].max()
    datas_futuras = [ultima_data + timedelta(days=d) for d in range(1, num_dias_futuro + 1)]
    datas_futuras_ordinal = np.array([d.toordinal() for d in datas_futuras]).reshape(-1, 1)
    
    previsoes = modelo.predict(datas_futuras_ordinal)
    
    previsao_formatada = [{
        'data': str(d.date()),
        'valor': round(v, 2)
    } for d, v in zip(datas_futuras, previsoes)]

    # Retorna o histórico e a previsão
    return dados_historicos_raw, previsao_formatada


@app.route('/prever', methods=['POST'])
def prever():
    # 1. Recebe o JSON enviado pelo PHP via HTTP POST
    dados = request.get_json()
    
    if not dados or 'dados_historicos' not in dados:
        # Resposta de erro HTTP 400 se o JSON estiver faltando
        return jsonify({"erro": "Dados históricos não fornecidos ou formato JSON inválido."}), 400

    try:
        # 2. Roda a previsão
        dados_grafico, previsao_final = prever_fluxo(dados['dados_historicos'])
        
        # 3. Retorna o JSON de sucesso (HTTP 200)
        return jsonify({
            'previsao': previsao_final,
            'dados_grafico': dados_grafico
        })
        
    except Exception as e:
        # 4. Retorna erro de servidor (HTTP 500) se algo der errado na IA
        return jsonify({"erro": f"Erro de processamento da IA: {str(e)}"}), 500

if __name__ == '__main__':
    # Inicializa o servidor Flask na porta 5000.
    # Você precisará desta porta para a comunicação.
    print("Iniciando API Flask de Previsão em http://0.0.0.0:5000/prever")
    app.run(host='0.0.0.0', port=5000)