<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Http\Requests\StoreMemberRequest;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    private array $status = [
        'aktif' => 'Aktif',
        'nonaktif' => 'Tidak Aktif',
    ];

    private array $Members = [
    ['id' => 1, 'nama' => 'Siti Aminah', 'nim' => '2310501001', 'email' => 'siti.aminah@pens.ac.id', 'nomor_telepon' => '081234567890', 'status' => 'aktif'],
    ['id' => 2, 'nama' => 'Budi Santoso', 'nim' => '2310501002', 'email' => 'budi.santoso@pens.ac.id', 'nomor_telepon' => '081298765432', 'status' => 'aktif'],
    ['id' => 3, 'nama' => 'Dewi Lestari', 'nim' => '2310501003', 'email' => 'dewi.lestari@pens.ac.id', 'nomor_telepon' => '081211122233', 'status' => 'nonaktif'],
];

    public function index()
    {
        $Members = Member::paginate(10);

        return view('members.index', compact('Members'));
    }

    public function create()
    {
        $status = $this->status;

        return view('members.create', compact('status'));
    }

    public function store(StoreMemberRequest $request)
    {
        $validated = $request->validated();

        Member::create($validated);

        return redirect()->route('members.index')
            ->with('success', "Data \"{$validated['nama']}\" berhasil ditambahkan.");
    }

    public function show(string $id)
    {
        $Member = Member::findOrFail($id);
        return view('members.show', compact('Member'));
    }

    public function edit(string $id)
    {
        $member = Member::findOrFail($id);
        $status = $this->status;

        return view('members.edit', compact('member', 'status'));
    }

    public function update(Request $request, string $id)
    {
        $member = Member::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'nim' => 'required|string|max:20|unique:members,nim,' . $member->id,
            'email' => 'required|email|max:100|unique:members,email,' . $member->id,
            'nomor_telepon' => 'required|string|max:15',
            'alamat' => 'required|string',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $member->update($validated);

        return redirect()->route('members.index')
            ->with('success', "\"{$validated['nama']}\" berhasil diperbarui.");
    }

    public function destroy(string $id)
    {
        $member = Member::findOrFail($id);
        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Anggota berhasil dihapus.');
    }
}