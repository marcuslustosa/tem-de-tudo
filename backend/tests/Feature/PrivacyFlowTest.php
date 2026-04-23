<?php

namespace Tests\Feature;

use App\Models\DataPrivacyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrivacyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_export_and_download_file(): void
    {
        config(['privacy.export_disk' => 'local']);
        Storage::fake('local');

        $user = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        Sanctum::actingAs($user);

        $requestResponse = $this->postJson('/api/privacy/export');
        $requestResponse
            ->assertStatus(202)
            ->assertJsonPath('success', true);

        $requestId = (int) $requestResponse->json('data.request_id');
        $this->assertGreaterThan(0, $requestId);

        $downloadResponse = $this->get("/api/privacy/export/{$requestId}/download");
        $downloadResponse->assertOk();
        $this->assertStringContainsString(
            'attachment;',
            (string) $downloadResponse->headers->get('content-disposition')
        );

        $privacyRequest = DataPrivacyRequest::findOrFail($requestId);
        $this->assertSame(DataPrivacyRequest::STATUS_COMPLETED, $privacyRequest->status);
        $this->assertNotNull($privacyRequest->file_path);
        Storage::disk('local')->assertExists($privacyRequest->file_path);
    }

    public function test_user_can_update_privacy_consent_flags(): void
    {
        $user = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/privacy/consent', [
            'marketing_consent' => true,
            'privacy_policy_accepted' => true,
            'data_processing_consent' => true,
            'consent_version' => 'v2',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.marketing_consent', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'marketing_consent' => true,
            'consent_version' => 'v2',
        ]);

        $this->assertDatabaseHas('data_privacy_requests', [
            'user_id' => $user->id,
            'request_type' => DataPrivacyRequest::TYPE_CONSENT_UPDATE,
            'status' => DataPrivacyRequest::STATUS_COMPLETED,
        ]);
    }

    public function test_delete_account_requires_password_and_anonymizes_user(): void
    {
        $user = User::factory()->create([
            'perfil' => 'cliente',
            'status' => 'ativo',
            'password' => Hash::make('Secret123!'),
            'marketing_consent' => true,
        ]);

        $user->createToken('test-token');
        Sanctum::actingAs($user);

        $forbidden = $this->postJson('/api/privacy/delete-account', [
            'password' => 'wrong-password',
        ]);
        $forbidden->assertStatus(403);

        $response = $this->postJson('/api/privacy/delete-account', [
            'password' => 'Secret123!',
            'reason' => 'Solicitacao de exclusao',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertSame('deleted', $user->status);
        $this->assertFalse((bool) $user->is_active);
        $this->assertFalse((bool) $user->marketing_consent);
        $this->assertStringStartsWith('deleted_', $user->email);
        $this->assertSame(0, $user->tokens()->count());

        $this->assertDatabaseHas('data_privacy_requests', [
            'user_id' => $user->id,
            'request_type' => DataPrivacyRequest::TYPE_DELETE_ACCOUNT,
            'status' => DataPrivacyRequest::STATUS_COMPLETED,
        ]);
    }
}

