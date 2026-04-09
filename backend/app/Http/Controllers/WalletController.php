<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * WalletController — Gera passes para Google Wallet e Apple Wallet (PKPass)
 *
 * Google Wallet: retorna JSON no formato "Generic Pass" que pode ser adicionado
 *                via URL jwt (link direto) ou via Google Wallet API.
 *
 * Apple Wallet:  retorna um .pkpass básico (ZIP de JSONs assinado).
 *                Exige certificado de desenvolvedor Apple para ser válido
 *                em produção. Em desenvolvimento, o arquivo gerado funciona
 *                em smartphones via "Arquivo > Abrir com Carteira".
 */
class WalletController extends Controller
{
    // ============================================================
    // GOOGLE WALLET
    // ============================================================

    /**
     * Retorna o JSON de um "Generic Pass" compatível com Google Wallet.
     * O cliente pode abrir o link:
     *   https://pay.google.com/gp/v/save/{jwt}
     * onde jwt é gerado com este payload.
     */
    public function googleWalletPass(Request $request)
    {
        $user    = Auth::user();
        $nivel   = ucfirst($user->nivel ?? 'Bronze');
        $pontos  = $user->pontos ?? 0;
        $issuerId = config('services.google_wallet.issuer_id', 'TEM_DE_TUDO');

        // Generic Pass Object (Google Wallet Generic API)
        $passObject = [
            'id'     => "{$issuerId}.cliente_{$user->id}",
            'classId' => "{$issuerId}.cartao_fidelidade",
            'genericType' => 'GENERIC_PRIVATE',
            'cardTitle' => [
                'defaultValue' => ['language' => 'pt-BR', 'value' => 'Tem de Tudo'],
            ],
            'subheader' => [
                'defaultValue' => ['language' => 'pt-BR', 'value' => 'Cartão Fidelidade'],
            ],
            'header' => [
                'defaultValue' => ['language' => 'pt-BR', 'value' => $user->name],
            ],
            'textModulesData' => [
                ['id' => 'pontos', 'header' => 'Pontos', 'body' => (string) $pontos],
                ['id' => 'nivel',  'header' => 'Nível',  'body' => $nivel],
            ],
            'barcode' => [
                'type'            => 'QR_CODE',
                'value'           => 'CLIENT_' . $user->id . '_' . md5($user->email),
                'alternateText'   => 'Apresente ao estabelecimento',
            ],
            'hexBackgroundColor' => $this->corPorNivel($user->nivel),
            'state' => 'ACTIVE',
        ];

        // URL para adicionar ao Google Wallet
        $addUrl = 'https://pay.google.com/gp/v/save/' . base64_encode(json_encode([
            'iss' => config('services.google_wallet.service_account', 'tem-de-tudo@example.com'),
            'aud' => 'google',
            'typ' => 'savetowallet',
            'iat' => time(),
            'payload' => ['genericObjects' => [$passObject]],
        ]));

        return response()->json([
            'success'     => true,
            'data' => [
                'pass_json' => $passObject,
                'add_url'   => $addUrl,
                'tipo'      => 'google_wallet',
            ],
        ]);
    }

    // ============================================================
    // APPLE WALLET (PKPass)
    // ============================================================

    /**
     * Gera um arquivo .pkpass para Apple Wallet.
     *
     * Estrutura mínima de um PKPass:
     *   pass.json        → metadados do cartão
     *   manifest.json    → SHA1 de cada arquivo
     *   signature        → assinatura PKCS7 (requer cert Apple)
     *
     * Em produção, assinar com openssl + certificado Apple Pass Type ID.
     * Aqui geramos o zip com os arquivos corretos (sem assinatura real)
     * para facilitar a integração posterior.
     */
    public function appleWalletPass(Request $request)
    {
        $user    = Auth::user();
        $nivel   = ucfirst($user->nivel ?? 'Bronze');
        $pontos  = $user->pontos ?? 0;
        $serialNumber = 'TDT-U' . $user->id . '-' . md5($user->email);

        $passJson = [
            'formatVersion'     => 1,
            'passTypeIdentifier' => config('services.apple_wallet.pass_type_id', 'pass.br.com.temdetudo'),
            'serialNumber'      => $serialNumber,
            'teamIdentifier'    => config('services.apple_wallet.team_id', 'TEAMID'),
            'organizationName'  => 'Tem de Tudo',
            'description'       => 'Cartão Fidelidade Tem de Tudo',
            'foregroundColor'   => 'rgb(255,255,255)',
            'backgroundColor'   => $this->rgbPorNivel($user->nivel),
            'generic' => [
                'primaryFields' => [
                    ['key' => 'pontos', 'label' => 'PONTOS', 'value' => $pontos],
                ],
                'secondaryFields' => [
                    ['key' => 'nivel', 'label' => 'NÍVEL', 'value' => $nivel],
                    ['key' => 'nome',  'label' => 'CLIENTE', 'value' => $user->name],
                ],
                'auxiliaryFields' => [],
                'backFields' => [
                    ['key' => 'info', 'label' => 'Informações', 'value' => 'Apresente o QR Code ao estabelecimento para acumular pontos.'],
                ],
            ],
            'barcode' => [
                'message'         => 'CLIENT_' . $user->id . '_' . md5($user->email),
                'format'          => 'PKBarcodeFormatQR',
                'messageEncoding' => 'iso-8859-1',
            ],
        ];

        $passJsonStr  = json_encode($passJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $manifestData = ['pass.json' => sha1($passJsonStr)];
        $manifestStr  = json_encode($manifestData);

        // Cria ZIP em memória
        $zip     = new \ZipArchive();
        $tmpFile = tempnam(sys_get_temp_dir(), 'pkpass_') . '.pkpass';
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('pass.json',     $passJsonStr);
        $zip->addFromString('manifest.json', $manifestStr);
        // signature vazia – substituir pela assinatura PKCS7 real em produção
        $zip->addFromString('signature', '');
        $zip->close();

        return response()->download($tmpFile, 'cartao-fidelidade.pkpass', [
            'Content-Type' => 'application/vnd.apple.pkpass',
        ])->deleteFileAfterSend(true);
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function corPorNivel(?string $nivel): string
    {
        return match (strtolower($nivel ?? '')) {
            'prata'   => '#93A3B8',
            'ouro'    => '#D4A017',
            'platina' => '#A8B820',
            default   => '#CD7F32', // bronze
        };
    }

    private function rgbPorNivel(?string $nivel): string
    {
        return match (strtolower($nivel ?? '')) {
            'prata'   => 'rgb(147,163,184)',
            'ouro'    => 'rgb(212,160,23)',
            'platina' => 'rgb(168,184,32)',
            default   => 'rgb(205,127,50)',
        };
    }
}
