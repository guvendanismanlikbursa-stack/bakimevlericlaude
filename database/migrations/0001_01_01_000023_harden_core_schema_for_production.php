<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('city_id')->constrained('districts')->nullOnDelete();
            $table->softDeletes()->after('updated_at');
            $table->index(['city_id', 'district_id']);
            $table->index(['source']);
        });

        Schema::table('offer_requests', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('brand')->constrained('brands')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('city_id')->constrained('districts')->nullOnDelete();
            $table->index(['brand_id', 'status']);
            $table->index(['city_id', 'district_id', 'facility_category_id']);
        });

        Schema::table('facility_claims', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('brand')->constrained('brands')->nullOnDelete();
            $table->index(['brand_id', 'status']);
        });

        Schema::table('facility_reviews', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('brand')->constrained('brands')->nullOnDelete();
            $table->index(['facility_id', 'status']);
        });

        Schema::table('visit_requests', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('brand')->constrained('brands')->nullOnDelete();
            $table->index(['facility_id', 'status']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('family_user_id')->nullable()->after('sender_id')->constrained()->nullOnDelete();
            $table->foreignId('facility_user_id')->nullable()->after('family_user_id')->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->after('facility_user_id')->constrained('admins')->nullOnDelete();
            $table->index(['sender_type', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_user_id');
            $table->dropConstrainedForeignId('facility_user_id');
            $table->dropConstrainedForeignId('admin_id');
        });
        Schema::table('visit_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('brand_id'));
        Schema::table('facility_reviews', fn (Blueprint $table) => $table->dropConstrainedForeignId('brand_id'));
        Schema::table('facility_claims', fn (Blueprint $table) => $table->dropConstrainedForeignId('brand_id'));
        Schema::table('offer_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropConstrainedForeignId('district_id');
        });
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
            $table->dropSoftDeletes();
        });
    }
};
