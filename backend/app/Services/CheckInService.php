<?php

namespace App\Services;

use App\DTOs\CheckIn\CheckInDTO;
use App\Repositories\CheckInRepository;
use App\Repositories\UserRepository;
use App\Repositories\EmpresaRepository;
use App\Models\CheckIn;
use App\Models\Ponto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckInService
{
    public function __construct(
        private CheckInRepository $checkInRepository,
        private UserRepository $userRepository,
        private EmpresaRepository $empresaRepository
    ) {}

    /**
     * Realizar check-in
     * 
     * @throws Exception
     */
    public function checkIn(CheckInDTO $dto): array
    {
        DB::beginTransaction();
        
        try {
            // Busca empresa
            $empresa = $this->empresaRepository->findById($dto->empresa_id);
            if (!$empresa) {
                throw new Exception('Empresa não encontrada');
            }

            if (!$empresa->ativo) {
                throw new Exception('Esta empresa não está ativa');
            }

            // Busca usuário
            $user = $this->userRepository->findById($dto->user_id);
            if (!$user) {
                throw new Exception('Usuário não encontrado');
            }

            // Verifica se já fez check-in hoje
            if ($this->checkInRepository->hasCheckedInToday($dto->user_id, $dto->empresa_id)) {
                throw new Exception('Você já fez check-in nesta empresa hoje');
            }

            // Calcula pontos
            $pontosBase = 10;
            $multiplicador = $empresa->points_multiplier ?? 1.0;
            $pontosGanhos = (int) ($pontosBase * $multiplicador);

            // Cria check-in
            $checkIn = $this->checkInRepository->create([
                'user_id' => $dto->user_id,
                'empresa_id' => $dto->empresa_id,
                'pontos' => $pontosGanhos,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'metodo' => $dto->metodo,
            ]);

            // Cria registro de pontos
            Ponto::create([
                'user_id' => $dto->user_id,
                'empresa_id' => $dto->empresa_id,
                'checkin_id' => $checkIn->id,
                'pontos' => $pontosGanhos,
                'tipo' => 'checkin',
                'descricao' => "Check-in em {$empresa->nome}",
            ]);

            // Adiciona pontos ao usuário
            $this->userRepository->addPoints($user, $pontosGanhos);

            DB::commit();

            Log::info('Check-in realizado', [
                'user_id' => $dto->user_id,
                'empresa_id' => $dto->empresa_id,
                'pontos_ganhos' => $pontosGanhos
            ]);

            return [
                'checkin' => $checkIn->load('empresa'),
                'pontos_ganhos' => $pontosGanhos,
                'pontos_totais' => $user->fresh()->pontos,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao realizar check-in', [
                'user_id' => $dto->user_id,
                'empresa_id' => $dto->empresa_id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Histórico de check-ins do usuário
     */
    public function getHistory(int $userId, int $perPage = 15): array
    {
        try {
            $checkIns = $this->checkInRepository->getUserCheckIns($userId, $perPage);
            $totalPontos = $this->checkInRepository->getTotalPoints($userId);

            return [
                'checkins' => $checkIns,
                'total_pontos' => $totalPontos,
                'total_checkins' => $this->checkInRepository->countUserCheckIns($userId)
            ];

        } catch (Exception $e) {
            Log::error('Erro ao buscar histórico', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
