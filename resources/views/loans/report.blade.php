@extends('layouts.app')

@section('title', 'Laporan Peminjaman')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h1 style="margin: 0;">Laporan Peminjaman</h1>
        <button type="button" class="btn" onclick="window.print()" style="background: #4b5563;">Cetak Laporan</button>
    </div>

    <p class="muted">Rekapitulasi transaksi peminjaman buku perpustakaan.</p>

    <form method="GET" action="{{ route('loans.report') }}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
        <select name="status" style="padding: 6px 10px;">
            <option value="">Semua Status</option>
            <option value="dipinjam" @selected(request('status') === 'dipinjam')>Dipinjam</option>
            <option value="terlambat" @selected(request('status') === 'terlambat')>Terlambat</option>
            <option value="dikembalikan" @selected(request('status') === 'dikembalikan')>Dikembalikan</option>
        </select>
        <button type="submit" class="btn">Filter Status</button>
        @if (request('status'))
            <a href="{{ route('loans.report') }}" class="btn" style="background: #6b7280;">Reset</a>
        @endif
    </form>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Anggota</th>
                <th>Petugas</th>
                <th>Buku</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Kembali</th>
                <th>Tgl Dikembalikan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($loans as $loan)
                @php
                    $items = data_get($loan, 'loan_items', data_get($loan, 'loanItems', []));
                    $kode = data_get($loan, 'kode_transaksi', 'TRX-' . str_pad((string) data_get($loan, 'id', 0), 4, '0', STR_PAD_LEFT));
                    $status = data_get($loan, 'status', 'dipinjam');
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('loans.show', data_get($loan, 'id')) }}">
                            <strong>{{ $kode }}</strong>
                        </a>
                    </td>
                    <td>
                        {{ data_get($loan, 'member.nama', '-') }}
                        @if (data_get($loan, 'member.nim'))
                            <div class="muted" style="font-size: 12px;">{{ data_get($loan, 'member.nim') }}</div>
                        @endif
                    </td>
                    <td>{{ data_get($loan, 'user.name', '-') }}</td>
                    <td>
                        @forelse ($items as $item)
                            {{ data_get($item, 'book.judul', '-') }}@if (!$loop->last), @endif
                        @empty
                            <span class="muted">-</span>
                        @endforelse
                    </td>
                    <td>{{ data_get($loan, 'tanggal_pinjam', '-') }}</td>
                    <td>{{ data_get($loan, 'tanggal_kembali', '-') }}</td>
                    <td>{{ data_get($loan, 'tanggal_dikembalikan') ?? '-' }}</td>
                    <td><span class="badge badge-{{ $status }}">{{ ucfirst($status) }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada data laporan peminjaman.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (!empty($meta) && ($meta['last_page'] ?? 1) > 1)
        <nav class="pagination">
            @php
                $current = (int) ($meta['current_page'] ?? 1);
                $last = (int) ($meta['last_page'] ?? 1);
            @endphp

            @if ($current <= 1)
                <span class="pagination-disabled">&laquo; Sebelumnya</span>
            @else
                <a href="{{ request()->fullUrlWithQuery(['page' => $current - 1]) }}">&laquo; Sebelumnya</a>
            @endif

            @for ($page = 1; $page <= $last; $page++)
                @if ($page === $current)
                    <span class="pagination-current">{{ $page }}</span>
                @else
                    <a href="{{ request()->fullUrlWithQuery(['page' => $page]) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($current >= $last)
                <span class="pagination-disabled">Berikutnya &raquo;</span>
            @else
                <a href="{{ request()->fullUrlWithQuery(['page' => $current + 1]) }}">Berikutnya &raquo;</a>
            @endif
        </nav>
    @endif
@endsection
