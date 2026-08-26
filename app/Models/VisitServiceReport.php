<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitServiceReport extends Model
{
    protected $fillable = ['visit_service_request_id', 'admin_id', 'visited_at', 'note', 'photo_path'];

    protected function casts(): array
    {
        return ['visited_at' => 'date'];
    }

    public function visitServiceRequest()
    {
        return $this->belongsTo(VisitServiceRequest::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
