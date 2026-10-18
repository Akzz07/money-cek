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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('source_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 19, 2);
            $table->date('transaction_date');
            $table->string('description', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('posted');
            $table->string('adjustment_direction', 20)->nullable();
            $table->string('client_request_id', 36)->nullable()->unique();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['source_account_id', 'transaction_date']);
            $table->index(['category_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
