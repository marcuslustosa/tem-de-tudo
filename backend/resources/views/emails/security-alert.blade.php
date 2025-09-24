<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alerta de Segurança</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #FF6B6B 0%, #FF8E8E 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
        .btn { display: inline-block; padding: 12px 25px; background: #FF6B6B; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .alert { background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; font-family: monospace; font-size: 14px; }
        .emoji { font-size: 24px; }
        .safe { color: #28a745; }
        .danger { color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h1><span class="emoji">🔒</span> Alerta de Segurança</h1>
    </div>
    
    <div class="content">
        <p>Olá <strong>{{ $user->name }}</strong>,</p>
        
        <div class="alert">
            <h3>⚠️ Detectamos atividade em sua conta</h3>
            
            @if($event === 'login_suspicious')
                <p>Um login suspeito foi detectado em sua conta. Se não foi você, tome medidas imediatas.</p>
                
            @elseif($event === 'password_changed')
                <p class="safe">✅ Sua senha foi alterada com sucesso.</p>
                <p>Se você não fez esta alteração, entre em contato conosco imediatamente.</p>
                
            @elseif($event === 'account_locked')
                <p class="danger">🔒 Sua conta foi temporariamente bloqueada por medidas de segurança.</p>
                <p>Muitas tentativas de login foram detectadas. Aguarde {{ $details['lockout_minutes'] ?? 15 }} minutos para tentar novamente.</p>
                
            @elseif($event === 'new_device')
                <p>📱 Um novo dispositivo acessou sua conta.</p>
                <p>Se foi você, pode ignorar este email. Caso contrário, recomendamos trocar sua senha.</p>
                
            @else
                <p>Atividade de segurança detectada em sua conta.</p>
            @endif
        </div>
        
        <h3>📋 Detalhes do Evento:</h3>
        <div class="details">
            <strong>Data/Hora:</strong> {{ now()->format('d/m/Y H:i:s') }}<br>
            <strong>IP:</strong> {{ $details['ip_address'] ?? 'N/A' }}<br>
            <strong>Dispositivo:</strong> {{ $details['user_agent'] ?? 'N/A' }}<br>
            <strong>Localização:</strong> {{ $details['location'] ?? 'Não disponível' }}
        </div>
        
        <h3>🛡️ Medidas de Segurança Recomendadas:</h3>
        <ul>
            <li>✅ Altere sua senha se suspeitar de acesso não autorizado</li>
            <li>✅ Ative a autenticação de dois fatores</li>
            <li>✅ Verifique dispositivos conectados à sua conta</li>
            <li>✅ Use uma senha forte e única</li>
            <li>✅ Não compartilhe suas credenciais</li>
        </ul>
        
        @if($event === 'login_suspicious' || $event === 'new_device')
            <p><strong>🚨 Se não foi você:</strong></p>
            <a href="{{ env('APP_URL') }}/login.html" class="btn">Alterar Senha Agora</a>
        @endif
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
        
        <p>Se você tem dúvidas ou precisa de ajuda, nossa equipe de suporte está disponível 24/7.</p>
        
        <p>Atenciosamente,<br>
        <strong>Equipe de Segurança TemDeTudo</strong> 🔐</p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} TemDeTudo - Plataforma de Fidelidade</p>
        <p>Este alerta foi enviado para {{ $user->email }}</p>
        <p><strong>Contato de Segurança:</strong> seguranca@temdetudo.com | WhatsApp: (11) 99999-9999</p>
    </div>
</body>
</html>