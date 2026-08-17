<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledJobRun;

// 17 Agustos 2026: kullanicinin talebi - bkz. ScheduledJobMonitor ayni
// tarihli yorum. Bu ekran hicbir aksiyon almaz (silme/duzenleme yok) -
// sadece routes/console.php'deki her zamanlanmis gorevin son basarili/
// basarisiz calisma zamanini ve "beklenen sikliktan gecikmis mi" bilgisini
// gosterir.
class ScheduledJobController extends Controller
{
    public function index()
    {
        $jobs = ScheduledJobRun::orderBy('job_name')->get();

        return view('admin.scheduled-jobs.index', compact('jobs'));
    }
}
