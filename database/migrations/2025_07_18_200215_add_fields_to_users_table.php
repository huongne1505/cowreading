<?php

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->text('bio')->nullable()->after('avatar');
            $table->date('birth_date')->nullable()->after('bio');
            $table->string('phone')->nullable()->after('birth_date');
            $table->string('role')->default('user')->after('phone'); // Add role field
            $table->boolean('is_premium')->default(false)->after('role');
            $table->timestamp('premium_expires_at')->nullable()->after('is_premium');
            $table->timestamp('last_login_at')->nullable()->after('premium_expires_at');
            $table->boolean('is_active')->default(true)->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'bio',
                'birth_date',
                'phone',
                'role',
                'is_premium',
                'premium_expires_at',
                'last_login_at',
                'is_active'
            ]);
        });
    }
};
