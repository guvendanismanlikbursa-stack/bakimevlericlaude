<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_requests', function (Blueprint $table) {
            if (! $this->indexExists('offer_requests', 'offer_requests_brand_status_index')) {
                $table->index(['brand', 'status']);
            }
            if (! $this->indexExists('offer_requests', 'offer_requests_brand_family_user_id_index')) {
                $table->index(['brand', 'family_user_id']);
            }
            if (! $this->indexExists('offer_requests', 'offer_requests_brand_city_id_facility_category_id_index')) {
                $table->index(['brand', 'city_id', 'facility_category_id']);
            }
        });

        Schema::table('quotes', function (Blueprint $table) {
            if (! $this->indexExists('quotes', 'quotes_offer_request_id_facility_id_unique')) {
                $table->unique(['offer_request_id', 'facility_id']);
            }
            if (! $this->indexExists('quotes', 'quotes_facility_id_status_index')) {
                $table->index(['facility_id', 'status']);
            }
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('quotes', 'quotes_facility_id_status_index');
        $this->dropIndexIfExists('quotes', 'quotes_offer_request_id_facility_id_unique');
        $this->dropIndexIfExists('offer_requests', 'offer_requests_brand_city_id_facility_category_id_index');
        $this->dropIndexIfExists('offer_requests', 'offer_requests_brand_family_user_id_index');
        $this->dropIndexIfExists('offer_requests', 'offer_requests_brand_status_index');
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))->contains('name', $index);
        }

        if ($driver === 'mysql') {
            return ! empty(DB::select('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [$index]));
        }

        return false;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE '.$table.' DROP INDEX '.$index);
            return;
        }

        DB::statement('DROP INDEX '.$index);
    }
};