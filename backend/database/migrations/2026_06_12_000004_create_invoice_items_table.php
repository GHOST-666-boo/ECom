<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');

            // Snapshot data (never recalculate from live product — invoice is legal document)
            $table->string('product_name');
            $table->string('hsn_code')->comment('Resolved at invoice time: product.hsn_code ?? category.hsn_code');
            $table->decimal('gst_rate', 5, 2)->comment('Resolved at invoice time: product.gst_rate ?? category.gst_rate');

            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('taxable_value', 10, 2)->comment('quantity × unit_price');

            // Per-line GST amounts
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);

            $table->decimal('line_total', 10, 2)->comment('taxable_value + cgst + sgst + igst');

            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
