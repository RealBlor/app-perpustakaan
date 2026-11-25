<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;

class StatsController extends Controller
{
    public function index()
    {
        Loan::syncOverdue();

        return response()->json([
            'total_buku' => Book::count(),
            'total_anggota' => Member::count(),
            // Aktif = bukunya belum kembali (dipinjam + terlambat).
            'peminjaman_aktif' => Loan::active()->count(),
            'peminjaman_terlambat' => Loan::where('status', Loan::STATUS_TERLAMBAT)->count(),
        ]);
    }
}
