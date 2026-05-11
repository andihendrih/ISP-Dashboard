<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voucher_batches', function (Blueprint $t) {
            $t->id();
            $t->string('label')->nullable();        // "Cetakan 28-Apr 2026 - Toko"
            $t->string('profile');                  // RADIUS groupname (Hotspot6Jam, etc)
            $t->unsignedInteger('count');           // jumlah voucher di batch
            $t->string('prefix', 16)->nullable();
            $t->unsignedSmallInteger('code_length')->default(6);
            $t->date('expires_at')->nullable();     // kalau Mode B (set Expiration saat generate)
            $t->json('codes');                      // array of usernames yang di-generate
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index('profile');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('voucher_batches');
    }
};
