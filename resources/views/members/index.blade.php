@extends('layouts.app')

@section('title', 'Daftar Anggota')

@section('content')
    <h1>Daftar Anggota</h1>

    <p><a href="{{ route('members.create')}}" class="btn"> Tambah Anggota </a></p>

    <form action="{{ route('members.index') }}" method="GET">
        <label for="search">Cari nama anggota</label>
        <input type="text" name="search" id="search" value="{{ request('search') }}">
        <button type="submit">Cari</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>NIM</th>
                <th>Email</th>
                <th>No. Telepon</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($members as $Member)
                <tr>
                    <td>{{ $Member['id'] }}</td>
                    <td>{{ $Member['nama'] }}</td>
                    <td>{{ $Member['nim'] }}</td>
                    <td>{{ $Member['email'] }}</td>
                    <td>{{ $Member['nomor_telepon'] }}</td>
                    <td>{{ ucfirst($Member['status']) }}</td>
                    <td>
                        <a href="{{ route('members.show', $Member['id']) }}">Detail</a>
                        |
                        <a href="{{ route('members.edit', $Member['id']) }}">Edit</a>
                        |
                        <form class="inline" action="{{ route('members.destroy', $Member['id']) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada data anggota.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $members->appends(request()->query())->links() }}

    <p><em>Catatan: data di atas masih data dummy (array statis di Controller). Form tambah/edit anggota dan CRUD lengkap anggota baru dibuat mulai Pertemuan 5.</em></p>
@endsection