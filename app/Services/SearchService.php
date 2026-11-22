<?php

namespace App\Services;

use Meilisearch\Client;
use Meilisearch\Contracts\SearchQuery;

class SearchService
{
    private Client $client;
    private string $index;
    private array $searchOptions;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );
        $this->index = 'book_contents';
        $this->searchOptions = [
            'attributesToRetrieve' => ['id', 'book_id', 'book_title', 'content'],
            'attributesToHighlight' => ['content', 'book_title'],
            'highlightPreTag' => '<mark class="search-result">',
            'highlightPostTag' => '</mark>',
            'attributesToCrop' => ['content'],
            'cropLength' => 30,
            'cropMarker' => '…',
        ];
    }

    public function search(
        string $query,
        int $page,
        int $perPage,
        ?array $allowedBookIds = null
    ): array
    {
        $terms = $this->queryTerms($query);

        if ($terms === [] || $allowedBookIds === []) {
            return [
                'hits' => [],
                'nbHits' => 0,
                'estimatedTotalHits' => 0,
            ];
        }

        if ($allowedBookIds !== null) {
            $allowedBookIds = array_values(array_unique(array_map(
                static fn ($bookId): int => (int) $bookId,
                $allowedBookIds
            )));
        }

        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $fetchLimit = ($page * $perPage) + 1;
        $queries = array_map(
            fn (string $term) => $this->makeSearchQuery(
                $term,
                $fetchLimit,
                $allowedBookIds
            ),
            $terms
        );

        $response = $this->client->multiSearch($queries);
        $mergedHits = [];
        $seenIds = [];
        $hasUnfetchedHits = false;

        foreach ($response['results'] ?? [] as $resultIndex => $result) {
            $hasUnfetchedHits = $hasUnfetchedHits
                || ($result['estimatedTotalHits'] ?? 0) > $fetchLimit;

            foreach ($result['hits'] ?? [] as $hit) {
                $id = (string) $hit['id'];

                if (isset($seenIds[$id])) {
                    continue;
                }

                $seenIds[$id] = true;
                $hit['_matchedQuery'] = $terms[$resultIndex];
                $mergedHits[] = $hit;
            }
        }

        $knownTotal = count($mergedHits) + ($hasUnfetchedHits ? 1 : 0);
        $hits = array_slice(
            $mergedHits,
            ($page - 1) * $perPage,
            $perPage
        );

        return [
            'hits' => $hits,
            'nbHits' => $knownTotal,
            'estimatedTotalHits' => $knownTotal,
        ];
    }

    private function queryTerms(string $query): array
    {
        $words = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || $words === []) {
            return [];
        }

        $phrase = implode(' ', $words);

        return array_values(array_unique([$phrase, ...$words]));
    }

    private function makeSearchQuery(
        string $term,
        int $limit,
        ?array $allowedBookIds
    ): SearchQuery
    {
        $query = (new SearchQuery())
            ->setIndexUid($this->index)
            ->setQuery('"' . str_replace('"', '\\"', $term) . '"')
            ->setAttributesToRetrieve($this->searchOptions['attributesToRetrieve'])
            ->setAttributesToHighlight($this->searchOptions['attributesToHighlight'])
            ->setHighlightPreTag($this->searchOptions['highlightPreTag'])
            ->setHighlightPostTag($this->searchOptions['highlightPostTag'])
            ->setAttributesToCrop($this->searchOptions['attributesToCrop'])
            ->setCropLength($this->searchOptions['cropLength'])
            ->setCropMarker($this->searchOptions['cropMarker'])
            ->setMatchingStrategy('all')
            ->setOffset(0)
            ->setLimit($limit);

        if ($allowedBookIds !== null) {
            $query->setFilter([
                'book_id IN [' . implode(', ', $allowedBookIds) . ']',
            ]);
        }

        return $query;
    }
}
