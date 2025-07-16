<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fg_gift_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 60)->default('published');
            $table->string('gift_type')->default('manual'); // manual, automatic, buy_x_get_y, coupon_based
            $table->string('criteria_type')->default('cart_subtotal'); // cart_subtotal, cart_total, category_total, cart_quantity
            $table->decimal('criteria_value', 15, 2)->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('active_days')->nullable(); // ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
            $table->integer('max_gifts_per_order')->nullable();
            $table->integer('max_gifts_per_customer')->nullable();
            $table->integer('max_gifts_total')->nullable();
            $table->boolean('require_customer_login')->default(false);
            $table->boolean('allow_coupon')->default(true);
            $table->boolean('require_min_orders')->default(false);
            $table->integer('min_orders_count')->nullable();
            $table->string('product_filter_type')->nullable(); // all, specific_products, specific_categories
            $table->json('product_ids')->nullable();
            $table->json('category_ids')->nullable();
            $table->string('customer_filter_type')->nullable(); // all, specific_customers
            $table->json('customer_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('fg_gift_rule_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_rule_id')->constrained('fg_gift_rules')->onDelete('cascade');
            $table->foreignId('product_id');
            $table->integer('quantity')->default(1);
            $table->boolean('is_same_product')->default(false); // For Buy X Get X scenarios
            $table->timestamps();
        });

        Schema::create('fg_gift_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_rule_id')->nullable()->constrained('fg_gift_rules')->onDelete('set null');
            $table->foreignId('order_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('product_id');
            $table->integer('quantity')->default(1);
            $table->string('gift_type')->default('rule'); // rule, manual
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });

        Schema::create('fg_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fg_gift_logs');
        Schema::dropIfExists('fg_gift_rule_products');
        Schema::dropIfExists('fg_gift_rules');
        Schema::dropIfExists('fg_settings');
    }
};
