<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('hsn_code', 8)->nullable()->after('is_active')
                ->comment('Product-level HSN code. Overrides category HSN if set.');
            $table->decimal('gst_rate', 5, 2)->nullable()->after('hsn_code')
                ->comment('Product-level GST rate. Overrides category GST rate if set.');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['hsn_code', 'gst_rate']);
        });
    }
};
