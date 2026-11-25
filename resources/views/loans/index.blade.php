@extends('layouts.app')

@section('title', 'Daftar Peminjaman')

@section('content')
    @php
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
    @endphp

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h1 style="margin: 0;">Daftar Peminjaman</h1>
        @if ($isAdmin)
            <a href="{{ route('loans.create') }}" class="btn">+ Tambah Peminjaman</a>
        @endif
    </div>

    @if (isset($summary))
        <div class="stats-grid" style="margin-top: 16px; margin-bottom: 16px;">
            <div class="stat-card" style="padding: 12px;">
                <div class="value" style="font-size: 22px;">{{ $summary['total'] }}</div>
                <div class="label">Total Transaksi</div>
            </div>
            <div class="stat-card" style="padding: 12px;">
                <div class="value" style="font-size: 22px; color: #1e40af;">{{ $summary['dipinjam'] }}</div>
                <div class="label">Sedang Dipinjam</div>
            </div>
            <div class="stat-card" style="padding: 12px;">
                <div class="value" style="font-size: 22px; color: #991b1b;">{{ $summary['terlambat'] }}</div>
                <div class="label">Terlambat</div>
            </div>
            <div class="stat-card" style="padding: 12px;">
                <div class="value" style="font-size: 22px; color: #065f46;">{{ $summary['dikembalikan'] }}</div>
                <div class="label">Dikembalikan</div>
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('loans.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
        <input type="text" name="search" value="{{ $search ?? request('search') }}"
               placeholder="Cari kode TRX, nama anggota, NIM, atau judul buku..."
               style="flex: 1; min-width: 220px; padding: 6px 10px;">
        <select name="status" style="padding: 6px 10px;">
            <option value="">Semua Status</option>
            <option value="dipinjam" @selected(($status ?? request('status')) === 'dipinjam')>Dipinjam</option>
            <option value="terlambat" @selected(($status ?? request('status')) === 'terlambat')>Terlambat</option>
            <option value="dikembalikan" @selected(($status ?? request('status')) === 'dikembalikan')>Dikembalikan</option>
        </select>
        <button type="submit" class="btn">Filter</button>
        @if (($search ?? request('search')) || ($status ?? request('status')))
            <a href="{{ route('loans.index') }}" class="btn" style="background: #6b7280;">Reset</a>
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
                <th>Status &amp; Denda</th>
                @if ($isAdmin)
                    <th>Aksi</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($loans as $loan)
                <tr>
                    <td>
                        <a href="{{ route('loans.show', $loan['id']) }}">
                            <strong>{{ $loan->kode_transaksi ?? ('#' . $loan['id']) }}</strong>
                        </a>
                    </td>
                    <td>
                        {{ $loan['member']['nama'] }}
                        @if (!empty($loan['member']['nim']))
                            <div class="muted" style="font-size: 12px;">{{ $loan['member']['nim'] }}</div>
                        @endif
                    </td>
                    <td>{{ $loan['user']['name'] }}</td>
                    <td>
                        @foreach ($loan['loanItems'] as $item)
                            {{ $item['book']['judul'] }}@if (!$loop->last), @endif
                        @endforeach
                    </td>
                    <td>{{ $loan['tanggal_pinjam'] }}</td>
                    <td>{{ $loan['tanggal_kembali'] }}</td>
                    <td>
                        <span class="badge badge-{{ $loan['status'] }}">{{ ucfirst($loan['status']) }}</span>
                        @if (($loan->estimasi_denda ?? 0) > 0)
                            <div style="color: #991b1b; font-size: 12px; margin-top: 4px; font-weight: bold;">
                                Denda: {{ $loan->estimasi_denda_formatted }} ({{ $loan->hari_terlambat }} hr)
                            </div>
                        @endif
                    </td>
                    @if ($isAdmin)
                        <td>
                            <a href="{{ route('loans.show', $loan['id']) }}">Detail</a>
                            @if ($loan['status'] !== 'dikembalikan')
                                |
                                <a href="{{ route('loans.edit', $loan['id']) }}">Edit</a>
                                |
                                <form class="inline" action="{{ route('loans.kembalikan', $loan['id']) }}" method="POST"
                                      onsubmit="return confirm('Tandai peminjaman ini sudah dikembalikan?')">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit">Kembalikan</button>
                                </form>
                            @endif
                            |
                            <form class="inline" action="{{ route('loans.destroy', $loan['id']) }}" method="POST"
                                  onsubmit="return confirm('Hapus transaksi peminjaman ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Hapus</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isAdmin ? 8 : 7 }}">Belum ada data peminjaman.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $loans->links() }}
@endsection
