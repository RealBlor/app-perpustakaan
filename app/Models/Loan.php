<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Loan extends Model
{
    public const STATUS_DIPINJAM = 'dipinjam';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';
    public const STATUS_TERLAMBAT = 'terlambat';

    /** Batas maksimal buku aktif yang dapat dipinjam per anggota. */
    public const MAX_ACTIVE_BOOKS_PER_MEMBER = 5;

    /** Tarif denda keterlambatan per hari per buku (Rupiah). */
    public const FINE_PER_DAY_PER_BOOK = 2000;

    protected $fillable = [
        'member_id',
        'user_id',
        'tanggal_pinjam',
        'tanggal_kembali',
        'tanggal_dikembalikan',
        'status',
    ];

    protected $appends = [
        'kode_transaksi',
        'hari_terlambat',
        'estimasi_denda',
        'estimasi_denda_formatted',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    /** Peminjaman yang bukunya belum kembali (dipinjam atau terlambat). */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_DIPINJAM, self::STATUS_TERLAMBAT]);
    }

    /** Filter berdasarkan pencarian nama anggota, NIM, judul buku, atau ID/kode transaksi. */
    public function scopeSearch($query, ?string $search)
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        $numericId = preg_replace('/^TRX-0*/i', '', $search);

        return $query->where(function ($q) use ($search, $numericId) {
            if (ctype_digit($numericId)) {
                $q->orWhere('id', (int) $numericId);
            }

            $q->orWhereHas('member', function ($mq) use ($search) {
                $mq->where('nama', 'like', "%{$search}%")
                   ->orWhere('nim', 'like', "%{$search}%");
            })->orWhereHas('loanItems.book', function ($bq) use ($search) {
                $bq->where('judul', 'like', "%{$search}%");
            });
        });
    }

    /** Filter berdasarkan status peminjaman yang valid. */
    public function scopeByStatus($query, ?string $status)
    {
        $validStatuses = [
            self::STATUS_DIPINJAM,
            self::STATUS_DIKEMBALIKAN,
            self::STATUS_TERLAMBAT,
        ];

        if ($status && in_array($status, $validStatuses, true)) {
            return $query->where('status', $status);
        }

        return $query;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_DIKEMBALIKAN;
    }

    public function isOverdue(): bool
    {
        if ($this->isReturned()) {
            return false;
        }

        return Carbon::parse($this->tanggal_kembali)->startOfDay()->lt(now()->startOfDay());
    }

    /** Kode transaksi yang rapi untuk tampilan demo (mis. TRX-0001). */
    public function getKodeTransaksiAttribute(): string
    {
        return 'TRX-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Jumlah hari keterlambatan dibandingkan tanggal jatuh tempo. */
    public function getHariTerlambatAttribute(): int
    {
        if (! $this->tanggal_kembali) {
            return 0;
        }

        $dueDate = Carbon::parse($this->tanggal_kembali)->startOfDay();
        $referenceDate = $this->isReturned() && $this->tanggal_dikembalikan
            ? Carbon::parse($this->tanggal_dikembalikan)->startOfDay()
            : now()->startOfDay();

        if ($referenceDate->lte($dueDate)) {
            return 0;
        }

        return (int) $dueDate->diffInDays($referenceDate);
    }

    /** Total denda keterlambatan berdasarkan jumlah buku dan hari terlambat. */
    public function getEstimasiDendaAttribute(): int
    {
        $days = $this->hari_terlambat;
        if ($days <= 0) {
            return 0;
        }

        $bookCount = $this->relationLoaded('loanItems')
            ? $this->loanItems->count()
            : $this->loanItems()->count();

        return $days * max(1, $bookCount) * self::FINE_PER_DAY_PER_BOOK;
    }

    /** Format mata uang Rupiah untuk tampilan UI/API. */
    public function getEstimasiDendaFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->estimasi_denda, 0, ',', '.');
    }

    /**
     * Buat peminjaman baru beserta item bukunya dan kurangi stok tiap buku.
     * Semua dilakukan dalam satu transaksi: kalau satu langkah gagal, tidak ada yang tersimpan.
     *
     * @param  array{member_id:int, user_id:int, tanggal_pinjam:string, tanggal_kembali:string}  $data
     * @param  array<int,int|string>  $bookIds
     *
     * @throws ValidationException  anggota nonaktif, melebihi kuota, atau stok buku habis
     */
    public static function borrow(array $data, array $bookIds): self
    {
        $bookIds = array_values(array_unique(array_map('intval', $bookIds)));

        return DB::transaction(function () use ($data, $bookIds) {
            $member = Member::findOrFail($data['member_id']);

            if ($member->status !== 'aktif') {
                throw ValidationException::withMessages([
                    'member_id' => 'Anggota ini berstatus nonaktif dan tidak dapat meminjam buku.',
                ]);
            }

            // Cek batas maksimal peminjaman aktif per anggota
            $activeBooksCount = LoanItem::whereHas('loan', function ($q) use ($member) {
                $q->where('member_id', $member->id)->active();
            })->count();

            if (($activeBooksCount + count($bookIds)) > self::MAX_ACTIVE_BOOKS_PER_MEMBER) {
                $sisaKuota = max(0, self::MAX_ACTIVE_BOOKS_PER_MEMBER - $activeBooksCount);
                throw ValidationException::withMessages([
                    'book_ids' => "Batas maksimal peminjaman adalah " . self::MAX_ACTIVE_BOOKS_PER_MEMBER . " buku aktif per anggota (sisa kuota: {$sisaKuota} buku).",
                ]);
            }

            $books = Book::whereIn('id', $bookIds)->lockForUpdate()->get();

            if ($books->count() !== count($bookIds)) {
                throw ValidationException::withMessages([
                    'book_ids' => 'Satu atau lebih buku yang dipilih tidak ditemukan.',
                ]);
            }

            $habis = $books->filter(fn (Book $book) => $book->stok < 1)->pluck('judul');
            if ($habis->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'book_ids' => 'Stok habis untuk: ' . $habis->implode(', ') . '.',
                ]);
            }

            $jatuhTempoLewat = Carbon::parse($data['tanggal_kembali'])->startOfDay()->lt(now()->startOfDay());

            $loan = static::create([
                'member_id' => $data['member_id'],
                'user_id' => $data['user_id'],
                'tanggal_pinjam' => $data['tanggal_pinjam'],
                'tanggal_kembali' => $data['tanggal_kembali'],
                'status' => $jatuhTempoLewat ? self::STATUS_TERLAMBAT : self::STATUS_DIPINJAM,
            ]);

            foreach ($books as $book) {
                $loan->loanItems()->create(['book_id' => $book->id]);
                $book->decrement('stok');
            }

            return $loan;
        });
    }

    /**
     * Perpanjang tanggal jatuh tempo peminjaman yang masih aktif.
     */
    public function extendDueDate(string $newDueDate): void
    {
        $jatuhTempoLewat = Carbon::parse($newDueDate)->startOfDay()->lt(now()->startOfDay());

        $this->update([
            'tanggal_kembali' => $newDueDate,
            'status' => $jatuhTempoLewat ? self::STATUS_TERLAMBAT : self::STATUS_DIPINJAM,
        ]);
    }

    /**
     * Tandai peminjaman sudah dikembalikan dan kembalikan stok bukunya.
     *
     * @return bool false jika peminjaman ini sebelumnya sudah dikembalikan (stok tidak ditambah dua kali)
     */
    public function markReturned(): bool
    {
        return DB::transaction(function () {
            $loan = static::whereKey($this->getKey())->lockForUpdate()->first();

            if (! $loan || $loan->isReturned()) {
                return false;
            }

            $loan->restockBooks();
            $loan->update([
                'status' => self::STATUS_DIKEMBALIKAN,
                'tanggal_dikembalikan' => now()->toDateString(),
            ]);

            $this->refresh();

            return true;
        });
    }

    /**
     * Hapus peminjaman. Jika bukunya belum kembali, stoknya dikembalikan dulu.
     */
    public function deleteAndRestock(): void
    {
        DB::transaction(function () {
            $loan = static::whereKey($this->getKey())->lockForUpdate()->first();

            if (! $loan) {
                return;
            }

            if (! $loan->isReturned()) {
                $loan->restockBooks();
            }

            $loan->loanItems()->delete();
            $loan->delete();
        });
    }

    /**
     * Ubah status 'dipinjam' menjadi 'terlambat' untuk peminjaman yang lewat tanggal kembali.
     * Dipanggil sebelum menampilkan daftar/statistik supaya status selalu mutakhir.
     */
    public static function syncOverdue(): int
    {
        return static::where('status', self::STATUS_DIPINJAM)
            ->whereDate('tanggal_kembali', '<', now()->toDateString())
            ->update(['status' => self::STATUS_TERLAMBAT]);
    }

    private function restockBooks(): void
    {
        foreach ($this->loanItems()->with('book')->get() as $item) {
            $item->book?->increment('stok');
        }
    }
}
