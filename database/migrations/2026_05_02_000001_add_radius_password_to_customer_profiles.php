<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_profiles', 'radius_password')) {
                $table->string('radius_password', 120)->nullable()->after('radius_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('customer_profiles', 'radius_password')) {
                $table->dropColumn('radius_password');
            }
        });
    }
};
