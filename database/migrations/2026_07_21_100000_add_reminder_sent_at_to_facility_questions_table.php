<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 21 Temmuz 2026: cevapsiz aile sorulari suresiz "pending" kalabiliyordu,
// kuruma hicbir hatirlatma gitmiyordu (bkz. RemindUnansweredQuestions).
// Bu alan, hatirlatmanin SADECE BIR KEZ gonderilmesini saglar (her gun
// tekrar tekrar hatirlatma spam'i olmasin diye).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_questions', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('facility_questions', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
