<?php

namespace Tests\Unit;

use App\Services\SearchService;
use Meilisearch\Client;
use Mockery;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    public function test_it_runs_phrase_and_word_queries_in_one_request_and_keeps_priority(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('multiSearch')
            ->once()
            ->withArgs(function (array $queries): bool {
                $serialized = array_map(fn ($query) => $query->toArray(), $queries);

                $this->assertSame(
                    ['"بقیة الله"', '"بقیة"', '"الله"'],
                    array_column($serialized, 'q')
                );
                $this->assertSame([21, 21, 21], array_column($serialized, 'limit'));
                $this->assertSame([0, 0, 0], array_column($serialized, 'offset'));

                return true;
            })
            ->andReturn([
                'results' => [
                    ['hits' => [['id' => 10], ['id' => 11]], 'estimatedTotalHits' => 2],
                    ['hits' => [['id' => 10], ['id' => 12]], 'estimatedTotalHits' => 2],
                    ['hits' => [['id' => 11]], 'estimatedTotalHits' => 1],
                ],
            ]);

        $result = (new SearchService($client))->search('بقیة الله', 1, 20);

        $this->assertSame([10, 11, 12], array_column($result['hits'], 'id'));
        $this->assertSame(
            ['بقیة الله', 'بقیة الله', 'بقیة'],
            array_column($result['hits'], '_matchedQuery')
        );
        $this->assertSame(3, $result['nbHits']);
    }

    public function test_it_normalizes_whitespace_and_removes_repeated_terms(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('multiSearch')
            ->once()
            ->withArgs(function (array $queries): bool {
                $serialized = array_map(fn ($query) => $query->toArray(), $queries);

                $this->assertSame(
                    ['"بقیة الله بقیة"', '"بقیة"', '"الله"'],
                    array_column($serialized, 'q')
                );

                return true;
            })
            ->andReturn(['results' => [
                ['hits' => [], 'estimatedTotalHits' => 0],
                ['hits' => [], 'estimatedTotalHits' => 0],
                ['hits' => [], 'estimatedTotalHits' => 0],
            ]]);

        (new SearchService($client))->search("  بقیة \t الله   بقیة  ", 1, 20);
    }

    public function test_it_paginates_after_merging_and_deduplicating(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('multiSearch')
            ->once()
            ->andReturn([
                'results' => [
                    ['hits' => [['id' => 1], ['id' => 2]], 'estimatedTotalHits' => 2],
                    ['hits' => [['id' => 2], ['id' => 3], ['id' => 4]], 'estimatedTotalHits' => 3],
                    ['hits' => [['id' => 4], ['id' => 5]], 'estimatedTotalHits' => 2],
                ],
            ]);

        $result = (new SearchService($client))->search('بقیة الله', 2, 2);

        $this->assertSame([3, 4], array_column($result['hits'], 'id'));
        $this->assertSame(5, $result['nbHits']);
    }

    public function test_empty_query_returns_no_hits_without_contacting_meilisearch(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('multiSearch');

        $result = (new SearchService($client))->search(" \t ", 1, 20);

        $this->assertSame([
            'hits' => [],
            'nbHits' => 0,
            'estimatedTotalHits' => 0,
        ], $result);
    }

    public function test_it_applies_the_allowed_book_ids_to_every_query(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('multiSearch')
            ->once()
            ->withArgs(function (array $queries): bool {
                $serialized = array_map(fn ($query) => $query->toArray(), $queries);

                $this->assertSame(
                    [
                        ['book_id IN [47, 30]'],
                        ['book_id IN [47, 30]'],
                        ['book_id IN [47, 30]'],
                    ],
                    array_column($serialized, 'filter')
                );

                return true;
            })
            ->andReturn([
                'results' => [
                    ['hits' => [], 'estimatedTotalHits' => 0],
                    ['hits' => [], 'estimatedTotalHits' => 0],
                ],
            ]);

        (new SearchService($client))->search('بقیه الله', 1, 100, [47, 30, 47]);
    }

    public function test_empty_allowed_book_ids_return_no_hits_without_contacting_meilisearch(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('multiSearch');

        $result = (new SearchService($client))->search('الله', 1, 100, []);

        $this->assertSame([
            'hits' => [],
            'nbHits' => 0,
            'estimatedTotalHits' => 0,
        ], $result);
    }
}
