@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
<style>
    .category-form {
        max-width: 500px;
    }

    .category-form label {
        display: block;
        margin-top: 12px;
        font-weight: bold;
    }

    .category-form input,
    .category-form textarea {
        width: 100%;
        padding: 6px;
        margin-top: 4px;
        box-sizing: border-box;
    }

    .error {
        color: #b91c1c;
        font-size: 14px;
        margin-top: 4px;
    }

    .btn {
        margin-top: 20px;
        padding: 8px 16px;
    }
</style>

<div class="container">
    <div class="card category-form">
        <div class="card-header">
            <h1>Edit Kategori</h1>
            <p>
                <a href="{{ route('categories.index') }}">
                    &larr; Kembali ke daftar kategori
                </a>
            </p>
        </div>

        <div class="card-body">
            <form action="{{ route('categories.update', $category['id']) }}" method="POST">
                @csrf
                @method('PUT')

                <label for="nama_kategori">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="nama_kategori"
                       value="{{ old('nama_kategori', $category['nama_kategori']) }}">

                @error('nama_kategori')
                    <div class="error">{{ $message }}</div>
                @enderror

                <label for="deskripsi">Deskripsi (opsional)</label>
                <textarea name="deskripsi" id="deskripsi" rows="4">{{ old('deskripsi', $category['deskripsi']) }}</textarea>

                @error('deskripsi')
                    <div class="error">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn">Simpan</button>
            </form>
        </div>
    </div>
</div>
@endsection