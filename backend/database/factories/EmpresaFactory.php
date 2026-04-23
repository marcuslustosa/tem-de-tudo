<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->state([
                'perfil' => 'empresa',
                'status' => 'ativo',
            ]),
            'nome' => $this->faker->company(),
            'endereco' => $this->faker->streetAddress(),
            'telefone' => $this->faker->numerify('(11) 9####-####'),
            'cnpj' => sprintf(
                '%02d.%03d.%03d/%04d-%02d',
                random_int(10, 99),
                random_int(100, 999),
                random_int(100, 999),
                random_int(1000, 9999),
                random_int(10, 99)
            ),
            'descricao' => $this->faker->sentence(),
            'ramo' => $this->faker->randomElement([
                'restaurante',
                'mercado',
                'academia',
                'farmacia',
                'servicos',
            ]),
            'points_multiplier' => 1.0,
            'ativo' => true,
        ];
    }
}

