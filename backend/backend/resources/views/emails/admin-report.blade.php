<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório Administrativo - TemDeTudo</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
        .stats { display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 10px; flex: 1; min-width: 150px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .section { margin: 30px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; font-weight: bold; }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
        .warning { color: #ffc107; }
        .emoji { font-size: 24px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><span class="emoji">📊</span> Relatório {{ $periodName }}</h1>
        <p>{{ now()->format('d/m/Y H:i') }}</p>
    </div>
    
    <div class="content">
        <p>Olá <strong>{{ $admin->name }}</strong>,</p>
        
        <p>Aqui está o resumo {{ strtolower($periodName) }} das atividades da plataforma TemDeTudo:</p>
        
        <div class="section">
            <h2>📈 Visão Geral</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($report['users']['total'] ?? 0) }}</div>
                    <div class="stat-label">Total de Usuários</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number positive">{{ number_format($report['users']['new'] ?? 0) }}</div>
                    <div class="stat-label">Novos Usuários</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($report['companies']['total'] ?? 0) }}</div>
                    <div class="stat-label">Empresas Parceiras</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number positive">{{ number_format($report['points']['distributed'] ?? 0) }}</div>
                    <div class="stat-label">Pontos Distribuídos</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>🔐 Segurança & Logins</h2>
            <table class="table">
                <tr>
                    <th>Métrica</th>
                    <th>Valor</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>Logins Bem-sucedidos</td>
                    <td>{{ number_format($report['security']['successful_logins'] ?? 0) }}</td>
                    <td><span class="positive">✅ Normal</span></td>
                </tr>
                <tr>
                    <td>Tentativas Falhadas</td>
                    <td>{{ number_format($report['security']['failed_attempts'] ?? 0) }}</td>
                    <td>
                        @if(($report['security']['failed_attempts'] ?? 0) > 100)
                            <span class="warning">⚠️ Alto</span>
                        @else
                            <span class="positive">✅ Normal</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Contas Bloqueadas</td>
                    <td>{{ number_format($report['security']['locked_accounts'] ?? 0) }}</td>
                    <td>
                        @if(($report['security']['locked_accounts'] ?? 0) > 10)
                            <span class="negative">🚨 Crítico</span>
                        @else
                            <span class="positive">✅ Normal</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>💰 Atividade de Pontos</h2>
            <table class="table">
                <tr>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Pontos</th>
                </tr>
                <tr>
                    <td>Pontos Ganhos</td>
                    <td>{{ number_format($report['points']['transactions_gain'] ?? 0) }} transações</td>
                    <td class="positive">+{{ number_format($report['points']['total_gained'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Pontos Resgatados</td>
                    <td>{{ number_format($report['points']['transactions_redeem'] ?? 0) }} resgates</td>
                    <td class="negative">-{{ number_format($report['points']['total_redeemed'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td><strong>Saldo Total</strong></td>
                    <td>-</td>
                    <td><strong>{{ number_format($report['points']['total_balance'] ?? 0) }}</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>🏆 Top Performers</h2>
            
            <h3>👥 Usuários Mais Ativos</h3>
            @if(isset($report['top_users']) && count($report['top_users']) > 0)
                <table class="table">
                    <tr>
                        <th>Posição</th>
                        <th>Usuário</th>
                        <th>Pontos</th>
                        <th>Nível</th>
                    </tr>
                    @foreach($report['top_users'] as $index => $user)
                    <tr>
                        <td>{{ $index + 1 }}°</td>
                        <td>{{ $user['name'] }}</td>
                        <td>{{ number_format($user['pontos']) }}</td>
                        <td>
                            @php
                                $nivel = 'Bronze';
                                if($user['pontos'] >= 10000) $nivel = '💎 Diamante';
                                elseif($user['pontos'] >= 5000) $nivel = '🥇 Ouro';
                                elseif($user['pontos'] >= 2000) $nivel = '🥈 Prata';
                                else $nivel = '🥉 Bronze';
                            @endphp
                            {{ $nivel }}
                        </td>
                    </tr>
                    @endforeach
                </table>
            @else
                <p>Nenhum dado disponível para este período.</p>
            @endif
        </div>
        
        <div class="section">
            <h2>⚠️ Alertas e Recomendações</h2>
            <ul>
                @if(($report['security']['failed_attempts'] ?? 0) > 100)
                    <li class="warning">🔐 Alto número de tentativas de login falhadas - Monitorar atividade suspeita</li>
                @endif
                
                @if(($report['users']['new'] ?? 0) < 10)
                    <li class="warning">📈 Baixo crescimento de usuários - Considerar campanhas de marketing</li>
                @endif
                
                @if(($report['points']['transactions_gain'] ?? 0) > ($report['points']['transactions_redeem'] ?? 0) * 10)
                    <li class="positive">💎 Excelente engajamento - Usuários acumulando pontos ativamente</li>
                @endif
                
                @if(!isset($report['alerts']) || count($report['alerts']) === 0)
                    <li class="positive">✅ Sistema funcionando normalmente - Nenhum alerta crítico</li>
                @endif
            </ul>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
        
        <p><strong>💡 Próximas Ações Sugeridas:</strong></p>
        <ul>
            <li>🔍 Revisar logs de segurança detalhados</li>
            <li>📊 Analisar métricas de conversão</li>
            <li>🎯 Planejar campanhas de engajamento</li>
            <li>🔧 Otimizar performance do sistema</li>
        </ul>
        
        <p>Para relatórios detalhados, acesse o <a href="{{ env('APP_URL') }}/admin.html">Dashboard Administrativo</a>.</p>
        
        <p>Atenciosamente,<br>
        <strong>Sistema Automático de Relatórios - TemDeTudo</strong> 🤖</p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} TemDeTudo - Plataforma de Fidelidade</p>
        <p>Relatório gerado automaticamente em {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Próximo relatório: {{ now()->addDay()->format('d/m/Y') }}</p>
    </div>
</body>
</html>