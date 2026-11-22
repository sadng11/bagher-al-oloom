<?php

namespace Tests\Feature;

use App\Services\SearchService;
use Mockery;
use Tests\TestCase;

class SearchRouteTest extends TestCase
{
    public function test_search_route_delegates_to_search_service(): void
    {
        $service = Mockery::mock(SearchService::class);
        $service->shouldReceive('search')
            ->once()
            ->with('بقیة الله', 1, 20)
            ->andReturn([
                'hits' => [[
                    'id' => 10,
                    'book_id' => 30,
                    'book_title' => 'کتاب',
                    'content' => 'بقیة الله',
                    '_matchedQuery' => 'بقیة الله',
                    '_formatted' => [
                        'book_title' => 'کتاب',
                        'content' => '<mark class="search-result">بقیة الله</mark>',
                    ],
                ]],
                'nbHits' => 1,
                'estimatedTotalHits' => 1,
            ]);
        $this->app->instance(SearchService::class, $service);

        $response = $this->get('/search?q=' . urlencode('بقیة الله'));

        $response->assertOk()
            ->assertViewIs('site.search.results')
            ->assertViewHas('search', 'بقیة الله')
            ->assertViewHas('page', 1)
            ->assertViewHas('perPage', 20)
            ->assertViewHas('results', fn (array $results) => $results['hits'][0]['id'] === 10)
            ->assertSee(route('library.book', [
                'id' => 30,
                'p' => 10,
                'q' => 'بقیة الله',
            ]))
            ->assertSee('بقیة الله');
    }
}
