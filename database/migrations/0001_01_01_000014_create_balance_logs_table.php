<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bakiye/hak hareketlerinin denetim izi (audit log)
        Schema::create('balance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // topup_approved | admin_adjust_balance | admin_adjust_credits | quote_charge | claim_bonus_credits
            $table->decimal('amount', 10, 2)->default(0); // bakiye degisimi (+ / -)
            $table->integer('credits_amount')->default(0); // hak degisimi (+ / -)
            $table->decimal('balance_after', 10, 2);
            $table->integer('credits_after');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_logs');
    }
};
