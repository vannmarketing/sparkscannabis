<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mix_and_match_products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('container_product_id')->index();
            $table->unsignedBigInteger('child_product_id')->index();
            $table->integer('min_qty')->default(0);
            $table->integer('max_qty')->default(1);
            $table->timestamps();
        });

        Schema::create('mix_and_match_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique()->index();
            $table->integer('min_container_size')->default(1);
            $table->integer('max_container_size')->nullable();
            $table->string('pricing_type', 20)->default('per_item'); // 'per_item' or 'fixed_price'
            $table->decimal('fixed_price', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mix_and_match_products');
        Schema::dropIfExists('mix_and_match_settings');
    }
};
