@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
    @endphp

    <h1>Dashboard</h1>
    <p>Ringkasan statistik dan aktivitas peminjaman perpustakaan.</p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="value">{{ $stats['total_buku'] }}</div>
            <div class="label">Total Buku</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $stats['total_anggota'] }}</div>
            <div class="label">Total Anggota</div>
        </div>
        <div class="stat-card">
            <div class="value">{{ $stats['peminjaman_aktif'] }}</div>
            <div class="label">Peminjaman Aktif</div>
        </div>
        <div class="stat-card">
            <div class="value" style="color: #991b1b;">{{ $stats['peminjaman_terlambat'] ?? 0 }}</div>
            <div class="label">Terlambat</div>
        </div>
    </div>

    @if (isset($recentLoans) && $recentLoans->isNotEmpty())
        <h2 style="margin-top: 28px;">Transaksi Peminjaman Terbaru</h2>
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentLoans as $loan)
                    <tr>
                        <td><a href="{{ route('loans.show', $loan->id) }}">{{ $loan->kode_transaksi }}</a></td>
                        <td>{{ $loan->member->nama ?? '-' }}</td>
                        <td>
                            @foreach ($loan->loanItems as $item)
                                {{ $item->book->judul ?? '-' }}@if (!$loop->last), @endif
                            @endforeach
                        </td>
                        <td>{{ $loan->tanggal_kembali }}</td>
                        <td>
                            <span class="badge badge-{{ $loan->status }}">{{ ucfirst($loan->status) }}</span>
                            @if ($loan->estimasi_denda > 0)
                                <div style="color: #991b1b; font-size: 12px;">{{ $loan->estimasi_denda_formatted }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p style="margin-top: 24px; display: flex; gap: 10px;">
        @if ($isAdmin)
            <a href="{{ route('loans.create') }}" class="btn">+ Transaksi Baru</a>
        @endif
        <a href="{{ route('loans.report') }}" class="btn" style="background: #4b5563;">Lihat Laporan Peminjaman</a>
    </p>
@endsection
