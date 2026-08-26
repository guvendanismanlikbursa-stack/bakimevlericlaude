<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;

// 26 Agustos 2026: kullanicinin talebi - "Burada çalışmak istiyorum"
// basvurularinin admin tarafindan goruldugu ve kurumun kayitli WhatsApp'ina
// MANUEL olarak iletildigi ekran. bkz. Public\JobApplicationController,
// BrokerController (sekme/durum filtreleme deseni oradan alindi).
class JobApplicationController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $status = $request->query('status', 'yeni');
        $query = JobApplication::with('facility')->latest();

        if (in_array($status, ['yeni', 'iletildi'], true)) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(30)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $applications)) {
            return $redirect;
        }

        $newCount = JobApplication::where('status', 'yeni')->count();

        return view('admin.job-applications.index', compact('applications', 'status', 'newCount'));
    }

    public function markForwarded(JobApplication $jobApplication)
    {
        $jobApplication->update([
            'status' => 'iletildi',
            'forwarded_at' => now(),
            'forwarded_by' => session('admin_id'),
        ]);

        log_admin_event('job_application_forwarded', $jobApplication);

        return back()->with('success', 'Başvuru iletildi olarak işaretlendi.');
    }

    public function destroy(JobApplication $jobApplication)
    {
        $jobApplication->delete();

        return back()->with('success', 'Başvuru silindi.');
    }
}
