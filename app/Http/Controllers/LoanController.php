<?php

namespace App\Http\Controllers;

use App\Http\Resources\LoanResource;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        Loan::syncOverdue();

        $search = $request->query('search');
        $status = $request->query('status');

        $loans = Loan::with(['member', 'user', 'loanItems.book'])
            ->search($search)
            ->byStatus($status)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => Loan::count(),
            'dipinjam' => Loan::where('status', Loan::STATUS_DIPINJAM)->count(),
            'terlambat' => Loan::where('status', Loan::STATUS_TERLAMBAT)->count(),
            'dikembalikan' => Loan::where('status', Loan::STATUS_DIKEMBALIKAN)->count(),
        ];

        return view('loans.index', compact('loans', 'summary', 'search', 'status'));
    }

    public function create()
    {
        // Hanya anggota aktif yang bisa meminjam.
        $members = Member::where('status', 'aktif')->orderBy('nama')->get();
        $books = Book::orderBy('judul')->get();
        $maxBooks = Loan::MAX_ACTIVE_BOOKS_PER_MEMBER;

        return view('loans.create', compact('members', 'books', 'maxBooks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|integer|exists:members,id',
            'tanggal_pinjam' => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_pinjam',
            'book_ids' => 'required|array|min:1',
            'book_ids.*' => 'integer|exists:books,id',
        ], [
            'member_id.required' => 'Anggota wajib dipilih.',
            'tanggal_pinjam.required' => 'Tanggal pinjam wajib diisi.',
            'tanggal_kembali.required' => 'Tanggal kembali wajib diisi.',
            'tanggal_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
            'book_ids.required' => 'Pilih minimal satu buku.',
            'book_ids.min' => 'Pilih minimal satu buku.',
        ]);

        // Petugas = akun yang sedang login. Stok dikurangi di dalam Loan::borrow().
        $loan = Loan::borrow([
            'member_id' => $validated['member_id'],
            'user_id' => Auth::id(),
            'tanggal_pinjam' => $validated['tanggal_pinjam'],
            'tanggal_kembali' => $validated['tanggal_kembali'],
        ], $validated['book_ids']);

        return redirect()->route('loans.index')
            ->with('success', "Transaksi peminjaman {$loan->kode_transaksi} berhasil dibuat.");
    }

    public function show(string $id)
    {
        Loan::syncOverdue();

        $loan = Loan::with(['member', 'user', 'loanItems.book'])->findOrFail($id);

        return view('loans.show', compact('loan'));
    }

    public function edit(string $id)
    {
        $loan = Loan::with(['member', 'user', 'loanItems.book'])->findOrFail($id);

        if ($loan->isReturned()) {
            return redirect()->route('loans.index')
                ->with('error', 'Peminjaman yang sudah dikembalikan tidak dapat diedit.');
        }

        return view('loans.edit', compact('loan'));
    }

    /**
     * Hanya tanggal kembali (perpanjangan) yang bisa diubah. Status dikelola otomatis:
     * "dikembalikan" lewat tombol Kembalikan, "terlambat" lewat tanggal jatuh tempo.
     */
    public function update(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        if ($loan->isReturned()) {
            return redirect()->route('loans.index')
                ->with('error', 'Peminjaman yang sudah dikembalikan tidak dapat diedit.');
        }

        $tanggalPinjam = Carbon::parse($loan->tanggal_pinjam)->toDateString();

        $validated = $request->validate([
            'tanggal_kembali' => 'required|date|after_or_equal:' . $tanggalPinjam,
        ], [
            'tanggal_kembali.required' => 'Tanggal kembali wajib diisi.',
            'tanggal_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
        ]);

        $loan->extendDueDate($validated['tanggal_kembali']);

        return redirect()->route('loans.index')
            ->with('success', "Transaksi {$loan->kode_transaksi} berhasil diperbarui.");
    }

    public function destroy(string $id)
    {
        $loan = Loan::findOrFail($id);
        $kode = $loan->kode_transaksi;
        $loan->deleteAndRestock();

        return redirect()->route('loans.index')
            ->with('success', "Transaksi {$kode} berhasil dihapus.");
    }

    public function kembalikan(string $id)
    {
        $loan = Loan::with('loanItems')->findOrFail($id);

        // Hitung denda sebelum status berubah menjadi dikembalikan
        $denda = $loan->estimasi_denda;
        $dendaFormatted = $loan->estimasi_denda_formatted;
        $hariTerlambat = $loan->hari_terlambat;

        if (! $loan->markReturned()) {
            return redirect()->route('loans.index')
                ->with('error', 'Peminjaman ini sudah dikembalikan sebelumnya.');
        }

        $pesan = "Buku pada transaksi {$loan->kode_transaksi} berhasil dikembalikan dan stok diperbarui.";
        if ($denda > 0) {
            $pesan .= " Tercatat keterlambatan {$hariTerlambat} hari dengan denda {$dendaFormatted}.";
        }

        return redirect()->route('loans.index')
            ->with('success', $pesan);
    }

    public function report(Request $request)
    {
        Loan::syncOverdue();

        $body = ['data' => [], 'meta' => null];
        $apiAvailable = false;

        try {
            $baseUrl = config('services.internal_api.base_url');
            if ($baseUrl) {
                $response = Http::timeout(3)->get($baseUrl . '/api/loans', [
                    'page' => $request->query('page', 1),
                    'status' => $request->query('status'),
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    $apiAvailable = true;
                }
            }
        } catch (ConnectionException $e) {
            $apiAvailable = false;
        }

        // Fallback otomatis ke Eloquent lokal dengan struktur array yang identik
        // dengan respons API (mendukung snake_case maupun camelCase).
        if (! $apiAvailable) {
            $paginator = Loan::with(['member', 'user', 'loanItems.book'])
                ->byStatus($request->query('status'))
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString();

            if (class_exists(LoanResource::class)) {
                $body = LoanResource::collection($paginator)->response()->getData(true);
            } else {
                $body['data'] = collect($paginator->items())->map(function (Loan $loan) {
                    $items = $loan->loanItems->map(fn ($item) => [
                        'id' => $item->id,
                        'book_id' => $item->book_id,
                        'book' => $item->book ? $item->book->toArray() : ['judul' => '-'],
                    ])->all();

                    return [
                        'id' => $loan->id,
                        'kode_transaksi' => $loan->kode_transaksi,
                        'member' => $loan->member ? $loan->member->toArray() : ['nama' => '-', 'nim' => '-'],
                        'user' => $loan->user ? $loan->user->toArray() : ['name' => '-'],
                        'loan_items' => $items,
                        'loanItems' => $items,
                        'tanggal_pinjam' => $loan->tanggal_pinjam,
                        'tanggal_kembali' => $loan->tanggal_kembali,
                        'tanggal_dikembalikan' => $loan->tanggal_dikembalikan,
                        'status' => $loan->status,
                        'hari_terlambat' => $loan->hari_terlambat,
                        'estimasi_denda' => $loan->estimasi_denda,
                        'estimasi_denda_formatted' => $loan->estimasi_denda_formatted,
                    ];
                })->all();

                $body['meta'] = [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ];
            }
        }

        return view('loans.report', [
            'loans' => $body['data'] ?? [],
            'meta' => $body['meta'] ?? null,
        ]);
    }
}
