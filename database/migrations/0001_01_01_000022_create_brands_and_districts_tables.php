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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('theme')->nullable();
            $table->string('default_section')->nullable();
            $table->json('category_scope')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->unique(['city_id', 'slug']);
            $table->index(['slug']);
        });

        foreach (config('brands.brands', []) as $slug => $brand) {
            DB::table('brands')->insert([
                'slug' => $slug,
                'name' => $brand['name'] ?? $slug,
                'theme' => $brand['theme'] ?? null,
                'default_section' => $brand['default_section'] ?? null,
                'category_scope' => json_encode($brand['category_scope'] ?? []),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (config('turkiye.provinces', []) as $cityName => $districts) {
            $city = DB::table('cities')->where('name', $cityName)->first();
            if (! $city) {
                $cityId = DB::table('cities')->insertGetId([
                    'name' => $cityName,
                    'slug' => Str::slug($cityName),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $cityId = $city->id;
            }

            foreach ($districts as $districtName) {
                DB::table('districts')->updateOrInsert(
                    ['city_id' => $cityId, 'slug' => Str::slug($districtName)],
                    ['name' => $districtName, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
        Schema::dropIfExists('brands');
    }
};
