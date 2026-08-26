<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\VisitServiceReport;
use App\Models\VisitServiceRequest;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VisitServiceController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');

        $query = VisitServiceRequest::with(['facility', 'familyUser'])->latest();
        if ($status !== '') {
            $query->where('status', $status);
        }

        $visitServiceRequests = $query->paginate(30)->withQueryString();
        if ($redirect = $this->redirectIfPageOutOfRange($request, $visitServiceRequests)) {
            return $redirect;
        }

        $newCount = VisitServiceRequest::where('status', 'yeni')->count();

        return view('admin.visit-service.index', compact('visitServiceRequests', 'newCount', 'status'));
    }

    public function show(VisitServiceRequest $visitServiceRequest)
    {
        $visitServiceRequest->load(['facility', 'familyUser', 'reports.admin']);

        return view('admin.visit-service.show', compact('visitServiceRequest'));
    }

    public function updateStatus(Request $request, VisitServiceRequest $visitServiceRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:yeni,iletisime_gecildi,aktif,pasif',
        ]);

        $visitServiceRequest->update([
            'status' => $data['status'],
            'admin_reviewed_at' => $visitServiceRequest->admin_reviewed_at ?? now(),
        ]);

        return back()->with('success', 'Talep durumu güncellendi.');
    }

    // 26 Agustos 2026: kullanicinin talebi - her fiziksel ziyaretten sonra
    // admin buradan tek bir rapor kaydi dusurur; aile bunu kendi panelinde
    // her zaman gorur, admin ayrica WhatsApp'tan da (wa.me linki, elle) iletebilir -
    // bu platformda otomatik WhatsApp API entegrasyonu yok.
    public function storeReport(Request $request, VisitServiceRequest $visitServiceRequest)
    {
        $data = $request->validate([
            'visited_at' => 'required|date|before_or_equal:today',
            'note' => 'required|string|max:2000',
            'photo' => 'nullable|file|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            try {
                $photoPath = app(ImageCompressionService::class)->store($request->file('photo'), 'visit-service-reports');
            } catch (\RuntimeException $e) {
                if ($e->getMessage() === 'unsupported_image_format') {
                    return back()->withErrors(['photo' => 'Desteklenmeyen veya bozuk bir görsel dosyası.'])->withInput();
                }
                throw $e;
            }

            if (! $photoPath || ! Storage::disk('public')->exists($photoPath)) {
                return back()->withErrors(['photo' => 'Görsel yüklenirken bir sorun oluştu, lütfen tekrar deneyin.'])->withInput();
            }
        }

        $report = VisitServiceReport::create([
            'visit_service_request_id' => $visitServiceRequest->id,
            'admin_id' => session('admin_id'),
            'visited_at' => $data['visited_at'],
            'note' => $data['note'],
            'photo_path' => $photoPath,
        ]);

        if ($visitServiceRequest->status === 'yeni' || $visitServiceRequest->status === 'iletisime_gecildi') {
            $visitServiceRequest->update(['status' => 'aktif']);
        }

        $visitServiceRequest->loadMissing('familyUser', 'facility');
        notify_user(
            $visitServiceRequest->familyUser,
            'visit_service_report_logged',
            'Yeni ziyaret raporu',
            "\"{$visitServiceRequest->facility->name}\" kurumundaki {$visitServiceRequest->patient_name} için yeni bir ziyaret raporu eklendi.",
            ['visit_service_request_id' => $visitServiceRequest->id],
        );

        return back()->with('success', 'Ziyaret raporu eklendi ve aileye bildirim gönderildi.'.($report->photo_path ? ' Fotoğrafı WhatsApp\'tan iletmeyi unutmayın.' : ''));
    }

    public function destroyReport(VisitServiceReport $report)
    {
        if ($report->photo_path) {
            Storage::disk('public')->delete($report->photo_path);
        }
        $requestId = $report->visit_service_request_id;
        $report->delete();

        return redirect()->route('admin.visit-service.show', $requestId)->with('success', 'Rapor silindi.');
    }
}
