<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Link to a CustomerProfile when this user is a portal account
            // for an end-customer (role=customer). Nullable for staff users.
            $table->foreignId('customer_profile_id')
                ->nullable()
                ->after('role_id')
                ->constrained('customer_profiles')
                ->nullOnDelete();
            $table->index('customer_profile_id', 'users_customer_profile_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_profile_id');
        });
    }
};
