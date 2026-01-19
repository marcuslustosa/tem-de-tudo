<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sistema OK</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; background: #0a0a0f; color: #fff; }
        h1 { color: #4caf50; font-size: 48px; }
        .info { background: #1a1a2e; padding: 20px; margin: 20px auto; max-width: 600px; border-radius: 12px; }
        code { background: #2a2a3e; padding: 4px 8px; border-radius: 4px; color: #667eea; }
        a { color: #667eea; text-decoration: none; padding: 12px 24px; background: #2a2a3e; border-radius: 8px; display: inline-block; margin: 10px; }
        a:hover { background: #667eea; color: #fff; }
    </style>
</head>
<body>
    <h1>✅ Sistema Online</h1>
    <div class="info">
        <p><strong>Servidor PHP:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>Laravel:</strong> <?php echo app()->version(); ?></p>
        <p><strong>Ambiente:</strong> <?php echo config('app.env'); ?></p>
        <p><strong>Banco:</strong> <?php echo config('database.default'); ?></p>
        <p><strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        try {
            $usersCount = DB::table('users')->count();
            echo "<p><strong>Usuários:</strong> {$usersCount}</p>";
        } catch (\Exception $e) {
            echo "<p style='color: #f44336;'><strong>Banco:</strong> Erro - " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <h3>Páginas de Teste:</h3>
    <a href="/teste-login.html">🔑 Teste de Login</a>
    <a href="/api/debug">🔧 API Debug</a>
    <a href="/entrar.html">📱 Tela de Login</a>
</body>
</html>
