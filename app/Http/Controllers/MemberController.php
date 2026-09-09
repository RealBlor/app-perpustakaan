<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    private array $Status = [
        ['Status' => 0, 'nama_kategori' => 'Tidak Aktif'],
        ['Status' => 1, 'nama_kategori' => 'Aktif'],
    ];

    private array $Members = [
        ['Nama' => 'Ronuld', 'NIM' => 3125600001, 'Email' => 'AkuRonuld@gmail.com', 'Nomor_telepon' => '0812121212', 'Alamat' => 'Bandung', 'Status' => 'Aktif'],
        ['Nama' => 'Jokowi', 'NIM' => 3125600002, 'Email' => 'Jokowihebat@gmail.com', 'Nomor_telepon' => '089999999', 'Alamat' => 'Sidoarjo, Sidokare', 'Status' => 'Tidak aktif'],
        ['Nama' => 'Prabowo', 'NIM' => 3125600003, 'Email' => 'akupresident@gmail.com', 'Nomor_telepon' => '08222222', 'Alamat' => 'Jakarta', 'Status' => 'Tidak aktif'],
    ];

    public function index()
    {
        $Members = $this->Members;

        return view('members.index', compact('Members'));
    }

    public function create()
    {
        $Status = $this->Status;

        return view('members.create', compact('Status'));
    }

    public function store(StoreMemberRequest $request)
    {
        $validated = $request->validated();

        return redirect()->route('members.index')
            ->with('success', "Data \"{$validated['Nama']}\" berhasil ditambahkan (data dummy, belum tersimpan ke database).");
    }

    public function show(string $Nama)
    {
        $Member = collect($this->Members)->firstWhere('Nama', (int) $Nama);

        abort_if(! $Member, 404);

        return view('members.show', compact('Member'));
    }

    public function edit(string $Nama)
    {
        $Member = collect($this->Members)->firstWhere('Nama', (int) $Nama);

        abort_if(! $Member, 404);

        $Status = $this->Status;

        return view('members.edit', compact('Member', 'Status'));
    }

    public function update(Request $request, string $Nama)
    {
        $validated = $request->validate([
            'Nama' => 'required|string|max:200',
            'NIM' => 'required|string|max:100',
            'Email' => 'required|string|max:200',
            'Nomor_telepon' => 'required|string|max:100',
            'Alamat' => 'required|string|max:20',
            'Status' => 'required|integer|min:0',
        ]);

        return redirect()->route('members.index')
            ->with('success', "\"{$validated['Nama']}\" berhasil diperbarui (data dummy, belum tersimpan ke database).");
    }

    public function destroy(string $Nama)
    {
        return redirect()->route('members.index')
            ->with('success', "Buku dengan Nama {$Nama} berhasil dihapus (data dummy, belum tersimpan ke database).");
    }
}