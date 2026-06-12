<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('hsn_code', 8)->nullable()->after('is_active')
                ->comment('Default HSN code for products in this category');
            $table->decimal('gst_rate', 5, 2)->nullable()->after('hsn_code')
                ->comment('Default GST rate (%) for products in this category');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['hsn_code', 'gst_rate']);
        });
    }
};
