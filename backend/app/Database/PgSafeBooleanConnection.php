<?php

namespace App\Database;

use DateTimeInterface;
use Illuminate\Database\PostgresConnection;
use PDO;

/**
 * Conexao PostgreSQL pg-safe para booleanos.
 *
 * O PostgreSQL e estrito: coluna boolean nao aceita inteiro. O Laravel, por padrao,
 * converte bool->int em prepareBindings, gerando "boolean = integer" / "is of type
 * boolean but expression is of type integer". Aqui:
 *   - NAO convertemos bool->int (mantemos o bool);
 *   - bindValues envia bool como PDO::PARAM_BOOL.
 * Assim qualquer where/insert/update com boolean PHP funciona no pg, sem precisar
 * tratar caso a caso. Compativel com os valores 'true'/'false' (string) e DB::raw
 * ja usados no codigo. Aplica-se SOMENTE ao driver pgsql.
 */
class PgSafeBooleanConnection extends PostgresConnection
{
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            }
            // Intencional: nao fazemos (int) $value para bool (pg-safe).
        }

        return $bindings;
    }

    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                match (true) {
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_int($value) => PDO::PARAM_INT,
                    is_resource($value) => PDO::PARAM_LOB,
                    default => PDO::PARAM_STR,
                },
            );
        }
    }
}
