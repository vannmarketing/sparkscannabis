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
        Schema::table('ec_discounts', function (Blueprint $table) {
            $table->json('active_days')->nullable()->after('display_at_checkout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ec_discounts', function (Blueprint $table) {
            $table->dropColumn('active_days');
        });
    }
};
