@extends('layouts.app')

@section('title', 'Daftar Buku')

@section('content')

    <h1>Tambah Buku</h1>
<p><a href="{{ route('books.index') }}" class="btn">&larr; Kembali ke daftar buku</a></p>

    <form action="{{ route('books.store') }}" method="POST">
        @csrf

        <label for="judul">Judul</label>
        <input type="text" name="judul" id="judul" value="{{ old('judul') }}"><br>
        @error('judul')
            <div class="error">{{ $message }}
        @enderror

        <label for="penulis">Penulis</label>
        <input type="text" name="penulis" id="penulis" value="{{ old('penulis') }}">
        <br>
        @error('penulis')
            <div class="error">{{ $message }}
            <br>
        @enderror

        <label for="penerbit">Penerbit</label>
        <input type="text" name="penerbit" id="penerbit" value="{{ old('penerbit') }}"><br>
        @error('penerbit')
            <div class="error">{{ $message }}<br>
        @enderror

        <label for="tahun_terbit">Tahun Terbit</label>
        <input type="number" name="tahun_terbit" id="tahun_terbit" value="{{ old('tahun_terbit') }}"><br>
        @error('tahun_terbit')
            <div class="error">{{ $message }}
        @enderror

        <label for="isbn">ISBN (opsional)</label>
        <input type="text" name="isbn" id="isbn" value="{{ old('isbn') }}"><br>
        @error('isbn')
            <div class="error">{{ $message }}
        @enderror

        <label for="stok">Stok</label>
        <input type="number" name="stok" id="stok" value="{{ old('stok', 1) }}"><br>
        @error('stok')
            <div class="error">{{ $message }}
        @enderror

        <label for="category_id">Kategori</label>
        <select name="category_id" id="category_id">
            <option value="">-- Pilih Kategori --</option>
            @foreach ($categories as $category)
                <option value="{{ $category['id'] }}" @selected(old('category_id') == $category['id'])>
                    {{ $category['nama_kategori'] }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <div class="error">{{ $message }}
        @enderror

        <button type="submit" class="btn">Simpan</button>
    </form>
</body>
@endsection