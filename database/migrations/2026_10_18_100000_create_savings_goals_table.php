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
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('target_amount', 19, 2);
            $table->date('target_date')->nullable();
            $table->string('status', 20)->default('active'); // active, paused, completed, cancelled
            $table->string('color', 30)->default('#2563EB');
            $table->string('icon', 50)->default('target');
            $table->foreignId('target_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('savings_goal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_goal_id')->constrained('savings_goals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('direction', 20); // contribution, withdrawal
            $table->decimal('amount', 19, 2);
            $table->date('entry_date');
            $table->string('notes', 255)->nullable();
            $table->foreignId('linked_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['savings_goal_id', 'entry_date']);
            $table->index(['user_id', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_goal_entries');
        Schema::dropIfExists('savings_goals');
    }
};
