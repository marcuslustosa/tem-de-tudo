<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseTable extends Command
{
    protected $signature = 'db:table {table?}';
    protected $description = 'Show table information';

    public function handle()
    {
        try {
            $tableName = $this->argument('table');
            
            if ($tableName) {
                // Verificar se tabela existe
                if (!Schema::hasTable($tableName)) {
                    $this->error("Tabela '$tableName' não existe!");
                    return 1;
                }

                // Mostrar estrutura da tabela
                $this->info("Estrutura da tabela '$tableName':");
                $columns = Schema::getColumnListing($tableName);
                foreach ($columns as $column) {
                    $type = Schema::getColumnType($tableName, $column);
                    $this->line("- $column ($type)");
                }

                // Contar registros
                $count = DB::table($tableName)->count();
                $this->info("Total de registros: $count");
                
                return 0;
            }

            // Listar todas as tabelas
            $tables = Schema::getAllTables();
            $this->info('Tabelas no banco:');
            foreach ($tables as $table) {
                $name = current($table);
                $count = DB::table($name)->count();
                $this->line("- $name ($count registros)");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Erro: " . $e->getMessage());
            return 1;
        }
    }
}