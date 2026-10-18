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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('limit_amount', 19, 2);
            $table->unsignedTinyInteger('warning_threshold')->default(80);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'period_start']);
            $table->index(['user_id', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
