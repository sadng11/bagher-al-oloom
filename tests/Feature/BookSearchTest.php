<?php

namespace Tests\Feature;

use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_validates_keyword_and_writer_id(): void
    {
        $this->postJson('/book/search', [
            'keyword' => 'ال',
            'writerId' => -1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('keyword');

        $this->postJson('/book/search', [
            'keyword' => 'الله',
            'writerId' => 'bad',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('writerId');
    }

    public function test_all_writer_search_groups_priority_ordered_formatted_hits_and_limits_output(): void
    {
        $hits = [
            [
                'id' => 15353,
                'book_id' => 47,
                'book_title' => 'کتاب اول',
                'content' => 'متن اول',
                '_formatted' => [
                    'book_title' => 'کتاب <mark class="search-result">اول</mark>',
                    'content' => 'متن <mark class="search-result">الله</mark>',
                ],
                '_matchedQuery' => 'الله',
            ],
            [
                'id' => 400,
                'book_id' => 30,
                'book_title' => 'کتاب دوم',
                'content' => 'متن دوم',
                '_formatted' => [
                    'book_title' => 'کتاب دوم',
                    'content' => '<mark class="search-result">بقیه الله</mark>',
                ],
                '_matchedQuery' => 'بقیه الله',
            ],
        ];

        for ($id = 3; $id <= 101; $id++) {
            $hits[] = [
                'id' => 20000 + $id,
                'book_id' => 47,
                'book_title' => 'کتاب اول',
                'content' => 'متن',
                '_formatted' => [
                    'book_title' => 'کتاب اول',
                    'content' => 'متن',
                ],
                '_matchedQuery' => 'الله',
            ];
        }

        $service = Mockery::mock(SearchService::class);
        $service->shouldReceive('search')
            ->once()
            ->with('بقیه الله', 1, 100, null)
            ->andReturn([
                'hits' => $hits,
                'nbHits' => 101,
                'estimatedTotalHits' => 101,
            ]);
        $this->app->instance(SearchService::class, $service);

        $response = $this->postJson('/book/search', [
            'keyword' => 'بقیه الله',
            'writerId' => -1,
        ])->assertOk();

        $response->assertJsonPath('0.book.id', 47)
            ->assertJsonPath(
                '0.book.title',
                'کتاب <mark class="search-result">اول</mark>'
            )
            ->assertJsonPath(
                '0.search.0.content',
                'متن <mark class="search-result">الله</mark>'
            )
            ->assertJsonPath('0.search.0.matched_query', 'الله')
            ->assertJsonPath('1.book.id', 30)
            ->assertJsonCount(2);

        $this->assertCount(
            100,
            collect($response->json())->pluck('search')->flatten(1)
        );
    }

    public function test_selected_writer_books_are_passed_to_search_service(): void
    {
        $now = now();
        $writerId = DB::table('writers')->insertGetId([
            'name' => 'نویسنده',
            'nickname' => 'آزمایشی',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $bookIds = [
            DB::table('books')->insertGetId([
                'writer_id' => $writerId,
                'title' => 'کتاب یک',
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            DB::table('books')->insertGetId([
                'writer_id' => $writerId,
                'title' => 'کتاب دو',
                'created_at' => $now,
                'updated_at' => $now,
            ]),
        ];

        $service = Mockery::mock(SearchService::class);
        $service->shouldReceive('search')
            ->once()
            ->withArgs(function (
                string $keyword,
                int $page,
                int $perPage,
                ?array $allowedBookIds
            ) use ($bookIds): bool {
                $this->assertSame('الله', $keyword);
                $this->assertSame(1, $page);
                $this->assertSame(100, $perPage);
                $this->assertSame($bookIds, $allowedBookIds);

                return true;
            })
            ->andReturn([
                'hits' => [],
                'nbHits' => 0,
                'estimatedTotalHits' => 0,
            ]);
        $this->app->instance(SearchService::class, $service);

        $this->postJson('/book/search', [
            'keyword' => 'الله',
            'writerId' => $writerId,
        ])->assertOk()->assertExactJson([]);
    }

    public function test_writer_without_books_uses_an_empty_book_scope(): void
    {
        $service = Mockery::mock(SearchService::class);
        $service->shouldReceive('search')
            ->once()
            ->with('الله', 1, 100, [])
            ->andReturn([
                'hits' => [],
                'nbHits' => 0,
                'estimatedTotalHits' => 0,
            ]);
        $this->app->instance(SearchService::class, $service);

        $this->postJson('/book/search', [
            'keyword' => 'الله',
            'writerId' => 999,
        ])->assertOk()->assertExactJson([]);
    }
}
