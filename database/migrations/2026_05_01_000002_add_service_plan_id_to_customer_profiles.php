<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->foreignId('service_plan_id')->nullable()->after('package')->constrained('service_plans')->nullOnDelete();
            $table->boolean('billing_enabled')->default(true)->after('service_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_plan_id');
            $table->dropColumn('billing_enabled');
        });
    }
};
