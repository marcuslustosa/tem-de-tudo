<?php

namespace Tests\Feature;

use Tests\TestCase;

class ManifestContractsTest extends TestCase
{
    public function test_manifest_points_only_to_existing_public_pages(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);

        $this->assertPathExistsInPublic($manifest['start_url'] ?? null);

        foreach (($manifest['shortcuts'] ?? []) as $shortcut) {
            $this->assertPathExistsInPublic($shortcut['url'] ?? null);
        }
    }

    private function assertPathExistsInPublic(?string $path): void
    {
        $this->assertIsString($path);
        $this->assertNotSame('', trim($path));

        $relative = ltrim((string) $path, '/');
        $absolute = public_path(str_replace('/', DIRECTORY_SEPARATOR, $relative));

        $this->assertFileExists($absolute, "Manifest referencia uma pagina inexistente: {$path}");
    }
}
