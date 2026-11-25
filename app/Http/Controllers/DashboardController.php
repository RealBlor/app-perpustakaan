<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        Loan::syncOverdue();

        $stats = null;

        try {
            $baseUrl = config('services.internal_api.base_url');
            if ($baseUrl) {
                $response = Http::timeout(2)->get($baseUrl . '/api/stats');
                if ($response->successful()) {
                    $stats = $response->json();
                }
            }
        } catch (ConnectionException $e) {
            $stats = null;
        }

        // Fallback langsung ke database jika service API eksternal tidak aktif saat demo
        if (! is_array($stats)) {
            $stats = [
                'total_buku' => Book::count(),
                'total_anggota' => Member::count(),
                'peminjaman_aktif' => Loan::active()->count(),
                'peminjaman_terlambat' => Loan::where('status', Loan::STATUS_TERLAMBAT)->count(),
            ];
        }

        $recentLoans = Loan::with(['member', 'user', 'loanItems.book'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'recentLoans'));
    }
}
