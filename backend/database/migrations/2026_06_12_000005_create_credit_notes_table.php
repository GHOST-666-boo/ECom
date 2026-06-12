<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();

            $table->string('credit_note_number', 25)->unique()
                ->comment('Format: VRD-CN-2025-26-00001');

            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('restrict');
            $table->foreignId('order_id')->constrained('orders')->onDelete('restrict');

            $table->enum('reason', ['return', 'cancellation', 'discount'])
                ->default('cancellation');

            // Mirrors the invoice amounts (represents the reversal)
            $table->decimal('subtotal', 10, 2);
            $table->decimal('cgst', 10, 2)->default(0);
            $table->decimal('sgst', 10, 2)->default(0);
            $table->decimal('igst', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            $table->string('pdf_path')->nullable();
            $table->enum('status', ['generated', 'sent'])->default('generated');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
