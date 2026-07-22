<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // null for super admin
            $table->foreignId('organization_id')
            ->nullable()
            ->constrained('organizations')
            ->nullOnDelete();

            // self-reference: which user created this account
            $table->foreignId('added_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->enum('role', UserRole::values())
            ->default(UserRole::GroupMember->value);

            // forces a password change on first login after being created with temporary password
            $table->boolean('must_change_password')->default(true);
            
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
