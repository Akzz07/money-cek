<?php

declare(strict_types=1);

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
            $table->string('timezone', 50)->default('Asia/Jakarta')->after('remember_token');
            $table->string('locale', 10)->default('id')->after('timezone');
            $table->string('currency', 3)->default('IDR')->after('locale');
            $table->unsignedTinyInteger('payday_day')->nullable()->after('currency');
            $table->date('next_payday_date')->nullable()->after('payday_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'locale',
                'currency',
                'payday_day',
                'next_payday_date',
            ]);
        });
    }
};
