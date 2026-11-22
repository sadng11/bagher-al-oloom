<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryBookDeepLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_search_target_is_passed_to_book_reader(): void
    {
        [$bookId, $targetId] = $this->seedBooks();

        $response = $this->get(route('library.book', [
            'id' => $bookId,
            'p' => $targetId,
            'q' => 'بقیه الله',
        ]));

        $response->assertOk()
            ->assertViewHas('targetId', $targetId)
            ->assertViewHas('targetQuery', 'بقیه الله')
            ->assertViewHas('initialPage', 1)
            ->assertSee(':target-id="' . $targetId . '"', false);
    }

    public function test_target_from_another_book_is_ignored(): void
    {
        [$bookId, , $otherTargetId] = $this->seedBooks();

        $response = $this->get(route('library.book', [
            'id' => $bookId,
            'p' => $otherTargetId,
            'q' => 'عبارت',
        ]));

        $response->assertOk()
            ->assertViewHas('targetId', null)
            ->assertViewHas('targetQuery', null)
            ->assertViewHas('initialPage', 1);
    }

    private function seedBooks(): array
    {
        $now = now();
        $writerId = DB::table('writers')->insertGetId([
            'name' => 'نویسنده',
            'nickname' => 'آزمایشی',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherBookId = DB::table('books')->insertGetId([
            'writer_id' => $writerId,
            'title' => 'کتاب دیگر',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherTargetId = DB::table('book_contents')->insertGetId([
            'book_id' => $otherBookId,
            'content' => '<p>عبارت</p>',
        ]);
        $bookId = DB::table('books')->insertGetId([
            'writer_id' => $writerId,
            'title' => 'کتاب هدف',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $targetId = DB::table('book_contents')->insertGetId([
            'book_id' => $bookId,
            'content' => '<p>حضرت بقیّة اللّه</p>',
        ]);

        return [$bookId, $targetId, $otherTargetId];
    }
}
