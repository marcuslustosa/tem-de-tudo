<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pontos Expirando</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #FF6B35;
        }
        .header h1 {
            color: #FF6B35;
            margin: 0;
            font-size: 28px;
        }
        .warning-icon {
            font-size: 60px;
            text-align: center;
            margin: 20px 0;
        }
        .content {
            padding: 20px 0;
        }
        .highlight-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .highlight-box h2 {
            margin: 0;
            font-size: 36px;
            font-weight: bold;
        }
        .highlight-box p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 12px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Tem de Tudo</h1>
        </div>

        <div class="warning-icon">⏰</div>

        <div class="content">
            <h2 style="color: #FF6B35;">Olá, {{ $userName }}!</h2>
            
            <p>Temos um aviso importante sobre seus pontos no <strong>Tem de Tudo</strong>.</p>

            <div class="highlight-box">
                <h2>{{ $pontosExpirando }} pontos</h2>
                <p>estão prestes a expirar!</p>
            </div>

            <div class="info-box">
                <strong>⏱️ Tempo restante:</strong> {{ $diasRestantes }} dias<br>
                <strong>📅 Data de expiração:</strong> {{ $dataExpiracao }}<br>
                <strong>💎 Seus pontos atuais:</strong> {{ $pontosAtuais }} pontos
            </div>

            <p><strong>Não perca seus pontos!</strong> Use-os antes que expirem:</p>

            <ul>
                <li>✨ Resgate ofertas exclusivas</li>
                <li>🎁 Troque por descontos</li>
                <li>🏆 Aproveite promoções especiais</li>
                <li>💰 Ganhe mais valor nas suas compras</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/recompensas" class="cta-button">
                    🛍️ Ver Ofertas Disponíveis
                </a>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                <strong>💡 Dica:</strong> Continue acumulando pontos fazendo check-ins nos estabelecimentos parceiros e aproveitando nossas campanhas especiais!
            </p>
        </div>

        <div class="footer">
            <p>
                Esta é uma notificação automática do sistema Tem de Tudo.<br>
                Você está recebendo este e-mail porque possui pontos que estão próximos da data de expiração.
            </p>
            <p style="margin-top: 10px;">
                © {{ date('Y') }} Tem de Tudo - Programa de Fidelidade
            </p>
        </div>
    </div>
</body>
</html>
