<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataImportRow extends Model
{
    protected $fillable = ['data_import_batch_id', 'facility_id', 'row_number', 'status', 'name', 'phone', 'message', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function batch()
    {
        return $this->belongsTo(DataImportBatch::class, 'data_import_batch_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
