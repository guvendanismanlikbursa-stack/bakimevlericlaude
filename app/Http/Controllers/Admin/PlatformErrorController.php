<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\PlatformError;
use Illuminate\Http\Request;

class PlatformErrorController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $query = PlatformError::latest();

        if ($request->get('status', 'open') === 'open') {
            $query->whereNull('resolved_at');
        } elseif ($request->get('status') === 'resolved') {
            $query->whereNotNull('resolved_at');
        }

        $platformErrors = $query->paginate(30)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $platformErrors)) {
            return $redirect;
        }

        $openCount = PlatformError::whereNull('resolved_at')->count();

        return view('admin.platform-errors.index', compact('platformErrors', 'openCount'));
    }

    public function resolve(PlatformError $platformError)
    {
        $platformError->update(['resolved_at' => now()]);

        return back()->with('success', 'Hata çözüldü olarak işaretlendi.');
    }

    public function destroy(PlatformError $platformError)
    {
        $platformError->delete();

        return back()->with('success', 'Kayıt silindi.');
    }
}
