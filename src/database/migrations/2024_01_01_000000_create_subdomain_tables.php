<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Domains configuration (must be created before subdomains due to FK)
        Schema::create('pteroca_subdomain_domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('domain', 255)->unique();
            $table->string('cloudflare_zone_id', 64);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        // Main subdomains table
        Schema::create('pteroca_subdomains', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('user_id');
            $table->string('subdomain', 63);
            $table->unsignedInteger('domain_id');
            $table->string('cloudflare_a_record_id', 64)->nullable();
            $table->string('cloudflare_srv_record_id', 64)->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'error'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->timestamp('last_changed_at')->nullable();

            $table->unique(['subdomain', 'domain_id'], 'unique_subdomain_domain');
            $table->index('server_id', 'idx_server');
            $table->index('user_id', 'idx_user');
            $table->index('status', 'idx_status');

            $table->foreign('domain_id')
                ->references('id')
                ->on('pteroca_subdomain_domains')
                ->onDelete('restrict');
        });

        // Blacklist
        Schema::create('pteroca_subdomain_blacklist', function (Blueprint $table) {
            $table->increments('id');
            $table->string('word', 63)->unique();
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Activity logs
        Schema::create('pteroca_subdomain_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('subdomain_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->enum('action', ['create', 'update', 'delete', 'suspend', 'unsuspend', 'error']);
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action', 'idx_action');
            $table->index('created_at', 'idx_created');
        });

        // Settings (key-value store)
        Schema::create('pteroca_subdomain_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('setting_key', 64)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Seed default settings
        DB::table('pteroca_subdomain_settings')->insert([
            ['setting_key' => 'cloudflare_api_token', 'setting_value' => null],
            ['setting_key' => 'min_length', 'setting_value' => '3'],
            ['setting_key' => 'max_length', 'setting_value' => '32'],
            ['setting_key' => 'change_cooldown_hours', 'setting_value' => '24'],
            ['setting_key' => 'auto_delete_on_terminate', 'setting_value' => 'true'],
            ['setting_key' => 'auto_suspend_on_suspend', 'setting_value' => 'true'],
            ['setting_key' => 'default_ttl', 'setting_value' => '1'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pteroca_subdomain_settings');
        Schema::dropIfExists('pteroca_subdomain_logs');
        Schema::dropIfExists('pteroca_subdomain_blacklist');
        Schema::dropIfExists('pteroca_subdomains');
        Schema::dropIfExists('pteroca_subdomain_domains');
    }
};
