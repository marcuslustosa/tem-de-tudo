<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FrontendContractsTest extends TestCase
{
    public function test_todas_as_paginas_com_data_page_possuem_handler_no_stitch_app(): void
    {
        $publicPath = public_path();
        $jsPath = $publicPath . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'stitch-app.js';
        $this->assertFileExists($jsPath, 'Arquivo stitch-app.js nao encontrado.');

        $js = file_get_contents($jsPath);
        $this->assertNotFalse($js);

        $htmlFiles = glob($publicPath . DIRECTORY_SEPARATOR . '*.html') ?: [];
        $missingHandlers = [];

        foreach ($htmlFiles as $file) {
            $html = file_get_contents($file);
            if ($html === false) {
                continue;
            }

            if (!preg_match('/data-page=["\']([^"\']+)["\']/', $html, $matches)) {
                continue;
            }

            $page = trim($matches[1]);
            if ($page === '') {
                continue;
            }

            $quotedPage = preg_quote($page, '/');
            $hasHandler = preg_match('/(^|\\W)(["\'])?' . $quotedPage . '\\2?\\s*:/m', $js) === 1;

            if (!$hasHandler) {
                $missingHandlers[] = basename($file) . ' -> ' . $page;
            }
        }

        $this->assertSame([], $missingHandlers, 'Paginas sem handler no stitch-app.js: ' . implode(', ', $missingHandlers));
    }

    public function test_todos_os_endpoints_usados_no_frontend_existem_nas_rotas_api(): void
    {
        $jsPath = public_path('js' . DIRECTORY_SEPARATOR . 'stitch-app.js');
        $this->assertFileExists($jsPath, 'Arquivo stitch-app.js nao encontrado.');

        $js = file_get_contents($jsPath);
        $this->assertNotFalse($js);

        preg_match_all('/api\\.request\\(\\s*[\'"]([^\'"]+)[\'"]/', $js, $matches);
        $frontendEndpoints = array_values(array_unique(array_filter($matches[1] ?? [])));
        sort($frontendEndpoints);

        $apiRoutes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => '/' . ltrim(preg_replace('/^api\\/?/', '', $route->uri()), '/'))
            ->filter(fn ($uri) => $uri !== '/')
            ->values()
            ->all();

        $missingEndpoints = [];
        foreach ($frontendEndpoints as $endpoint) {
            if (!$this->routeMatchesEndpoint($endpoint, $apiRoutes)) {
                $missingEndpoints[] = $endpoint;
            }
        }

        $this->assertSame([], $missingEndpoints, 'Endpoints do frontend sem rota correspondente: ' . implode(', ', $missingEndpoints));
    }

    private function routeMatchesEndpoint(string $endpoint, array $apiRoutes): bool
    {
        foreach ($apiRoutes as $routeUri) {
            if ($routeUri === $endpoint) {
                return true;
            }

            $pattern = preg_quote($routeUri, '/');
            $pattern = preg_replace('/\\\\\\{[^\\\\]+\\\\\\}/', '[^\\/]+', $pattern);

            if (preg_match('/^' . $pattern . '$/', $endpoint) === 1) {
                return true;
            }
        }

        return false;
    }
}

