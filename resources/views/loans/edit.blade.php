@extends('layouts.app')

@section('title', 'Edit Peminjaman')

@section('content')
    <h1>Edit Peminjaman</h1>
    <p><a href="{{ route('loans.index') }}">&larr; Kembali ke daftar peminjaman</a></p>

    <div class="form-stack">
        <label>Anggota</label>
        <div>{{ $loan['member']['nama'] }} ({{ $loan['member']['nim'] }})</div>

        <label>Buku</label>
        <div>
            @foreach ($loan['loanItems'] as $item)
                {{ $item['book']['judul'] }}@if (!$loop->last), @endif
            @endforeach
        </div>

        <label>Status</label>
        <div>
            <span class="badge badge-{{ $loan['status'] }}">{{ ucfirst($loan['status']) }}</span>
            <span class="muted">(diatur otomatis; gunakan tombol Kembalikan untuk menutup peminjaman)</span>
        </div>

        <form action="{{ route('loans.update', $loan['id']) }}" method="POST">
            @csrf
            @method('PUT')

            <label for="tanggal_kembali">Tanggal Kembali (perpanjangan)</label>
            <input type="date" name="tanggal_kembali" id="tanggal_kembali"
                   value="{{ old('tanggal_kembali', \Illuminate\Support\Str::substr($loan['tanggal_kembali'], 0, 10)) }}">
            @error('tanggal_kembali')
                <div class="error">{{ $message }}</div>
            @enderror

            <button type="submit" class="btn" style="margin-top: 20px;">Perbarui</button>
        </form>
    </div>
@endsection
