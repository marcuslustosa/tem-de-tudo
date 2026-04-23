<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;
use Carbon\Carbon;

class BannersSeeder extends Seeder
{
    /**
     * Popula banners de demonstração para o sistema
     */
    public function run(): void
    {
        $this->command->info('🎨 Criando banners de demonstração...');

        $banners = [
            [
                'title' => 'Bem-vindo ao Tem de Tudo!',
                'image_url' => 'https://picsum.photos/seed/banner1/1200/400',
                'link' => '/parceiros_tem_de_tudo.html',
                'active' => true,
                'position' => 1,
                'starts_at' => Carbon::now()->subDays(7),
                'ends_at' => Carbon::now()->addMonths(3),
                'payload' => json_encode([
                    'type' => 'promotional',
                    'color' => '#E10098',
                    'cta' => 'Ver Parceiros'
                ])
            ],
            [
                'title' => 'Acumule Pontos e Ganhe Recompensas',
                'image_url' => 'https://picsum.photos/seed/banner2/1200/400',
                'link' => '/recompensas.html',
                'active' => true,
                'position' => 2,
                'starts_at' => Carbon::now()->subDays(5),
                'ends_at' => Carbon::now()->addMonths(2),
                'payload' => json_encode([
                    'type' => 'informational',
                    'color' => '#7A2C8F',
                    'cta' => 'Ver Recompensas'
                ])
            ],
            [
                'title' => 'Promoção Especial - Dobro de Pontos!',
                'image_url' => 'https://picsum.photos/seed/banner3/1200/400',
                'link' => '/parceiros_tem_de_tudo.html',
                'active' => true,
                'position' => 3,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addDays(30),
                'payload' => json_encode([
                    'type' => 'campaign',
                    'color' => '#00BCD4',
                    'cta' => 'Participar Agora',
                    'multiplier' => 2
                ])
            ],
            [
                'title' => 'Seja um Parceiro VIPUS',
                'image_url' => 'https://picsum.photos/seed/banner4/1200/400',
                'link' => '/criar_conta.html?tipo=empresa',
                'active' => true,
                'position' => 4,
                'starts_at' => Carbon::now()->subDays(10),
                'ends_at' => null, // Banner permanente
                'payload' => json_encode([
                    'type' => 'registration',
                    'color' => '#7A2C8F',
                    'cta' => 'Cadastre sua Empresa'
                ])
            ],
            [
                'title' => 'Convide Amigos e Ganhe Bônus',
                'image_url' => 'https://picsum.photos/seed/banner5/1200/400',
                'link' => '/meu_perfil.html',
                'active' => false, // Banner inativo para demo
                'position' => 5,
                'starts_at' => Carbon::now()->addDays(5),
                'ends_at' => Carbon::now()->addMonths(1),
                'payload' => json_encode([
                    'type' => 'referral',
                    'color' => '#E10098',
                    'cta' => 'Convidar Agora',
                    'bonus_points' => 100
                ])
            ],
            [
                'title' => 'Download o App VIPUS',
                'image_url' => 'https://picsum.photos/seed/banner6/1200/400',
                'link' => null, // Link externo pode ser adicionado
                'active' => true,
                'position' => 6,
                'starts_at' => Carbon::now(),
                'ends_at' => null,
                'payload' => json_encode([
                    'type' => 'app_download',
                    'color' => '#00BCD4',
                    'cta' => 'Baixar App',
                    'platforms' => ['ios', 'android']
                ])
            ]
        ];

        foreach ($banners as $bannerData) {
            Banner::create($bannerData);
            $this->command->info("  ✓ Banner criado: {$bannerData['title']}");
        }

        $activeBanners = Banner::where('active', true)->count();
        $totalBanners = Banner::count();

        $this->command->info("✅ {$totalBanners} banners criados ({$activeBanners} ativos)");
    }
}
