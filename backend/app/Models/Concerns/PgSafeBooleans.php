<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Mutators pg-safe para colunas booleanas. No PostgreSQL, enviar inteiro (Laravel
 * converte bool->int) para coluna boolean estoura ("boolean = integer"). Estes
 * mutators gravam 'true'/'false' (string aceita pelo pg) no pgsql e bool nos demais.
 *
 * Replica o padrao ja usado em App\Models\Promocao::setAtivoAttribute.
 * So afeta a ESCRITA do atributo correspondente quando ele e setado.
 */
trait PgSafeBooleans
{
    protected function pgSafeBooleanValue($value): bool|string
    {
        $bool = (bool) $value;

        return DB::connection()->getDriverName() === 'pgsql'
            ? ($bool ? 'true' : 'false')
            : $bool;
    }

    public function setAtivoAttribute($value): void
    {
        $this->attributes['ativo'] = $this->pgSafeBooleanValue($value);
    }

    public function setActiveAttribute($value): void
    {
        $this->attributes['active'] = $this->pgSafeBooleanValue($value);
    }

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['is_active'] = $this->pgSafeBooleanValue($value);
    }

    public function setEnviadoAttribute($value): void
    {
        $this->attributes['enviado'] = $this->pgSafeBooleanValue($value);
    }

    public function setResgatadoAttribute($value): void
    {
        $this->attributes['resgatado'] = $this->pgSafeBooleanValue($value);
    }
}
