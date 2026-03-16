<?php

namespace Tests\Feature;

use App\Exceptions\CloudflareException;
use App\Models\Subdomain;
use App\Models\SubdomainBlacklist;
use App\Models\SubdomainDomain;
use App\Models\SubdomainLog;
use App\Models\User;
use App\Services\CloudflareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdomainTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected SubdomainDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);

        // Create a test domain
        $this->domain = SubdomainDomain::create([
            'domain' => 'example.com',
            'cloudflare_zone_id' => 'fake-zone-id-123',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Set default settings
        Subdomain::setSetting('min_length', '3');
        Subdomain::setSetting('max_length', '32');
        Subdomain::setSetting('change_cooldown_hours', '24');
        Subdomain::setSetting('cloudflare_api_token', 'fake-token');

        // Mock Cloudflare service globally
        $this->mockCloudflareService();
    }

    /**
     * Mock the CloudflareService to avoid real API calls.
     */
    protected function mockCloudflareService(): void
    {
        $mock = $this->mock(CloudflareService::class, function ($mock) {
            $mock->shouldReceive('createSubdomainRecords')
                ->andReturn([
                    'a_record_id' => 'fake-a-record-id',
                    'srv_record_id' => 'fake-srv-record-id',
                ]);

            $mock->shouldReceive('deleteSubdomainRecords')
                ->andReturn([
                    'a_record' => true,
                    'srv_record' => true,
                    'errors' => [],
                ]);

            $mock->shouldReceive('deleteRecord')->andReturn(true);

            $mock->shouldReceive('recordExists')->andReturn(null);

            $mock->shouldReceive('testConnection')
                ->andReturn([
                    'success' => true,
                    'zone_name' => 'example.com',
                    'zone_status' => 'active',
                    'name_servers' => [],
                ]);
        });
    }

    // =========================================================================
    // Subdomain Creation Tests
    // =========================================================================

    public function test_user_can_create_subdomain(): void
    {
        // Create a fake server owned by the user
        $server = $this->createFakeServer($this->user->id);

        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'myserver',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pteroca_subdomains', [
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'subdomain' => 'myserver',
            'domain_id' => $this->domain->id,
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_create_duplicate_subdomain(): void
    {
        $server1 = $this->createFakeServer($this->user->id);
        $server2 = $this->createFakeServer($this->user->id);

        // Create first subdomain
        Subdomain::create([
            'server_id' => $server1->id,
            'user_id' => $this->user->id,
            'subdomain' => 'myserver',
            'domain_id' => $this->domain->id,
            'status' => 'active',
        ]);

        // Try to create with same name
        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server2->id), [
                'subdomain' => 'myserver',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_server_can_only_have_one_subdomain(): void
    {
        $server = $this->createFakeServer($this->user->id);

        Subdomain::create([
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'subdomain' => 'existing',
            'domain_id' => $this->domain->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'newname',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertSessionHas('error');
    }

    // =========================================================================
    // Validation Tests
    // =========================================================================

    public function test_blacklisted_subdomain_rejected(): void
    {
        SubdomainBlacklist::create(['word' => 'admin']);

        $server = $this->createFakeServer($this->user->id);

        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'admin',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_subdomain_validation_rules(): void
    {
        $server = $this->createFakeServer($this->user->id);

        // Too short
        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'ab',
                'domain_id' => $this->domain->id,
            ]);
        $response->assertSessionHasErrors('subdomain');

        // Starts with hyphen
        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => '-invalid',
                'domain_id' => $this->domain->id,
            ]);
        $response->assertSessionHasErrors('subdomain');

        // Ends with hyphen
        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'invalid-',
                'domain_id' => $this->domain->id,
            ]);
        $response->assertSessionHasErrors('subdomain');

        // Consecutive hyphens
        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'in--valid',
                'domain_id' => $this->domain->id,
            ]);
        $response->assertSessionHasErrors('subdomain');

        // Invalid characters
        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'inv@lid!',
                'domain_id' => $this->domain->id,
            ]);
        $response->assertSessionHasErrors('subdomain');
    }

    public function test_valid_subdomain_formats_accepted(): void
    {
        $server = $this->createFakeServer($this->user->id);

        $validNames = ['myserver', 'my-server', 'server123', 'a1b', 'test-server-1'];

        foreach ($validNames as $name) {
            // Clean up previous subdomain for this server
            Subdomain::where('server_id', $server->id)->delete();

            $response = $this->actingAs($this->user)
                ->post(route('client.subdomain.store', $server->id), [
                    'subdomain' => $name,
                    'domain_id' => $this->domain->id,
                ]);

            $response->assertSessionHas('success', "Subdomain '{$name}' should be valid");
        }
    }

    // =========================================================================
    // Cooldown Tests
    // =========================================================================

    public function test_cooldown_enforced(): void
    {
        $server = $this->createFakeServer($this->user->id);

        // Create subdomain with recent last_changed_at
        Subdomain::create([
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'subdomain' => 'oldname',
            'domain_id' => $this->domain->id,
            'status' => 'active',
            'last_changed_at' => now(), // Just changed
        ]);

        // Try to update immediately (should fail due to 24h cooldown)
        $response = $this->actingAs($this->user)
            ->put(route('client.subdomain.update', $server->id), [
                'subdomain' => 'newname',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertSessionHas('error');
    }

    public function test_cooldown_expired_allows_change(): void
    {
        $server = $this->createFakeServer($this->user->id);

        Subdomain::create([
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'subdomain' => 'oldname',
            'domain_id' => $this->domain->id,
            'status' => 'active',
            'last_changed_at' => now()->subHours(25), // 25h ago, cooldown is 24h
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('client.subdomain.update', $server->id), [
                'subdomain' => 'newname',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertSessionHas('success');
    }

    // =========================================================================
    // Permission Tests
    // =========================================================================

    public function test_user_can_only_manage_own_subdomains(): void
    {
        $otherUser = User::factory()->create();
        $server = $this->createFakeServer($otherUser->id); // Server belongs to OTHER user

        // Try to create subdomain on someone else's server
        $response = $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'stolen',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_others_subdomain(): void
    {
        $otherUser = User::factory()->create();
        $server = $this->createFakeServer($otherUser->id);

        Subdomain::create([
            'server_id' => $server->id,
            'user_id' => $otherUser->id,
            'subdomain' => 'theirserver',
            'domain_id' => $this->domain->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('client.subdomain.destroy', $server->id));

        $response->assertForbidden();
    }

    // =========================================================================
    // Admin Tests
    // =========================================================================

    public function test_admin_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.subdomains.index'));

        $response->assertOk();
        $response->assertViewHas('stats');
        $response->assertViewHas('recentSubdomains');
    }

    public function test_admin_can_manage_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.subdomains.settings.update'), [
                'min_length' => '4',
                'max_length' => '20',
                'change_cooldown_hours' => '48',
                'auto_delete_on_terminate' => 'true',
                'auto_suspend_on_suspend' => 'false',
                'default_ttl' => '300',
            ]);

        $response->assertRedirect();

        $this->assertEquals('4', Subdomain::getSetting('min_length'));
        $this->assertEquals('20', Subdomain::getSetting('max_length'));
        $this->assertEquals('48', Subdomain::getSetting('change_cooldown_hours'));
        $this->assertEquals('false', Subdomain::getSetting('auto_suspend_on_suspend'));
    }

    public function test_admin_can_manage_blacklist(): void
    {
        // Add
        $response = $this->actingAs($this->admin)
            ->post(route('admin.subdomains.blacklist.add'), [
                'word' => 'badword',
                'reason' => 'Test reason',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pteroca_subdomain_blacklist', ['word' => 'badword']);

        // Remove
        $item = SubdomainBlacklist::where('word', 'badword')->first();
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.subdomains.blacklist.remove', $item->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pteroca_subdomain_blacklist', ['word' => 'badword']);
    }

    public function test_admin_can_load_default_blacklist(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.subdomains.blacklist.default'));

        $response->assertRedirect();

        $count = SubdomainBlacklist::count();
        $this->assertGreaterThan(0, $count);
        $this->assertDatabaseHas('pteroca_subdomain_blacklist', ['word' => 'admin']);
    }

    // =========================================================================
    // Availability Check Tests
    // =========================================================================

    public function test_check_availability_returns_available(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('api.subdomain.check'), [
                'subdomain' => 'available-name',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertOk();
        $response->assertJson(['available' => true]);
    }

    public function test_check_availability_returns_taken(): void
    {
        Subdomain::create([
            'server_id' => 999,
            'user_id' => $this->user->id,
            'subdomain' => 'taken',
            'domain_id' => $this->domain->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.subdomain.check'), [
                'subdomain' => 'taken',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertOk();
        $response->assertJson(['available' => false]);
    }

    public function test_check_availability_rejects_blacklisted(): void
    {
        SubdomainBlacklist::create(['word' => 'blocked']);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.subdomain.check'), [
                'subdomain' => 'blocked',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertOk();
        $response->assertJson(['available' => false]);
    }

    public function test_check_availability_rejects_invalid_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('api.subdomain.check'), [
                'subdomain' => '-invalid',
                'domain_id' => $this->domain->id,
            ]);

        $response->assertOk();
        $response->assertJson(['available' => false]);
    }

    // =========================================================================
    // Rate Limiting Tests
    // =========================================================================

    public function test_rate_limiting_works(): void
    {
        // Send 6 requests (limit is 5)
        for ($i = 1; $i <= 6; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson(route('api.subdomain.check'), [
                    'subdomain' => "test{$i}",
                    'domain_id' => $this->domain->id,
                ]);

            if ($i <= 5) {
                $response->assertOk();
            } else {
                $response->assertStatus(429);
            }
        }
    }

    // =========================================================================
    // Activity Log Tests
    // =========================================================================

    public function test_subdomain_creation_is_logged(): void
    {
        $server = $this->createFakeServer($this->user->id);

        $this->actingAs($this->user)
            ->post(route('client.subdomain.store', $server->id), [
                'subdomain' => 'logged',
                'domain_id' => $this->domain->id,
            ]);

        $this->assertDatabaseHas('pteroca_subdomain_logs', [
            'user_id' => $this->user->id,
            'action' => 'create',
        ]);
    }

    public function test_subdomain_deletion_is_logged(): void
    {
        $server = $this->createFakeServer($this->user->id);

        $subdomain = Subdomain::create([
            'server_id' => $server->id,
            'user_id' => $this->user->id,
            'subdomain' => 'todelete',
            'domain_id' => $this->domain->id,
            'status' => 'active',
            'cloudflare_a_record_id' => 'fake-a',
            'cloudflare_srv_record_id' => 'fake-srv',
        ]);

        $this->actingAs($this->user)
            ->delete(route('client.subdomain.destroy', $server->id));

        $this->assertDatabaseHas('pteroca_subdomain_logs', [
            'user_id' => $this->user->id,
            'action' => 'delete',
        ]);
    }

    // =========================================================================
    // Model Tests
    // =========================================================================

    public function test_subdomain_full_address_accessor(): void
    {
        $subdomain = Subdomain::create([
            'server_id' => 1,
            'user_id' => $this->user->id,
            'subdomain' => 'test',
            'domain_id' => $this->domain->id,
            'status' => 'active',
        ]);

        $this->assertEquals('test.example.com', $subdomain->full_address);
    }

    public function test_subdomain_cooldown_check(): void
    {
        $subdomain = new Subdomain(['last_changed_at' => now()]);
        $this->assertTrue($subdomain->isOnCooldown(24));
        $this->assertFalse($subdomain->isOnCooldown(0));

        $subdomain->last_changed_at = now()->subHours(25);
        $this->assertFalse($subdomain->isOnCooldown(24));
    }

    public function test_blacklist_checks_substring(): void
    {
        SubdomainBlacklist::create(['word' => 'admin']);

        $this->assertTrue(SubdomainBlacklist::isBlacklisted('admin'));
        $this->assertTrue(SubdomainBlacklist::isBlacklisted('myadmin'));
        $this->assertTrue(SubdomainBlacklist::isBlacklisted('admin123'));
        $this->assertFalse(SubdomainBlacklist::isBlacklisted('myserver'));
    }

    public function test_settings_encrypt_decrypt(): void
    {
        Subdomain::setSetting('cloudflare_api_token', 'my-secret-token');

        $this->assertEquals('my-secret-token', Subdomain::getSetting('cloudflare_api_token'));

        // Verify it's stored encrypted (raw value should differ)
        $raw = \Illuminate\Support\Facades\DB::table('pteroca_subdomain_settings')
            ->where('setting_key', 'cloudflare_api_token')
            ->value('setting_value');

        $this->assertNotEquals('my-secret-token', $raw);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a fake server object for testing.
     *
     * In a real PteroCA environment, this would use the Server model.
     * For tests, we create a minimal mock.
     */
    protected function createFakeServer(int $userId): object
    {
        // If PteroCA Server model exists, use it
        if (class_exists(\App\Models\Server::class)) {
            return \App\Models\Server::factory()->create(['user_id' => $userId]);
        }

        // Otherwise, create a simple mock
        return (object) [
            'id' => rand(1, 99999),
            'user_id' => $userId,
            'ip' => '144.126.138.69',
            'port' => 25565,
            'allocation' => (object) [
                'ip' => '144.126.138.69',
                'port' => 25565,
            ],
        ];
    }
}
