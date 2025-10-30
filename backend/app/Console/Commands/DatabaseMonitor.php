<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDOException;
use Exception;

class DatabaseMonitor extends Command
{
    protected $signature = 'db:monitor';
    protected $description = 'Monitor database connection and tables';

    public function handle()
    {
        try {
            // Testar conexão
            DB::connection()->getPdo();
            $this->info("✓ Connected to database: " . DB::connection()->getDatabaseName());
            
            // Verificar tabela sessions
            $hasSessionsTable = DB::connection()->getSchemaBuilder()->hasTable('sessions');
            if (!$hasSessionsTable) {
                throw new \Exception("Sessions table not found!");
            }
            $this->info("✓ Sessions table exists");

            // Testar criação de sessão
            $sessionId = "test_" . time();
            DB::table('sessions')->insert([
                'id' => $sessionId,
                'user_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DatabaseMonitor',
                'payload' => 'test',
                'last_activity' => time()
            ]);
            DB::table('sessions')->where('id', $sessionId)->delete();
            $this->info("✓ Session operations working");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Database Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}