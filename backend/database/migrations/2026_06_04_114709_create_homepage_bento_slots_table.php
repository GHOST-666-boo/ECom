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
        Schema::create('homepage_bento_slots', function (Blueprint $table) {
            $table->id();
            $table->string('slot_key')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('badge')->nullable();
            $table->string('theme')->default('light');
            $table->string('link_type')->default('none'); // 'none', 'category', 'product', 'custom'
            
            // Foreign Keys and constraints
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('custom_url')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_bento_slots');
    }
};
