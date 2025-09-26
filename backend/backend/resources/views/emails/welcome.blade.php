<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bem-vindo à TemDeTudo</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
        .btn { display: inline-block; padding: 12px 25px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .highlight { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .emoji { font-size: 24px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><span class="emoji">🎉</span> Bem-vindo à TemDeTudo!</h1>
    </div>
    
    <div class="content">
        <p>Olá <strong>{{ $user->name }}</strong>!</p>
        
        @if($userType === 'company')
            <p>É com grande alegria que recebemos sua empresa como parceira da <strong>TemDeTudo</strong>! 🏢</p>
            
            <div class="highlight">
                <h3>🚀 Próximos passos para sua empresa:</h3>
                <ul>
                    <li>✅ Configurar sistema de pontos para clientes</li>
                    <li>✅ Personalizar recompensas e promoções</li>
                    <li>✅ Acessar dashboard administrativo</li>
                    <li>✅ Gerar QR Code para coleta de pontos</li>
                </ul>
            </div>
            
            <p>Acesse seu painel administrativo:</p>
            <a href="{{ env('APP_URL') }}/profile-company.html" class="btn">Acessar Painel da Empresa</a>
            
        @else
            <p>Parabéns! Você agora faz parte da maior plataforma de fidelidade do Brasil! 🎊</p>
            
            <div class="highlight">
                <h3>🎁 Como funciona:</h3>
                <ul>
                    <li>🛍️ Faça compras em empresas parceiras</li>
                    <li>📱 Escaneie QR codes para ganhar pontos</li>
                    <li>🏆 Troque pontos por recompensas incríveis</li>
                    <li>⭐ Suba de nível e ganhe benefícios exclusivos</li>
                </ul>
            </div>
            
            <p>Comece a acumular pontos agora:</p>
            <a href="{{ env('APP_URL') }}/profile-client.html" class="btn">Ver Meu Perfil</a>
        @endif
        
        <p>Se tiver dúvidas, nossa equipe está sempre disponível para ajudar!</p>
        
        <p>Atenciosamente,<br>
        <strong>Equipe TemDeTudo</strong> 💜</p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} TemDeTudo - Plataforma de Fidelidade</p>
        <p>Este email foi enviado para {{ $user->email }}</p>
        <p>Dúvidas? Entre em contato: suporte@temdetudo.com</p>
    </div>
</body>
</html>