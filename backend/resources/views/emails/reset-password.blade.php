<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: rgba(255,255,255,0.9);
            margin: 10px 0 0 0;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #2d3748;
            margin-top: 0;
        }
        .content p {
            color: #4a5568;
            line-height: 1.6;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            opacity: 0.9;
        }
        .token-box {
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .token {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            color: #667eea;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .footer {
            background: #f7fafc;
            padding: 20px;
            text-align: center;
            color: #718096;
            font-size: 14px;
        }
        .warning {
            background: #fff5f5;
            border-left: 4px solid #f56565;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            color: #c53030;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Tem de Tudo</h1>
            <p>Sistema de Fidelidade</p>
        </div>

        <div class="content">
            <h2>Recuperação de Senha</h2>
            
            <p>Olá! Recebemos uma solicitação para redefinir a senha da sua conta.</p>
            
            <p>Clique no botão abaixo para criar uma nova senha:</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">
                    🔑 Redefinir Senha
                </a>
            </div>

            <p style="color: #718096; font-size: 14px;">
                Ou copie e cole este link no seu navegador:<br>
                <code style="background: #f7fafc; padding: 5px 10px; border-radius: 4px; display: inline-block; margin-top: 10px;">{{ $resetUrl }}</code>
            </p>

            <div class="token-box">
                <p style="margin: 0 0 10px 0; color: #4a5568; font-size: 14px;">Seu código de verificação:</p>
                <div class="token">{{ $token }}</div>
            </div>

            <div class="warning">
                <p><strong>⚠️ Atenção:</strong> Este link expira em 60 minutos por motivos de segurança.</p>
            </div>

            <p>Se você não solicitou a redefinição de senha, ignore este e-mail. Sua senha permanecerá inalterada.</p>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">

            <p style="color: #718096; font-size: 14px;">
                <strong>Dicas de segurança:</strong><br>
                • Nunca compartilhe sua senha com ninguém<br>
                • Use senhas diferentes para cada serviço<br>
                • Escolha senhas com letras, números e símbolos
            </p>
        </div>

        <div class="footer">
            <p>
                <strong>Tem de Tudo</strong><br>
                Sistema de Fidelidade e Promoções<br>
                © {{ date('Y') }} Todos os direitos reservados
            </p>
            <p style="margin-top: 15px;">
                📧 suporte@temdetudo.com.br<br>
                🌐 www.temdetudo.com.br
            </p>
        </div>
    </div>
</body>
</html>
