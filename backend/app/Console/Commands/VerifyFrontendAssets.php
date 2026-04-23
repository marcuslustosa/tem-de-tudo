<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class VerifyFrontendAssets extends Command
{
    protected $signature = 'app:verify-frontend-assets {--fix : Corrige arquivos conhecidos quando possivel}';
    protected $description = 'Valida ativos visuais criticos do frontend para deploy em producao';

    public function handle(): int
    {
        $publicPath = public_path();
        $fix = (bool) $this->option('fix');

        $this->info('Validando ativos visuais...');

        $this->ensureCriticalAssets($publicPath, $fix);
        $missingReferences = $this->findMissingAssetReferences($publicPath);

        if ($missingReferences === []) {
            $this->info('Frontend validado: nenhum asset referenciado faltando.');
            return self::SUCCESS;
        }

        $this->error('Foram encontrados assets referenciados e ausentes:');
        foreach ($missingReferences as $entry) {
            $this->line("- {$entry['page']} -> {$entry['asset']}");
        }

        return self::FAILURE;
    }

    private function ensureCriticalAssets(string $publicPath, bool $fix): void
    {
        $critical = [
            '/img/logo.png' => null,
            '/logo.svg' => null,
            '/dist/stitch-app.min.js' => '/js/stitch-app.js',
            '/assets/images/temdetudo-logo.png' => '/img/logo.png',
            '/assets/images/temdetudo-logo.svg' => '/logo.svg',
        ];

        foreach ($critical as $target => $fallback) {
            $targetPath = $publicPath . $target;
            if ($this->fileExistsAndNotEmpty($targetPath)) {
                continue;
            }

            if (!$fix || $fallback === null) {
                continue;
            }

            $fallbackPath = $publicPath . $fallback;
            if (!$this->fileExistsAndNotEmpty($fallbackPath)) {
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                File::makeDirectory($targetDir, 0775, true);
            }

            File::copy($fallbackPath, $targetPath);
            $this->warn("Asset corrigido automaticamente: {$target}");
        }
    }

    /**
     * @return array<int, array{page: string, asset: string}>
     */
    private function findMissingAssetReferences(string $publicPath): array
    {
        $finder = new Finder();
        $finder->files()->in($publicPath)->depth('== 0')->name('*.html');

        $missing = [];
        foreach ($finder as $file) {
            $content = $file->getContents();

            preg_match_all('/\b(?:src|href)=["\']([^"\']+)["\']/i', $content, $matches);
            $refs = $matches[1] ?? [];

            foreach ($refs as $ref) {
                $normalized = $this->normalizeReference($ref);
                if ($normalized === null) {
                    continue;
                }

                $assetPath = $publicPath . $normalized;
                if ($this->fileExistsAndNotEmpty($assetPath)) {
                    continue;
                }

                $missing[] = [
                    'page' => $file->getFilename(),
                    'asset' => $normalized,
                ];
            }
        }

        return $this->uniqueMissing($missing);
    }

    private function normalizeReference(string $ref): ?string
    {
        $ref = trim($ref);
        if ($ref === '') {
            return null;
        }

        if (preg_match('/^(https?:|mailto:|tel:|javascript:|#)/i', $ref)) {
            return null;
        }

        if (!str_starts_with($ref, '/')) {
            return null;
        }

        $clean = preg_replace('/[?#].*$/', '', $ref) ?: $ref;
        if (str_starts_with($clean, '/api/')) {
            return null;
        }

        $extension = strtolower((string) pathinfo($clean, PATHINFO_EXTENSION));
        $allowed = ['css', 'js', 'png', 'jpg', 'jpeg', 'svg', 'ico', 'webp', 'avif', 'gif'];
        if (!in_array($extension, $allowed, true)) {
            return null;
        }

        return $clean;
    }

    private function fileExistsAndNotEmpty(string $path): bool
    {
        return is_file($path) && filesize($path) > 0;
    }

    /**
     * @param array<int, array{page: string, asset: string}> $rows
     * @return array<int, array{page: string, asset: string}>
     */
    private function uniqueMissing(array $rows): array
    {
        $seen = [];
        $result = [];

        foreach ($rows as $row) {
            $key = $row['page'] . '|' . $row['asset'];
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $row;
        }

        return $result;
    }
}

