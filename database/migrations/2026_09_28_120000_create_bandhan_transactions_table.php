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
        Schema::create('bandhan_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('triggered_by_user_id')->nullable();
            $table->string('application_id')->nullable();
            $table->string('action_id')->nullable();
            $table->string('lot_number')->nullable()->index();
            $table->unsignedBigInteger('record_count')->nullable();
            $table->string('raw_payload_path')->nullable();
            $table->string('decrypted_file_path')->nullable();
            $table->longText('decrypted_data')->nullable();
            $table->string('status')->default('SUCCESS');
            $table->timestamps();
        });

        Schema::create('bandhan_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bandhan_transaction_id')->nullable()->constrained('bandhan_transactions')->nullOnDelete();
            $table->string('lot_number')->nullable()->index();
            $table->string('transaction_id')->nullable()->index();
            $table->string('beneficiary_name')->nullable();
            $table->string('column_3')->nullable();
            $table->string('account_number')->nullable();
            $table->string('beneficiary_id')->nullable()->index();
            $table->longText('raw_row')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bandhan_transaction_details');
        Schema::dropIfExists('bandhan_transactions');
    }
};
