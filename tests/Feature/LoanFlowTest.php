<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;
    private Member $member;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = User::factory()->create(['role' => 'petugas']);
        $this->member = Member::factory()->create(['status' => 'aktif']);
        $this->category = Category::create(['nama_kategori' => 'Teknologi']);
    }

    private function book(int $stok = 3): Book
    {
        return Book::factory()->create([
            'stok' => $stok,
            'category_id' => $this->category->id,
        ]);
    }

    /** Buat peminjaman lewat logika yang sama dengan controller. */
    private function loan(array $books, array $override = []): Loan
    {
        return Loan::borrow(array_merge([
            'member_id' => $this->member->id,
            'user_id' => $this->petugas->id,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(7)->toDateString(),
        ], $override), collect($books)->pluck('id')->all());
    }

    private function payload(array $books, array $override = []): array
    {
        return array_merge([
            'member_id' => $this->member->id,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(7)->toDateString(),
            'book_ids' => collect($books)->pluck('id')->all(),
        ], $override);
    }

    // ---------------------------------------------------------------- meminjam

    public function test_borrowing_creates_loan_and_decrements_stock(): void
    {
        $a = $this->book(3);
        $b = $this->book(1);

        $this->actingAs($this->petugas)
            ->post('/loans', $this->payload([$a, $b]))
            ->assertRedirect(route('loans.index'));

        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseCount('loan_items', 2);
        $this->assertDatabaseHas('loans', [
            'member_id' => $this->member->id,
            'user_id' => $this->petugas->id,   // petugas = akun yang login
            'status' => 'dipinjam',
        ]);
        $this->assertSame(2, $a->fresh()->stok);
        $this->assertSame(0, $b->fresh()->stok);
    }

    public function test_cannot_borrow_out_of_stock_book_and_nothing_is_saved(): void
    {
        $ada = $this->book(2);
        $habis = $this->book(0);

        $this->actingAs($this->petugas)
            ->post('/loans', $this->payload([$ada, $habis]))
            ->assertSessionHasErrors('book_ids');

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
        $this->assertSame(2, $ada->fresh()->stok);   // transaksi dibatalkan, stok tidak berubah
    }

    public function test_inactive_member_cannot_borrow(): void
    {
        $book = $this->book(2);
        $nonaktif = Member::factory()->create(['status' => 'nonaktif']);

        $this->actingAs($this->petugas)
            ->post('/loans', $this->payload([$book], ['member_id' => $nonaktif->id]))
            ->assertSessionHasErrors('member_id');

        $this->assertDatabaseCount('loans', 0);
        $this->assertSame(2, $book->fresh()->stok);
    }

    public function test_return_date_cannot_be_before_borrow_date(): void
    {
        $book = $this->book(2);

        $this->actingAs($this->petugas)
            ->post('/loans', $this->payload([$book], [
                'tanggal_pinjam' => now()->toDateString(),
                'tanggal_kembali' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('tanggal_kembali');

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_create_page_loads_and_lists_only_active_members(): void
    {
        $this->book(2);
        $nonaktif = Member::factory()->create(['status' => 'nonaktif', 'nama' => 'Anggota Nonaktif Uji']);

        $this->actingAs($this->petugas)
            ->get('/loans/create')
            ->assertOk()
            ->assertSee($this->member->nama)
            ->assertDontSee($nonaktif->nama);
    }

    // --------------------------------------------------------------- mengembalikan

    public function test_returning_restores_stock_exactly_once(): void
    {
        $book = $this->book(3);
        $loan = $this->loan([$book]);
        $this->assertSame(2, $book->fresh()->stok);

        $this->actingAs($this->petugas)
            ->put("/loans/{$loan->id}/kembalikan")
            ->assertRedirect(route('loans.index'))
            ->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame('dikembalikan', $loan->status);
        $this->assertSame(now()->toDateString(), substr((string) $loan->tanggal_dikembalikan, 0, 10));
        $this->assertSame(3, $book->fresh()->stok);

        // Kembalikan lagi: ditolak, stok tidak bertambah dua kali.
        $this->actingAs($this->petugas)
            ->put("/loans/{$loan->id}/kembalikan")
            ->assertSessionHas('error');

        $this->assertSame(3, $book->fresh()->stok);
    }

    // ------------------------------------------------------------------ terlambat

    public function test_overdue_loan_becomes_terlambat_when_listing(): void
    {
        $loan = $this->loan([$this->book(3)], [
            'tanggal_pinjam' => now()->subDays(10)->toDateString(),
            'tanggal_kembali' => now()->subDays(3)->toDateString(),
        ]);

        $this->actingAs($this->petugas)->get('/loans')->assertOk();

        $this->assertSame('terlambat', $loan->fresh()->status);
    }

    public function test_loan_due_today_is_not_late_yet(): void
    {
        $loan = $this->loan([$this->book(3)], [
            'tanggal_kembali' => now()->toDateString(),
        ]);

        $this->actingAs($this->petugas)->get('/loans')->assertOk();

        $this->assertSame('dipinjam', $loan->fresh()->status);
    }

    public function test_returned_loan_is_not_marked_late(): void
    {
        $loan = $this->loan([$this->book(3)], [
            'tanggal_pinjam' => now()->subDays(10)->toDateString(),
            'tanggal_kembali' => now()->subDays(3)->toDateString(),
        ]);
        $loan->markReturned();

        $this->actingAs($this->petugas)->get('/loans')->assertOk();

        $this->assertSame('dikembalikan', $loan->fresh()->status);
    }

    // ----------------------------------------------------------------- edit/hapus

    public function test_extending_due_date_of_late_loan_makes_it_active_again(): void
    {
        $loan = $this->loan([$this->book(3)], [
            'tanggal_pinjam' => now()->subDays(10)->toDateString(),
            'tanggal_kembali' => now()->subDays(3)->toDateString(),
        ]);
        Loan::syncOverdue();
        $this->assertSame('terlambat', $loan->fresh()->status);

        $this->actingAs($this->petugas)
            ->put("/loans/{$loan->id}", ['tanggal_kembali' => now()->addDays(5)->toDateString()])
            ->assertRedirect(route('loans.index'));

        $this->assertSame('dipinjam', $loan->fresh()->status);
    }

    public function test_returned_loan_cannot_be_edited(): void
    {
        $loan = $this->loan([$this->book(3)]);
        $loan->markReturned();

        $this->actingAs($this->petugas)
            ->get("/loans/{$loan->id}/edit")
            ->assertRedirect(route('loans.index'))
            ->assertSessionHas('error');

        $this->actingAs($this->petugas)
            ->put("/loans/{$loan->id}", ['tanggal_kembali' => now()->addDays(9)->toDateString()])
            ->assertSessionHas('error');
    }

    public function test_deleting_active_loan_restores_stock(): void
    {
        $book = $this->book(3);
        $loan = $this->loan([$book]);
        $this->assertSame(2, $book->fresh()->stok);

        $this->actingAs($this->petugas)->delete("/loans/{$loan->id}")->assertRedirect(route('loans.index'));

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
        $this->assertSame(3, $book->fresh()->stok);
    }

    public function test_deleting_returned_loan_does_not_change_stock(): void
    {
        $book = $this->book(3);
        $loan = $this->loan([$book]);
        $loan->markReturned();
        $this->assertSame(3, $book->fresh()->stok);

        $this->actingAs($this->petugas)->delete("/loans/{$loan->id}");

        $this->assertSame(3, $book->fresh()->stok);
    }

    public function test_book_and_member_with_loan_history_cannot_be_deleted(): void
    {
        $book = $this->book(3);
        $this->loan([$book]);

        $this->actingAs($this->petugas)
            ->delete("/books/{$book->id}")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('books', ['id' => $book->id]);

        $this->actingAs($this->petugas)
            ->delete("/members/{$this->member->id}")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('members', ['id' => $this->member->id]);
    }

    // ------------------------------------------------------------------------ API

    public function test_api_borrow_and_return_keep_stock_in_sync(): void
    {
        $book = $this->book(2);

        $response = $this->postJson('/api/loans', $this->payload([$book], ['user_id' => $this->petugas->id]))
            ->assertCreated();
        $loanId = $response->json('data.id');
        $this->assertSame(1, $book->fresh()->stok);

        $this->putJson("/api/loans/{$loanId}/kembalikan")->assertOk()->assertJsonPath('data.status', 'dikembalikan');
        $this->assertSame(2, $book->fresh()->stok);

        $this->putJson("/api/loans/{$loanId}/kembalikan")->assertStatus(422);
        $this->assertSame(2, $book->fresh()->stok);
    }

    public function test_api_rejects_out_of_stock_book(): void
    {
        $habis = $this->book(0);

        $this->postJson('/api/loans', $this->payload([$habis], ['user_id' => $this->petugas->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('book_ids');

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_api_stats_count_late_loans_as_active(): void
    {
        $this->loan([$this->book(3)]);
        $this->loan([$this->book(3)], [
            'tanggal_pinjam' => now()->subDays(10)->toDateString(),
            'tanggal_kembali' => now()->subDays(3)->toDateString(),
        ]);
        $returned = $this->loan([$this->book(3)]);
        $returned->markReturned();

        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJson([
                'peminjaman_aktif' => 2,       // 1 dipinjam + 1 terlambat
                'peminjaman_terlambat' => 1,
            ]);
    }
}
