@extends('layouts.app')

@section('title', 'Tambah Peminjaman')

@section('content')
    <h1>Tambah Peminjaman</h1>
    <p><a href="{{ route('loans.index') }}">&larr; Kembali ke daftar peminjaman</a></p>
    <p class="muted">Petugas pencatat: {{ auth()->user()->name }} (otomatis dari akun yang login).</p>

    <form action="{{ route('loans.store') }}" method="POST" class="form-stack">
        @csrf

        <label for="member_id">Anggota</label>
        <select name="member_id" id="member_id">
            <option value="">-- Pilih Anggota --</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>
                    {{ $member->nama }} ({{ $member->nim }})
                </option>
            @endforeach
        </select>
        @error('member_id')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="tanggal_pinjam">Tanggal Pinjam</label>
        <input type="date" name="tanggal_pinjam" id="tanggal_pinjam"
               value="{{ old('tanggal_pinjam', now()->toDateString()) }}">
        @error('tanggal_pinjam')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="tanggal_kembali">Tanggal Kembali</label>
        <input type="date" name="tanggal_kembali" id="tanggal_kembali"
               value="{{ old('tanggal_kembali', now()->addDays(7)->toDateString()) }}">
        @error('tanggal_kembali')
            <div class="error">{{ $message }}</div>
        @enderror

        <label>Buku yang Dipinjam</label>
        <div class="checkbox-list">
            @forelse ($books as $book)
                <label>
                    <input type="checkbox" name="book_ids[]" value="{{ $book->id }}"
                           @checked(in_array($book->id, old('book_ids', [])))
                           @disabled($book->stok < 1)>
                    {{ $book->judul }}
                    @if ($book->stok < 1)
                        <span class="muted">(stok habis)</span>
                    @else
                        <span class="muted">(stok: {{ $book->stok }})</span>
                    @endif
                </label>
            @empty
                <p>Belum ada data buku.</p>
            @endforelse
        </div>
        @error('book_ids')
            <div class="error">{{ $message }}</div>
        @enderror
        @error('book_ids.*')
            <div class="error">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn" style="margin-top: 20px;">Simpan</button>
    </form>
@endsection
