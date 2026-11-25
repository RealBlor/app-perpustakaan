@extends('layouts.app')

@section('title', 'Detail Peminjaman')

@section('content')
    @php
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
    @endphp

    <h1>Detail Peminjaman {{ $loan->kode_transaksi ?? '' }}</h1>
    <p><a href="{{ route('loans.index') }}">&larr; Kembali ke daftar peminjaman</a></p>

    <table class="info">
        <tr>
            <th>Kode Transaksi</th>
            <td><strong>{{ $loan->kode_transaksi ?? ('#' . $loan['id']) }}</strong></td>
        </tr>
        <tr>
            <th>Anggota</th>
            <td>{{ $loan['member']['nama'] }} ({{ $loan['member']['nim'] }})</td>
        </tr>
        <tr>
            <th>Petugas</th>
            <td>{{ $loan['user']['name'] }}</td>
        </tr>
        <tr>
            <th>Tanggal Pinjam</th>
            <td>{{ $loan['tanggal_pinjam'] }}</td>
        </tr>
        <tr>
            <th>Tanggal Kembali</th>
            <td>{{ $loan['tanggal_kembali'] }}</td>
        </tr>
        <tr>
            <th>Tanggal Dikembalikan</th>
            <td>{{ $loan['tanggal_dikembalikan'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td><span class="badge badge-{{ $loan['status'] }}">{{ ucfirst($loan['status']) }}</span></td>
        </tr>
        <tr>
            <th>Keterlambatan &amp; Denda</th>
            <td>
                @if (($loan->hari_terlambat ?? 0) > 0)
                    <span style="color: #991b1b; font-weight: bold;">
                        Terlambat {{ $loan->hari_terlambat }} hari &mdash; {{ $loan->estimasi_denda_formatted }}
                    </span>
                @else
                    <span class="muted">Tidak ada keterlambatan (Rp 0)</span>
                @endif
            </td>
        </tr>
    </table>

    <h2>Buku yang Dipinjam</h2>
    <table>
        <thead>
            <tr>
                <th>Judul</th>
                <th>Penulis</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($loan['loanItems'] as $item)
                <tr>
                    <td>{{ $item['book']['judul'] }}</td>
                    <td>{{ $item['book']['penulis'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($isAdmin && $loan['status'] !== 'dikembalikan')
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <form action="{{ route('loans.kembalikan', $loan['id']) }}" method="POST"
                  onsubmit="return confirm('Tandai peminjaman ini sudah dikembalikan?')">
                @csrf
                @method('PUT')
                <button type="submit" class="btn">Kembalikan Buku</button>
            </form>
            <a href="{{ route('loans.edit', $loan['id']) }}" class="btn" style="background: #4b5563;">Perpanjang Jatuh Tempo</a>
        </div>
    @endif
@endsection
