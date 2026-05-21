<?php

namespace Database\Seeders;

use App\Models\Avaliacao;
use App\Models\BonusAdesao;
use App\Models\BonusAdesaoResgate;
use App\Models\BonusAniversario;
use App\Models\CartaoFidelidade;
use App\Models\CartaoFidelidadeMovimento;
use App\Models\CartaoFidelidadeProgresso;
use App\Models\Empresa;
use App\Models\InscricaoEmpresa;
use App\Models\LembreteAusencia;
use App\Models\LembreteEnvio;
use App\Models\NotificacaoPush;
use App\Models\Promocao;
use App\Models\PromocaoResgate;
use App\Models\User;
use App\Services\QRCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class I9PlusDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    /** @var array<string, array<string, bool>> */
    private array $columnsCache = [];

    public function run(): void
    {
        if (!$this->requiredTablesAvailable()) {
            $this->command?->warn('I9PlusDemoSeeder abortado: tabelas base ausentes. Rode as migrations do Laravel antes do seed.');

            return;
        }

        $today = now()->startOfDay();
        $month = (int) $today->month;
        $birthdayDay = min(18, (int) $today->daysInMonth);
        $mariaBirthday = Carbon::create(1994, $month, $birthdayDay, 0, 0, 0);

        $this->command?->info('Criando dados demo i9Plus...');

        $admin = $this->syncUser('admin@demo.local', [
            'name' => 'Admin Demo i9Plus',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'perfil' => 'admin',
            'status' => 'ativo',
            'telefone' => '(11) 99999-0000',
            'email_verified_at' => $today,
            'pontos' => 0,
            'pontos_lifetime' => 0,
            'nivel' => 'Admin',
        ]);

        $owners = [
            'malagueta' => $this->syncUser('malagueta@demo.local', [
                'name' => 'Camila Malagueta',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'empresa',
                'status' => 'ativo',
                'telefone' => '(11) 98888-1101',
                'email_verified_at' => $today,
            ]),
            'texano' => $this->syncUser('texano@demo.local', [
                'name' => 'Bruno Texano',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'empresa',
                'status' => 'ativo',
                'telefone' => '(11) 98888-1102',
                'email_verified_at' => $today,
            ]),
            'makoto' => $this->syncUser('makoto@demo.local', [
                'name' => 'Keiko Makoto',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'empresa',
                'status' => 'ativo',
                'telefone' => '(11) 98888-1103',
                'email_verified_at' => $today,
            ]),
            'florenza' => $this->syncUser('florenza@demo.local', [
                'name' => 'Laura Florenza',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'empresa',
                'status' => 'ativo',
                'telefone' => '(11) 98888-1104',
                'email_verified_at' => $today,
            ]),
            'pending' => $this->syncUser('pendente@demo.local', [
                'name' => 'Empresa Pendente Demo',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'empresa',
                'status' => 'pendente',
                'telefone' => '(11) 98888-1105',
                'email_verified_at' => $today,
            ]),
            'suspended' => $this->syncUser('suspensa@demo.local', [
                'name' => 'Empresa Suspensa Demo',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'empresa',
                'status' => 'bloqueado',
                'telefone' => '(11) 98888-1106',
                'email_verified_at' => $today,
            ]),
        ];

        $companies = [
            'malagueta' => $this->syncEmpresa('Malagueta Galpão', [
                'owner_id' => $owners['malagueta']->id,
                'categoria' => 'Restaurante',
                'ramo' => 'restaurante',
                'descricao' => 'Galpão gastronômico com almoço executivo, happy hour e fidelização por QR Code.',
                'telefone' => '(11) 4002-1101',
                'whatsapp' => '(11) 98888-2101',
                'instagram' => '@malaguetagalpao',
                'facebook' => 'malaguetagalpao',
                'endereco' => 'Rua do Mercado, 128 - Centro, São Paulo - SP',
                'cnpj' => '11.111.111/0001-11',
                'logo' => '/assets/images/company1.jpg',
                'points_multiplier' => 1.0,
                'ativo' => true,
                'status' => Empresa::STATUS_ACTIVE,
            ]),
            'texano' => $this->syncEmpresa('Texano Burger', [
                'owner_id' => $owners['texano']->id,
                'categoria' => 'Hamburgueria',
                'ramo' => 'hamburgueria',
                'descricao' => 'Hambúrguer artesanal, combos semanais e recompensas presenciais no balcão.',
                'telefone' => '(11) 4002-1102',
                'whatsapp' => '(11) 98888-2102',
                'instagram' => '@texanoburger',
                'facebook' => 'texanoburger',
                'endereco' => 'Av. Paulista, 940 - Bela Vista, São Paulo - SP',
                'cnpj' => '11.111.111/0001-22',
                'logo' => '/assets/images/company2.jpg',
                'points_multiplier' => 1.0,
                'ativo' => true,
                'status' => Empresa::STATUS_ACTIVE,
            ]),
            'makoto' => $this->syncEmpresa('Makoto Sushi', [
                'owner_id' => $owners['makoto']->id,
                'categoria' => 'Japonesa',
                'ramo' => 'japonesa',
                'descricao' => 'Sushi bar com menu executivo, promoções ativas e bônus de aniversário do mês.',
                'telefone' => '(11) 4002-1103',
                'whatsapp' => '(11) 98888-2103',
                'instagram' => '@makotosushi',
                'facebook' => 'makotosushi',
                'endereco' => 'Rua Harmonia, 55 - Vila Madalena, São Paulo - SP',
                'cnpj' => '11.111.111/0001-33',
                'logo' => '/assets/images/company3.jpg',
                'points_multiplier' => 1.0,
                'ativo' => true,
                'status' => Empresa::STATUS_ACTIVE,
            ]),
            'florenza' => $this->syncEmpresa('Florenza Boutique', [
                'owner_id' => $owners['florenza']->id,
                'categoria' => 'Moda/Beleza',
                'ramo' => 'moda',
                'descricao' => 'Boutique com benefícios recorrentes, mimo de aniversário e ações de fidelização.',
                'telefone' => '(11) 4002-1104',
                'whatsapp' => '(11) 98888-2104',
                'instagram' => '@florenzaboutique',
                'facebook' => 'florenzaboutique',
                'endereco' => 'Alameda das Flores, 210 - Jardins, São Paulo - SP',
                'cnpj' => '11.111.111/0001-44',
                'logo' => '/assets/images/company4.jpg',
                'points_multiplier' => 1.0,
                'ativo' => true,
                'status' => Empresa::STATUS_ACTIVE,
            ]),
            'pending' => $this->syncEmpresa('Empresa Pendente Demo', [
                'owner_id' => $owners['pending']->id,
                'categoria' => 'Serviços',
                'ramo' => 'servicos',
                'descricao' => 'Empresa cadastrada para demonstrar a fila de aprovação administrativa.',
                'telefone' => '(11) 4002-1105',
                'whatsapp' => '(11) 98888-2105',
                'instagram' => '@pendentedemo',
                'facebook' => 'pendentedemo',
                'endereco' => 'Rua de Teste, 10 - São Paulo - SP',
                'cnpj' => '11.111.111/0001-55',
                'logo' => '/assets/images/company1.jpg',
                'points_multiplier' => 1.0,
                'ativo' => false,
                'status' => Empresa::STATUS_PENDING,
            ]),
            'suspended' => $this->syncEmpresa('Empresa Suspensa Demo', [
                'owner_id' => $owners['suspended']->id,
                'categoria' => 'Serviços',
                'ramo' => 'servicos',
                'descricao' => 'Empresa suspensa para demonstrar governança e bloqueio operacional.',
                'telefone' => '(11) 4002-1106',
                'whatsapp' => '(11) 98888-2106',
                'instagram' => '@suspensademo',
                'facebook' => 'suspensademo',
                'endereco' => 'Rua de Teste, 99 - São Paulo - SP',
                'cnpj' => '11.111.111/0001-66',
                'logo' => '/assets/images/company2.jpg',
                'points_multiplier' => 1.0,
                'ativo' => false,
                'status' => Empresa::STATUS_SUSPENDED,
            ]),
        ];

        $clients = [
            'joao' => $this->syncUser('joao@demo.local', [
                'name' => 'João Cliente Demo',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'cliente',
                'status' => 'ativo',
                'telefone' => '(11) 97777-3001',
                'data_nascimento' => Carbon::create(1991, 8, 14, 0, 0, 0),
                'pontos' => 320,
                'pontos_lifetime' => 920,
                'nivel' => 'Prata',
                'email_verified_at' => $today,
            ]),
            'maria' => $this->syncUser('maria@demo.local', [
                'name' => 'Maria Aniversariante',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'cliente',
                'status' => 'ativo',
                'telefone' => '(11) 97777-3002',
                'data_nascimento' => $mariaBirthday,
                'pontos' => 180,
                'pontos_lifetime' => 610,
                'nivel' => 'Prata',
                'email_verified_at' => $today,
            ]),
            'pedro' => $this->syncUser('pedro@demo.local', [
                'name' => 'Pedro Inativo',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'cliente',
                'status' => 'ativo',
                'telefone' => '(11) 97777-3003',
                'data_nascimento' => Carbon::create(1988, 11, 6, 0, 0, 0),
                'pontos' => 40,
                'pontos_lifetime' => 260,
                'nivel' => 'Bronze',
                'email_verified_at' => $today,
            ]),
            'ana' => $this->syncUser('ana@demo.local', [
                'name' => 'Ana Fidelidade',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'cliente',
                'status' => 'ativo',
                'telefone' => '(11) 97777-3004',
                'data_nascimento' => Carbon::create(1996, 3, 23, 0, 0, 0),
                'pontos' => 560,
                'pontos_lifetime' => 1480,
                'nivel' => 'Ouro',
                'email_verified_at' => $today,
            ]),
            'push_iphone' => $this->syncUser('cliente.push@demo.local', [
                'name' => 'Cliente Push iPhone',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'perfil' => 'cliente',
                'status' => 'ativo',
                'telefone' => '(11) 97777-3005',
                'data_nascimento' => Carbon::create(1993, 5, 12, 0, 0, 0),
                'pontos' => 90,
                'pontos_lifetime' => 180,
                'nivel' => 'Bronze',
                'email_verified_at' => $today,
            ]),
        ];

        $activeCompanyKeys = ['malagueta', 'texano', 'makoto', 'florenza'];
        $qrCodeService = app(QRCodeService::class);

        foreach ($activeCompanyKeys as $companyKey) {
            $qrCodeService->gerarQRCodeEmpresa($companies[$companyKey]);
        }

        $this->syncInscricao($clients['joao'], $companies['malagueta'], $today->copy()->subDays(42), $today->copy()->subDays(4), true);
        $this->syncInscricao($clients['ana'], $companies['malagueta'], $today->copy()->subDays(5), $today->copy()->subDay(), false);
        $this->syncInscricao($clients['maria'], $companies['malagueta'], $today->copy()->subDays(12), $today->copy()->subDays(7), false);
        $this->syncInscricao($clients['pedro'], $companies['malagueta'], $today->copy()->subDays(64), $today->copy()->subDays(46), false);
        $this->syncInscricao($clients['pedro'], $companies['texano'], $today->copy()->subDays(64), $today->copy()->subDays(46), false);
        $this->syncInscricao($clients['joao'], $companies['texano'], $today->copy()->subDays(20), $today->copy()->subDays(10), false);
        $this->syncInscricao($clients['maria'], $companies['makoto'], $today->copy()->subDays(3), $today->copy()->subDays(2), false);
        $this->syncInscricao($clients['ana'], $companies['florenza'], $today->copy()->subDays(18), $today->copy()->subDays(6), false);
        $this->syncInscricao($clients['maria'], $companies['florenza'], $today->copy()->subDays(28), $today->copy()->subDays(8), false);
        $this->syncInscricao($clients['push_iphone'], $companies['malagueta'], $today->copy()->subDays(9), $today->copy()->subDays(2), false);

        $bonusMap = [];
        foreach ($activeCompanyKeys as $companyKey) {
            $bonusMap[$companyKey] = $this->syncBonusAdesao($companies[$companyKey], [
                'titulo' => 'Ganhe 10% na primeira compra',
                'descricao' => 'Apresente seu QR Code no estabelecimento para validar seu bônus.',
                'tipo_desconto' => 'porcentagem',
                'valor_desconto' => 10,
                'imagem' => $companies[$companyKey]->logo ?: '/img/icon-192.png',
                'ativo' => true,
                'data_expiracao' => $today->copy()->addMonths(6),
                'limite_por_cliente' => 1,
                'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
                'ordem' => 1,
                'termos' => 'Valido para a primeira compra apos vinculo ativo via QR Code.',
            ]);
        }

        $bonusMap['malagueta'] = $this->syncBonusAdesao($companies['malagueta'], [
            'titulo' => 'Bem-vindo ao programa de fidelidade',
            'descricao' => 'Apresente seu QR Code e valide 10% de desconto na primeira compra.',
            'tipo_desconto' => 'porcentagem',
            'valor_desconto' => 10,
            'imagem' => $companies['malagueta']->logo ?: '/img/icon-192.png',
            'ativo' => true,
            'data_expiracao' => $today->copy()->addMonths(6),
            'limite_por_cliente' => 1,
            'tipo' => BonusAdesao::TYPE_ADHESION_BONUS,
            'ordem' => 1,
            'termos' => 'Valido uma unica vez e somente com validacao presencial da equipe da loja.',
        ]);

        $this->syncBonusAdesaoResgate(
            $bonusMap['malagueta'],
            $companies['malagueta'],
            $clients['joao'],
            $owners['malagueta'],
            $today->copy()->subDays(28)
        );

        $cards = [
            'malagueta' => $this->syncCartaoFidelidade($companies['malagueta'], [
                'titulo' => 'Cartao Fidelidade',
                'descricao' => 'Ganhe 1 ponto a cada visita e troque por combo ou desconto especial.',
                'regra_ganho' => 'Ganhe 1 ponto a cada visita',
                'pontos_por_visita' => 1,
                'pontos_necessarios' => 15,
                'meta_pontos' => 15,
                'recompensa' => 'Combo ou desconto especial',
                'recompensa_descricao' => 'Combo ou desconto especial',
                'ativo' => true,
            ]),
            'texano' => $this->syncCartaoFidelidade($companies['texano'], [
                'titulo' => 'Cartão Fidelidade',
                'descricao' => 'Clientes frequentes acumulam pontos e resgatam um combo especial.',
                'regra_ganho' => 'Ganhe 1 ponto a cada visita',
                'pontos_por_visita' => 1,
                'pontos_necessarios' => 15,
                'meta_pontos' => 15,
                'recompensa' => 'Batata grande ou refri cortesia',
                'recompensa_descricao' => 'Batata grande ou refri cortesia',
                'ativo' => true,
            ]),
            'makoto' => $this->syncCartaoFidelidade($companies['makoto'], [
                'titulo' => 'Cartão Fidelidade',
                'descricao' => 'Acumule visitas para liberar uma sobremesa japonesa.',
                'regra_ganho' => 'Ganhe 1 ponto a cada visita',
                'pontos_por_visita' => 1,
                'pontos_necessarios' => 15,
                'meta_pontos' => 15,
                'recompensa' => 'Hot roll ou sobremesa cortesia',
                'recompensa_descricao' => 'Hot roll ou sobremesa cortesia',
                'ativo' => true,
            ]),
            'florenza' => $this->syncCartaoFidelidade($companies['florenza'], [
                'titulo' => 'Cartão Fidelidade',
                'descricao' => 'Visitas recorrentes liberam um mimo de styling na próxima compra.',
                'regra_ganho' => 'Ganhe 1 ponto a cada visita',
                'pontos_por_visita' => 1,
                'pontos_necessarios' => 15,
                'meta_pontos' => 15,
                'recompensa' => 'Mimo de styling ou voucher especial',
                'recompensa_descricao' => 'Mimo de styling ou voucher especial',
                'ativo' => true,
            ]),
        ];

        $this->syncCartaoProgresso($clients['maria'], $cards['makoto'], 0, $today->copy()->subDays(2));
        $this->syncCartaoProgresso($clients['joao'], $cards['malagueta'], 3, $today->copy()->subDays(4));
        $this->syncCartaoProgresso($clients['ana'], $cards['malagueta'], 14, $today->copy()->subDay());
        $this->syncCartaoProgresso($clients['pedro'], $cards['malagueta'], 1, $today->copy()->subDays(46));
        $this->syncCartaoProgresso($clients['pedro'], $cards['texano'], 14, $today->copy()->subDays(46));
        $this->syncCartaoProgresso($clients['ana'], $cards['florenza'], 16, $today->copy()->subDays(6));

        $this->syncCartaoMovimento($cards['malagueta'], $companies['malagueta'], $clients['joao'], 3, CartaoFidelidadeMovimento::TYPE_EARNED, '[DEMO] Cliente iniciando o cartao fidelidade', $owners['malagueta']);
        $this->syncCartaoMovimento($cards['malagueta'], $companies['malagueta'], $clients['ana'], 14, CartaoFidelidadeMovimento::TYPE_EARNED, '[DEMO] Cliente proxima da recompensa', $owners['malagueta']);
        $this->syncCartaoMovimento($cards['malagueta'], $companies['malagueta'], $clients['pedro'], 15, CartaoFidelidadeMovimento::TYPE_REDEEMED, '[DEMO] Recompensa validada presencialmente', $owners['malagueta']);
        $this->syncCartaoMovimento($cards['texano'], $companies['texano'], $clients['pedro'], 14, CartaoFidelidadeMovimento::TYPE_EARNED, '[DEMO] Cliente próximo do resgate', $owners['texano']);
        $this->syncCartaoMovimento($cards['florenza'], $companies['florenza'], $clients['ana'], 16, CartaoFidelidadeMovimento::TYPE_EARNED, '[DEMO] Cliente com recompensa disponível', $owners['florenza']);

        $promotions = [
            'malagueta_ready' => $this->syncPromocao($companies['malagueta'], [
                'titulo' => 'Combo especial de hoje',
                'descricao' => 'Apresente seu QR Code e aproveite uma condicao exclusiva.',
                'imagem' => '/assets/images/company1.jpg',
                'notification_title' => 'Combo especial de hoje',
                'notification_body' => 'Apresente seu QR Code e aproveite uma condicao exclusiva.',
                'desconto' => 12,
                'desconto_percentual' => 12,
                'pontos_necessarios' => 0,
                'data_inicio' => $today->copy()->subHours(2),
                'data_fim' => $today->copy()->addDays(7),
                'validade' => $today->copy()->addDays(7),
                'status' => Promocao::STATUS_ACTIVE,
                'ativo' => true,
                'data_envio' => null,
                'total_envios' => 0,
                'visualizacoes' => 0,
                'resgates' => 0,
                'usos' => 0,
                'quantidade_disponivel' => 50,
                'qtd_disponivel' => 50,
                'qtd_resgatada' => 0,
                'limite_por_usuario' => 1,
            ]),
            'malagueta_history' => $this->syncPromocao($companies['malagueta'], [
                'titulo' => 'Rodada anterior: sobremesa cortesia',
                'descricao' => 'Campanha usada na semana passada para demonstrar historico da empresa.',
                'imagem' => '/assets/images/company1.jpg',
                'notification_title' => 'Sobremesa cortesia no Malagueta',
                'notification_body' => 'Mostre seu QR Code no balcao para validar a sobremesa cortesia.',
                'desconto' => 0,
                'pontos_necessarios' => 0,
                'data_inicio' => $today->copy()->subDays(16),
                'data_fim' => $today->copy()->subDays(8),
                'validade' => $today->copy()->subDays(8),
                'status' => Promocao::STATUS_PAUSED,
                'ativo' => false,
                'data_envio' => $today->copy()->subDays(12),
                'total_envios' => 14,
                'visualizacoes' => 32,
                'resgates' => 5,
                'usos' => 3,
                'quantidade_disponivel' => 30,
                'qtd_disponivel' => 30,
                'qtd_resgatada' => 3,
                'limite_por_usuario' => 1,
            ]),
            'malagueta_push_test' => $this->syncPromocao($companies['malagueta'], [
                'titulo' => 'Teste de Push',
                'descricao' => 'Promocao criada para validar notificacao no celular.',
                'imagem' => '/assets/images/company1.jpg',
                'notification_title' => 'Teste de notificacao',
                'notification_body' => 'Se voce recebeu esta mensagem, o push esta funcionando.',
                'desconto' => 0,
                'desconto_percentual' => 0,
                'pontos_necessarios' => 0,
                'data_inicio' => $today->copy()->subMinutes(30),
                'data_fim' => $today->copy()->addDays(14),
                'validade' => $today->copy()->addDays(14),
                'status' => Promocao::STATUS_ACTIVE,
                'ativo' => true,
                'data_envio' => null,
                'total_envios' => 0,
                'visualizacoes' => 0,
                'resgates' => 0,
                'usos' => 0,
                'quantidade_disponivel' => 99,
                'qtd_disponivel' => 99,
                'qtd_resgatada' => 0,
                'limite_por_usuario' => 1,
            ]),
            'texano_combo' => $this->syncPromocao($companies['texano'], [
                'titulo' => 'Combo especial da semana',
                'descricao' => 'Combo com burger, batata e refri validado no balcão para clientes vinculados.',
                'imagem' => '/assets/images/company2.jpg',
                'notification_title' => 'Combo da semana no Texano',
                'notification_body' => 'Liberado para clientes vinculados. Mostre seu QR Code no balcão.',
                'desconto' => 15,
                'desconto_percentual' => 15,
                'pontos_necessarios' => 0,
                'data_inicio' => $today->copy()->subDays(2),
                'data_fim' => $today->copy()->addDays(10),
                'validade' => $today->copy()->addDays(10),
                'status' => Promocao::STATUS_ACTIVE,
                'ativo' => true,
                'data_envio' => $today->copy()->subDays(2),
                'total_envios' => 11,
                'visualizacoes' => 31,
                'resgates' => 4,
                'usos' => 2,
                'quantidade_disponivel' => 40,
                'qtd_disponivel' => 40,
                'qtd_resgatada' => 2,
                'limite_por_usuario' => 1,
            ]),
            'makoto_birthday' => $this->syncPromocao($companies['makoto'], [
                'titulo' => 'Aniversariante ganha cortesia',
                'descricao' => 'Clientes do mês podem validar uma cortesia especial com o time do salão.',
                'imagem' => '/assets/images/company3.jpg',
                'notification_title' => 'Tem cortesia no Makoto',
                'notification_body' => 'Se for seu mês, apresente o QR Code e valide a cortesia.',
                'desconto' => 0,
                'pontos_necessarios' => 0,
                'data_inicio' => $today->copy()->subDays(3),
                'data_fim' => $today->copy()->addDays(12),
                'validade' => $today->copy()->addDays(12),
                'status' => Promocao::STATUS_ACTIVE,
                'ativo' => true,
                'data_envio' => $today->copy()->subDays(3),
                'total_envios' => 9,
                'visualizacoes' => 24,
                'resgates' => 3,
                'usos' => 1,
                'quantidade_disponivel' => 30,
                'qtd_disponivel' => 30,
                'qtd_resgatada' => 1,
                'limite_por_usuario' => 1,
            ]),
            'florenza_week' => $this->syncPromocao($companies['florenza'], [
                'titulo' => 'Mimo especial da semana',
                'descricao' => 'Clientes fidelizados liberam um mimo na prova de look desta semana.',
                'imagem' => '/assets/images/company4.jpg',
                'notification_title' => 'Mimo liberado na Florenza',
                'notification_body' => 'Passe na loja, mostre seu QR Code e valide o benefício.',
                'desconto' => 0,
                'pontos_necessarios' => 0,
                'data_inicio' => $today->copy()->subDay(),
                'data_fim' => $today->copy()->addDays(8),
                'validade' => $today->copy()->addDays(8),
                'status' => Promocao::STATUS_ACTIVE,
                'ativo' => true,
                'data_envio' => $today->copy()->subDay(),
                'total_envios' => 7,
                'visualizacoes' => 19,
                'resgates' => 2,
                'usos' => 1,
                'quantidade_disponivel' => 20,
                'qtd_disponivel' => 20,
                'qtd_resgatada' => 1,
                'limite_por_usuario' => 1,
            ]),
        ];

        $this->syncPromocaoResgate($promotions['malagueta_history'], $companies['malagueta'], $clients['joao'], $owners['malagueta'], $today->copy()->subDays(12));
        $this->syncPromocaoResgate($promotions['texano_combo'], $companies['texano'], $clients['pedro'], $owners['texano'], $today->copy()->subDays(12));

        $birthdayBonuses = [];
        foreach ($activeCompanyKeys as $companyKey) {
            $birthdayBonuses[$companyKey] = $this->syncBonusAniversario($companies[$companyKey], [
                'titulo' => 'Parabéns! Seu benefício do mês está liberado',
                'descricao' => 'Cliente aniversariante valida o benefício presencialmente mostrando o QR Code.',
                'presente' => 'Cortesia especial do mês',
                'imagem' => $companies[$companyKey]->logo ?: '/img/icon-192.png',
                'dias_validade' => 30,
                'notification_title' => 'Tem mimo de aniversário esperando por você',
                'notification_body' => 'Passe na loja neste mês e valide seu presente com o QR Code do cliente.',
                'ativo' => true,
            ]);
        }


        $birthdayBonuses['malagueta'] = $this->syncBonusAniversario($companies['malagueta'], [
            'titulo' => 'FELIZ ANIVERSARIO!',
            'descricao' => 'Comemore seu aniversario conosco e ganhe uma cortesia validada presencialmente.',
            'presente' => 'Sobremesa cortesia ou drink sem alcool',
            'imagem' => $companies['malagueta']->logo ?: '/img/icon-192.png',
            'dias_validade' => 30,
            'notification_title' => 'FELIZ ANIVERSARIO!',
            'notification_body' => 'Comemore seu aniversario conosco e ganhe uma cortesia.',
            'ativo' => true,
        ]);

        $reminders = [];
        foreach ($activeCompanyKeys as $companyKey) {
            $reminders[$companyKey] = $this->syncLembrete($companies[$companyKey], [
                'dias_ausencia' => 30,
                'dias_sem_visita' => 30,
                'titulo' => 'Sentimos sua falta!',
                'mensagem' => 'Sentimos sua falta! Volte e aproveite uma condição especial.',
                'ativo' => true,
            ]);
        }


        $reminders['malagueta'] = $this->syncLembrete($companies['malagueta'], [
            'dias_ausencia' => 30,
            'dias_sem_visita' => 30,
            'titulo' => 'Sentimos sua falta!',
            'mensagem' => 'Sentimos sua falta! Volte ao Malagueta e aproveite uma condicao especial.',
            'ativo' => true,
        ]);
        $this->syncLembreteEnvio(
            $reminders['texano'],
            $companies['texano'],
            $clients['pedro'],
            $today->copy()->subDays(46),
            LembreteEnvio::STATUS_SENT,
            $today->copy()->subDay()
        );

        $this->syncAvaliacao($clients['joao'], $companies['malagueta'], 5, 'Comida ótima e validação rápida no caixa.');
        $this->syncAvaliacao($clients['ana'], $companies['malagueta'], 4, 'Programa funciona bem e a equipe orienta o resgate.');
        $this->syncAvaliacao($clients['maria'], $companies['malagueta'], 5, 'Ambiente bonito e benefícios bem separados no app.');
        $this->syncAvaliacao($clients['pedro'], $companies['texano'], 4, 'Hambúrguer muito bom e fluxo de promoções claro.');
        $this->syncAvaliacao($clients['maria'], $companies['makoto'], 5, 'Sushi fresco e bônus de aniversário fácil de entender.');
        $this->syncAvaliacao($clients['ana'], $companies['florenza'], 5, 'Visual premium e recompensa de fidelidade convincente.');

        foreach ($activeCompanyKeys as $companyKey) {
            $companies[$companyKey]->refresh()->atualizarAvaliacaoMedia();
        }

        $this->syncNotificacao($clients['joao'], $companies['malagueta'], [
            'promocao_id' => $promotions['malagueta_history']->id,
            'tipo' => 'promocao',
            'titulo' => $promotions['malagueta_history']->notificationTitle(),
            'mensagem' => $promotions['malagueta_history']->notificationBody(),
            'imagem' => $promotions['malagueta_history']->imagem,
            'status' => 'sent',
            'enviado' => true,
            'data_envio' => $today->copy()->subDays(12),
        ]);

        $this->syncNotificacao($clients['maria'], $companies['makoto'], [
            'bonus_aniversario_id' => $birthdayBonuses['makoto']->id,
            'tipo' => 'aniversario',
            'titulo' => $birthdayBonuses['makoto']->notificationTitle(),
            'mensagem' => $birthdayBonuses['makoto']->notificationBody(),
            'imagem' => $birthdayBonuses['makoto']->imagem,
            'status' => 'sent',
            'enviado' => true,
            'data_envio' => $today->copy()->subDays(2),
        ]);

        $this->syncNotificacao($clients['pedro'], $companies['texano'], [
            'lembrete_id' => $reminders['texano']->id,
            'tipo' => 'lembrete',
            'titulo' => $reminders['texano']->titulo,
            'mensagem' => $reminders['texano']->mensagem,
            'imagem' => $companies['texano']->logo,
            'status' => 'sent',
            'enviado' => true,
            'data_envio' => $today->copy()->subDay(),
        ]);

        $this->syncNotificacao($clients['ana'], $companies['florenza'], [
            'promocao_id' => $promotions['florenza_week']->id,
            'tipo' => 'promocao',
            'titulo' => $promotions['florenza_week']->notificationTitle(),
            'mensagem' => $promotions['florenza_week']->notificationBody(),
            'imagem' => $promotions['florenza_week']->imagem,
            'status' => 'sent',
            'enviado' => true,
            'data_envio' => $today->copy()->subDay(),
        ]);

        $this->command?->info('Seed demo i9Plus pronta.');
        $this->command?->line('Admin demo: admin@demo.local / password');
        $this->command?->line('Cliente demo: joao@demo.local / password');
        $this->command?->line('Empresa demo: malagueta@demo.local / password');
        $this->command?->line('Cliente QR canônico é gerado sob demanda por /api/cliente/meu-qrcode.');

        unset($admin);
    }

    private function requiredTablesAvailable(): bool
    {
        foreach ([
            'users',
            'empresas',
            'qr_codes',
            'inscricoes_empresa',
            'bonus_adesao',
            BonusAdesaoResgate::TABLE_CANONICAL,
            'cartoes_fidelidade',
            'cartoes_fidelidade_progresso',
            'cartoes_fidelidade_movimentos',
            'promocoes',
            'promocao_resgates',
            'bonus_aniversario',
            'lembretes_ausencia',
            'lembrete_envios',
            'avaliacoes',
            'notificacoes_push',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function syncUser(string $email, array $attributes): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $this->fillModel($user, array_merge(['email' => $email], $attributes));
        $user->save();

        return $user->refresh();
    }

    private function syncEmpresa(string $nome, array $attributes): Empresa
    {
        $empresa = Empresa::firstOrNew(['nome' => $nome]);
        $this->fillModel($empresa, array_merge(['nome' => $nome], $attributes));
        $empresa->save();

        return $empresa->refresh();
    }

    private function syncInscricao(User $user, Empresa $empresa, Carbon $dataInscricao, ?Carbon $ultimaVisita, bool $bonusResgatado): InscricaoEmpresa
    {
        $inscricao = InscricaoEmpresa::firstOrNew([
            'user_id' => $user->id,
            'empresa_id' => $empresa->id,
        ]);

        $this->fillModel($inscricao, [
            'user_id' => $user->id,
            'empresa_id' => $empresa->id,
            'data_inscricao' => $dataInscricao,
            'ultima_visita' => $ultimaVisita,
            'bonus_adesao_resgatado' => $bonusResgatado,
        ]);
        $inscricao->save();

        return $inscricao->refresh();
    }

    private function syncBonusAdesao(Empresa $empresa, array $attributes): BonusAdesao
    {
        $bonus = BonusAdesao::firstOrNew(['empresa_id' => $empresa->id]);
        $this->fillModel($bonus, array_merge(['empresa_id' => $empresa->id], $attributes));
        $bonus->save();

        return $bonus->refresh();
    }

    private function syncBonusAdesaoResgate(
        BonusAdesao $bonus,
        Empresa $empresa,
        User $cliente,
        User $validator,
        Carbon $redeemedAt
    ): BonusAdesaoResgate {
        $resgate = BonusAdesaoResgate::firstOrNew([
            'bonus_id' => $bonus->id,
            'user_id' => $cliente->id,
        ]);

        $this->fillModel($resgate, [
            'bonus_id' => $bonus->id,
            'empresa_id' => $empresa->id,
            'user_id' => $cliente->id,
            'status' => BonusAdesaoResgate::STATUS_REDEEMED,
            'validated_by' => $validator->id,
            'redeemed_at' => $redeemedAt,
            'resgatado' => true,
            'data_resgate' => $redeemedAt,
            'pontos' => 0,
        ]);
        $resgate->save();

        return $resgate->refresh();
    }

    private function syncCartaoFidelidade(Empresa $empresa, array $attributes): CartaoFidelidade
    {
        $cartao = CartaoFidelidade::firstOrNew([
            'empresa_id' => $empresa->id,
            'titulo' => (string) ($attributes['titulo'] ?? 'Cartão Fidelidade'),
        ]);

        $this->fillModel($cartao, array_merge([
            'empresa_id' => $empresa->id,
            'titulo' => $attributes['titulo'] ?? 'Cartão Fidelidade',
        ], $attributes));
        $cartao->save();

        return $cartao->refresh();
    }

    private function syncCartaoProgresso(User $cliente, CartaoFidelidade $cartao, int $pontosAtuais, Carbon $ultimoPonto): CartaoFidelidadeProgresso
    {
        $progresso = CartaoFidelidadeProgresso::firstOrNew([
            'user_id' => $cliente->id,
            'cartao_fidelidade_id' => $cartao->id,
        ]);

        $this->fillModel($progresso, [
            'user_id' => $cliente->id,
            'cartao_fidelidade_id' => $cartao->id,
            'pontos_atuais' => $pontosAtuais,
            'vezes_resgatado' => $pontosAtuais > 15 ? 1 : 0,
            'ultimo_ponto' => $ultimoPonto,
        ]);
        $progresso->save();

        return $progresso->refresh();
    }

    private function syncCartaoMovimento(
        CartaoFidelidade $cartao,
        Empresa $empresa,
        User $cliente,
        int $pontos,
        string $tipo,
        string $descricao,
        User $autor
    ): CartaoFidelidadeMovimento {
        $movimento = CartaoFidelidadeMovimento::firstOrNew([
            'cartao_fidelidade_id' => $cartao->id,
            'empresa_id' => $empresa->id,
            'user_id' => $cliente->id,
            'tipo' => $tipo,
            'descricao' => $descricao,
        ]);

        $this->fillModel($movimento, [
            'cartao_fidelidade_id' => $cartao->id,
            'empresa_id' => $empresa->id,
            'user_id' => $cliente->id,
            'pontos' => $pontos,
            'tipo' => $tipo,
            'descricao' => $descricao,
            'created_by' => $autor->id,
        ]);
        $movimento->save();

        return $movimento->refresh();
    }

    private function syncPromocao(Empresa $empresa, array $attributes): Promocao
    {
        $titulo = (string) ($attributes['titulo'] ?? 'Promocao demo');
        $promocao = Promocao::firstOrNew([
            'empresa_id' => $empresa->id,
            'titulo' => $titulo,
        ]);

        $this->fillModel($promocao, array_merge([
            'empresa_id' => $empresa->id,
            'titulo' => $titulo,
        ], $attributes));
        $promocao->save();

        return $promocao->refresh();
    }

    private function syncPromocaoResgate(
        Promocao $promocao,
        Empresa $empresa,
        User $cliente,
        User $validator,
        Carbon $redeemedAt
    ): PromocaoResgate {
        $resgate = PromocaoResgate::firstOrNew([
            'promocao_id' => $promocao->id,
            'user_id' => $cliente->id,
        ]);

        $this->fillModel($resgate, [
            'promocao_id' => $promocao->id,
            'empresa_id' => $empresa->id,
            'user_id' => $cliente->id,
            'status' => PromocaoResgate::STATUS_REDEEMED,
            'redeemed_at' => $redeemedAt,
            'validated_by' => $validator->id,
        ]);
        $resgate->save();

        return $resgate->refresh();
    }

    private function syncBonusAniversario(Empresa $empresa, array $attributes): BonusAniversario
    {
        $bonus = BonusAniversario::firstOrNew([
            'empresa_id' => $empresa->id,
            'titulo' => (string) ($attributes['titulo'] ?? 'Parabéns! Seu benefício do mês está liberado'),
        ]);

        $this->fillModel($bonus, array_merge([
            'empresa_id' => $empresa->id,
            'titulo' => $attributes['titulo'] ?? 'Parabéns! Seu benefício do mês está liberado',
        ], $attributes));
        $bonus->save();

        return $bonus->refresh();
    }

    private function syncLembrete(Empresa $empresa, array $attributes): LembreteAusencia
    {
        $lembrete = LembreteAusencia::firstOrNew(['empresa_id' => $empresa->id]);
        $this->fillModel($lembrete, array_merge(['empresa_id' => $empresa->id], $attributes));
        $lembrete->save();

        return $lembrete->refresh();
    }

    private function syncLembreteEnvio(
        LembreteAusencia $lembrete,
        Empresa $empresa,
        User $cliente,
        Carbon $referenceLastVisitAt,
        string $status,
        Carbon $sentAt
    ): LembreteEnvio {
        $envio = LembreteEnvio::firstOrNew([
            'lembrete_id' => $lembrete->id,
            'user_id' => $cliente->id,
            'reference_last_visit_at' => $referenceLastVisitAt,
        ]);

        $this->fillModel($envio, [
            'lembrete_id' => $lembrete->id,
            'empresa_id' => $empresa->id,
            'user_id' => $cliente->id,
            'reference_last_visit_at' => $referenceLastVisitAt,
            'status' => $status,
            'sent_at' => $sentAt,
        ]);
        $envio->save();

        return $envio->refresh();
    }

    private function syncAvaliacao(User $cliente, Empresa $empresa, int $estrelas, string $comentario): Avaliacao
    {
        $avaliacao = Avaliacao::firstOrNew([
            'user_id' => $cliente->id,
            'empresa_id' => $empresa->id,
        ]);

        $this->fillModel($avaliacao, [
            'user_id' => $cliente->id,
            'empresa_id' => $empresa->id,
            'estrelas' => $estrelas,
            'comentario' => $comentario,
        ]);
        $avaliacao->save();

        return $avaliacao->refresh();
    }

    private function syncNotificacao(User $cliente, Empresa $empresa, array $attributes): NotificacaoPush
    {
        $tipo = (string) ($attributes['tipo'] ?? 'promocao');
        $titulo = (string) ($attributes['titulo'] ?? 'Notificacao demo');
        $notificacao = NotificacaoPush::firstOrNew([
            'user_id' => $cliente->id,
            'empresa_id' => $empresa->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
        ]);

        $this->fillModel($notificacao, array_merge([
            'user_id' => $cliente->id,
            'empresa_id' => $empresa->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
        ], $attributes));
        $notificacao->save();

        return $notificacao->refresh();
    }

    private function fillModel(Model $model, array $attributes): void
    {
        $model->forceFill($this->filterColumns($model->getTable(), $attributes));
    }

    private function filterColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return array_intersect_key($data, $this->tableColumns($table));
    }

    /**
     * @return array<string, bool>
     */
    private function tableColumns(string $table): array
    {
        if (!isset($this->columnsCache[$table])) {
            $this->columnsCache[$table] = array_fill_keys(Schema::getColumnListing($table), true);
        }

        return $this->columnsCache[$table];
    }
}
