<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataImportBatch extends Model
{
    protected $fillable = ['source', 'admin_id', 'city_id', 'district_id', 'facility_category_id', 'file_name', 'total_rows', 'created_count', 'skipped_count', 'error_count', 'status', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function category()
    {
        return $this->belongsTo(FacilityCategory::class, 'facility_category_id');
    }

    public function rows()
    {
        return $this->hasMany(DataImportRow::class);
    }
}
