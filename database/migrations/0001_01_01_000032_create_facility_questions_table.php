<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Aile Sorulari": kurum profili altinda herkese acik soru-cevap.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('brand', 40);
            $table->foreignId('family_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asker_name', 120)->nullable();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('facility_users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_questions');
    }
};
