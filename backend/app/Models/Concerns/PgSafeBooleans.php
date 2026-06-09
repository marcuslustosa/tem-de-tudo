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
 *
 * IMPORTANTE: nao aplicar em models cujo fluxo de escrita ja converte o boolean
 * manualmente (ex.: User no register usa string 'true'/'false'), para evitar
 * dupla conversao.
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

    public function setSentAttribute($value): void
    {
        $this->attributes['sent'] = $this->pgSafeBooleanValue($value);
    }

    public function setIsSentAttribute($value): void
    {
        $this->attributes['is_sent'] = $this->pgSafeBooleanValue($value);
    }

    public function setBonusAppliedAttribute($value): void
    {
        $this->attributes['bonus_applied'] = $this->pgSafeBooleanValue($value);
    }

    public function setExpiradoAttribute($value): void
    {
        $this->attributes['expirado'] = $this->pgSafeBooleanValue($value);
    }

    public function setConcluidoAttribute($value): void
    {
        $this->attributes['concluido'] = $this->pgSafeBooleanValue($value);
    }

    public function setRecompensaDadaAttribute($value): void
    {
        $this->attributes['recompensa_dada'] = $this->pgSafeBooleanValue($value);
    }

    public function setSucessoAttribute($value): void
    {
        $this->attributes['sucesso'] = $this->pgSafeBooleanValue($value);
    }

    public function setRedeCompartilhadaAttribute($value): void
    {
        $this->attributes['rede_compartilhada'] = $this->pgSafeBooleanValue($value);
    }

    public function setAppliesToAllProductsAttribute($value): void
    {
        $this->attributes['applies_to_all_products'] = $this->pgSafeBooleanValue($value);
    }

    public function setAppliesToAllServicesAttribute($value): void
    {
        $this->attributes['applies_to_all_services'] = $this->pgSafeBooleanValue($value);
    }
}
