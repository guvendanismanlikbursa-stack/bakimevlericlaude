<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('name');
            $table->foreignId('facility_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('district')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->decimal('price_min', 10, 2)->nullable();
            $table->decimal('price_max', 10, 2)->nullable();

            $table->string('applicant_name');
            $table->string('applicant_email');
            $table->string('applicant_phone');

            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['brand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_registrations');
    }
};
