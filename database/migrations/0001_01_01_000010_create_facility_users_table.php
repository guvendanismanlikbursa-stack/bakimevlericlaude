<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kurum yetkilisi hesabi SADECE admin bir sahiplenme basvurusunu (facility_claims)
        // onayladiginda otomatik olusturulur; tek seferlik sifre uretilip e-postaya gonderilir.
        Schema::create('facility_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->boolean('must_change_password')->default(true);
            $table->string('status')->default('active'); // active | suspended
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_users');
    }
};
