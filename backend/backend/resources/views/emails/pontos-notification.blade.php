<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tipo === 'ganho' ? 'Pontos Ganhos!' : 'Resgate Realizado!' }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: {{ $tipo === 'ganho' ? 'linear-gradient(135deg, #4CAF50 0%, #45a049 100%)' : 'linear-gradient(135deg, #FF6B6B 0%, #FF8E8E 100%)' }}; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
        .btn { display: inline-block; padding: 12px 25px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .highlight { background: {{ $tipo === 'ganho' ? '#e8f5e8' : '#ffe8e8' }}; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center; }
        .points { font-size: 36px; font-weight: bold; color: {{ $tipo === 'ganho' ? '#4CAF50' : '#FF6B6B' }}; }
        .emoji { font-size: 24px; }
    </style>
</head>
<body>
    <div class="header">
        @if($tipo === 'ganho')
            <h1><span class="emoji">🎉</span> Você Ganhou Pontos!</h1>
        @else
            <h1><span class="emoji">✅</span> Resgate Realizado!</h1>
        @endif
    </div>
    
    <div class="content">
        <p>Olá <strong>{{ $user->name }}</strong>!</p>
        
        @if($tipo === 'ganho')
            <p>Parabéns! Você acabou de ganhar pontos na <strong>{{ $empresa->nome ?? 'TemDeTudo' }}</strong>! 🛍️</p>
            
            <div class="highlight">
                <div class="points">+{{ $pontos }}</div>
                <p><strong>Pontos Ganhos!</strong></p>
                <p>Total atual: <strong>{{ $user->pontos }} pontos</strong></p>
            </div>
            
            <p>🎁 <strong>Dica:</strong> Continue acumulando pontos e troque por recompensas incríveis!</p>
            
        @else
            <p>Seu resgate foi processado com sucesso! 🎊</p>
            
            <div class="highlight">
                <div class="points">-{{ $pontos }}</div>
                <p><strong>Pontos Utilizados</strong></p>
                <p>Saldo restante: <strong>{{ $user->pontos }} pontos</strong></p>
            </div>
            
            <p>🚚 Sua recompensa será processada em até 24 horas!</p>
        @endif
        
        <p>Veja seu histórico completo e descubra novas oportunidades:</p>
        <a href="{{ env('APP_URL') }}/profile-client.html" class="btn">Ver Meu Perfil</a>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
        
        <h3>🏆 Seu Nível Atual</h3>
        <p>
            @php
                $nivel = 'Bronze';
                if($user->pontos >= 10000) $nivel = 'Diamante';
                elseif($user->pontos >= 5000) $nivel = 'Ouro';
                elseif($user->pontos >= 2000) $nivel = 'Prata';
            @endphp
            
            Nível <strong>{{ $nivel }}</strong> 
            
            @if($nivel === 'Bronze')
                ({{ 2000 - $user->pontos }} pontos para Prata)
            @elseif($nivel === 'Prata')
                ({{ 5000 - $user->pontos }} pontos para Ouro)
            @elseif($nivel === 'Ouro')
                ({{ 10000 - $user->pontos }} pontos para Diamante)
            @else
                - Nível Máximo! 💎
            @endif
        </p>
        
        <p>Atenciosamente,<br>
        <strong>Equipe TemDeTudo</strong> 💜</p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} TemDeTudo - Plataforma de Fidelidade</p>
        <p>Este email foi enviado para {{ $user->email }}</p>
        <p><a href="{{ env('APP_URL') }}/estabelecimentos.html">Descubra mais empresas parceiras</a></p>
    </div>
</body>
</html>