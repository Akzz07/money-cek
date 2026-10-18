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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('type', 20); // cash, bank, e_wallet, investment, savings, other
            $table->string('account_number', 50)->nullable();
            $table->string('institution_name', 100)->nullable();
            $table->decimal('opening_balance', 19, 2)->default(0.00);
            $table->date('opening_date');
            $table->boolean('include_in_total_assets')->default(true);
            $table->boolean('include_in_safe_to_spend')->default(true);
            $table->string('status', 20)->default('active'); // active, archived
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
