@extends('layouts.app')

@section('title', 'Tambah Anggota')

@section('content')

</head>
<body>
    <div class="container">
    <div class="card category-form">
        <div class="card-header">
    <h1>Tambah Anggota</h1>
    <p><a href="{{ route('members.index') }} " class="btn">&larr; Kembali ke daftar anggota</a></p>
<div class="card-body">
    <form action="{{ route('members.store') }}" method="POST">
        @csrf

        <label for="nama">Nama</label>
        <input type="text" name="nama" id="nama" value="{{ old('nama') }}">
        @error('nama')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="nim">NIM</label>
        <input type="text" name="nim" id="nim" value="{{ old('nim') }}">
        @error('nim')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}">
        @error('email')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="alamat">Alamat</label>
        <input type="text" name="alamat" id="alamat" value="{{ old('alamat') }}">
        @error('alamat')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="nomor_telepon">Nomor telepon</label>
        <input type="text" name="nomor_telepon" id="nomor_telepon" value="{{ old('nomor_telepon') }}">
        @error('nomor_telepon')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="status">Status</label>
        <select name="status" id="status">
            @foreach ($status as $value => $label)
                <option value="{{ $value }}" @selected(old('status', 'aktif') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <div class="error">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn">Simpan</button>
    </form>

         </div>
    </div>
</div>
@endsection