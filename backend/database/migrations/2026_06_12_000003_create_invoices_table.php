<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Invoice identification
            $table->string('invoice_number', 25)->unique()
                ->comment('Format: VRD-INV-2025-26-00001 (financial year based)');

            // Linked order — UNIQUE enforces one invoice per order (idempotency)
            $table->foreignId('order_id')
                ->unique()
                ->constrained('orders')
                ->onDelete('restrict');

            // Buyer details (snapshot at invoice time)
            $table->string('buyer_name');
            $table->text('buyer_address');
            $table->string('buyer_state');
            $table->string('buyer_gstin', 15)->nullable()
                ->comment('Filled for B2B invoices; null for B2C');
            $table->enum('invoice_type', ['B2C', 'B2B'])->default('B2C');

            // Seller details (snapshot from config at invoice time)
            $table->string('seller_gstin');
            $table->string('seller_name');
            $table->text('seller_address');
            $table->string('seller_state');

            // Dates
            $table->date('invoice_date');

            // Product line item totals
            $table->decimal('subtotal', 10, 2)
                ->comment('Sum of all taxable values (qty × unit_price)');

            // Shipping (SAC 996812)
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('shipping_gst_rate', 5, 2)->default(0);
            $table->decimal('shipping_cgst', 10, 2)->default(0);
            $table->decimal('shipping_sgst', 10, 2)->default(0);
            $table->decimal('shipping_igst', 10, 2)->default(0);

            // GST totals (CGST+SGST for intra-state, IGST for inter-state)
            $table->decimal('cgst', 10, 2)->default(0)
                ->comment('Filled when buyer_state == seller_state');
            $table->decimal('sgst', 10, 2)->default(0)
                ->comment('Filled when buyer_state == seller_state');
            $table->decimal('igst', 10, 2)->default(0)
                ->comment('Filled when buyer_state != seller_state');

            $table->decimal('total_amount', 10, 2)
                ->comment('subtotal + shipping + all taxes');

            // PDF
            $table->string('pdf_path')->nullable()
                ->comment('Path on Cloudflare R2 storage');

            // Status
            $table->enum('status', ['generated', 'sent', 'cancelled'])->default('generated');

            $table->timestamps();

            // Indexes
            $table->index('invoice_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
