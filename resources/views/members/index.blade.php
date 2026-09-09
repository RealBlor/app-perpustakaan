<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Nama</title>
    <style>
        body { font-family: sans-serif; margin: 40px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
        .success { background: #d1fae5; color: #065f46; padding: 10px 14px; border-radius: 4px; margin-top: 16px; }
        .btn { display: inline-block; padding: 6px 14px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; }
        form.inline { display: inline; }
    </style>
</head>
<body>
    <h1>Daftar Nama</h1>

    @if (session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    <p><a href="{{ route('members.create') }}" class="btn">+ Tambah Buku</a></p>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>NIM</th>
                <th>Email</th>
                <th>Alamat</th>
                <th>Nomor telepon</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($Members as $member)
                <tr>
                    <td>{{ $member['Nama'] }}</td>
                    <td>{{ $member['NIM'] }}</td>
                    <td>{{ $member['Email'] }}</td>
                    <td>{{ $member['Alamat'] }}</td>
                    <td>{{ $member['Nomor_telepon'] }}</td>
                    <td>{{ $member['Status'] }}</td>
                    <td>
                        <a href="{{ route('members.show', $member['Nama']) }}">Detail</a>
                        |
                        <a href="{{ route('members.edit', $member['Nama']) }}">Edit</a>
                        |
                        <form class="inline" action="{{ route('members.destroy', $member['Nama']) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada data nama.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p><em>Catatan: data di atas masih data dummy (array statis di Controller), belum dari database. Migration &amp; Model Eloquent baru dibuat di Pertemuan 5.</em></p>
</body>
</html>