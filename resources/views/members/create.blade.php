<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Buku</title>
    <style>
        body { font-family: sans-serif; margin: 40px; max-width: 500px; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input, select { width: 100%; padding: 6px; margin-top: 4px; box-sizing: border-box; }
        .error { color: #b91c1c; font-size: 14px; margin-top: 4px; }
        .btn { margin-top: 20px; padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Tambah Buku</h1>
    <p><a href="{{ route('members.index') }}">&larr; Kembali ke daftar buku</a></p>

    <form action="{{ route('members.store') }}" method="POST">
        @csrf

        <label for="Nama">nama</label>
        <input type="text" name="Nama" id="Nama" value="{{ old('Nama') }}">
        @error('Nama')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="NIM">NIM</label>
        <input type="text" name="NIM" id="NIM" value="{{ old('NIM') }}">
        @error('NIM')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="Email">Email</label>
        <input type="text" name="Email" id="Email" value="{{ old('Email') }}" required="%@gmail.com">
        @error('Email')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="Alamat">Alamat</label>
        <input type="text" name="Alamat" id="Alamat" value="{{ old('Alamat') }}">
        @error('Alamat')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="Nomor_telepon">Nomor telepon</label>
        <input type="text" name="Nomor_telepon" id="Nomor_telepon" value="{{ old('Nomor_telepon') }}">
        @error('Nomor_telepon')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="Status">Status</label>
        <input type="number" name="Status" id="Status" value="{{ old('Status', 1) }}">
        @error('Status')
            <div class="error">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn">Simpan</button>
    </form>
</body>
</html>