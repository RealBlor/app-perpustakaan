@extends('layouts.app')

@section('title', 'Detail Anggota')

@section('content')
</head>
<body>
    <h1>Detail Anggota</h1>
    <p><a href="{{ route('members.index') }}">&larr; Kembali ke daftar anggota</a></p>

    <table>
        <tr>
            <th>Nama</th>
            <td>{{ $Member->nama }}</td>
        </tr>
        <tr>
            <th>NIM</th>
            <td>{{ $Member->nim }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $Member->email }}</td>
        </tr>
        <tr>
            <th>Nomor Telepon</th>
            <td>{{ $Member->nomor_telepon }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ $Member->status }}</td>
        </tr>
    </table>
</body>
@endsection