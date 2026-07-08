<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_service_options', function (Blueprint $table) {
            $table->id();
            $table->string('section_slug', 80)->index();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->unique(['section_slug', 'slug']);
        });

        Schema::create('facility_service_option_facility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            // Varsayilan {tablo}_{kolon}_foreign adi 64 karakter MySQL sinirini
            // asiyordu ("Identifier name ... is too long"); kisa isim veriyoruz.
            $table->foreignId('facility_service_option_id')->constrained(indexName: 'fac_svc_opt_facility_option_fk')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['facility_id', 'facility_service_option_id'], 'facility_service_facility_unique');
        });

        Schema::create('data_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('google_maps_veri_cekici')->index();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('facility_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->string('status')->default('completed')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('data_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('status')->index();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('family_saved_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('brand')->nullable()->index();
            $table->string('list_type')->default('favorite')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['family_user_id', 'facility_id', 'list_type'], 'family_saved_facility_unique');
        });

        foreach (['elderly', 'child', 'rehab'] as $table) {
            Schema::create($table.'_facility_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('facility_id')->unique()->constrained()->cascadeOnDelete();
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }

        foreach (config('brands.service_sections', []) as $sectionSlug => $section) {
            foreach ($section['features'] ?? [] as $feature) {
                DB::table('facility_service_options')->insert([
                    'section_slug' => $sectionSlug,
                    'name' => $feature,
                    'slug' => Str::slug($feature),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rehab_facility_details');
        Schema::dropIfExists('child_facility_details');
        Schema::dropIfExists('elderly_facility_details');
        Schema::dropIfExists('family_saved_facilities');
        Schema::dropIfExists('data_import_rows');
        Schema::dropIfExists('data_import_batches');
        Schema::dropIfExists('facility_service_option_facility');
        Schema::dropIfExists('facility_service_options');
    }
};
