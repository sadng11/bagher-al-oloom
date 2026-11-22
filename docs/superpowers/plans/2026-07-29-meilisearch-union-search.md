# Meilisearch Union Search Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Return the priority-ordered, deduplicated union of the complete search phrase and each of its exact words with one Meilisearch network request.

**Architecture:** `SearchService` will create `SearchQuery` objects for the normalized phrase and its unique words, execute them with `Client::multiSearch()`, merge results in query priority order, and slice the merged list for the requested page. The `/search` route will delegate to this service and retain its existing highlighting cleanup and view contract.

**Tech Stack:** PHP 8, Laravel 9, Laravel Scout, meilisearch-php 1.16, PHPUnit 9, Mockery.

## Global Constraints

- Typo tolerance remains disabled for `book_contents`.
- Complete-phrase results precede individual-word results.
- Duplicate documents are removed by indexed `id`.
- All component queries are sent in one multi-search request.
- Existing crop and highlight markup remains unchanged.

---

### Task 1: Priority-preserving multi-search service

**Files:**
- Modify: `app/Services/SearchService.php`
- Create: `tests/Unit/SearchServiceTest.php`

**Interfaces:**
- Consumes: `Meilisearch\Client::multiSearch(array $queries): array`
- Produces: `SearchService::search(string $query, int $page, int $perPage): array`
- Returns: `['hits' => array, 'nbHits' => int, 'estimatedTotalHits' => int]`

- [ ] **Step 1: Write failing service tests**

Create tests that inject a mocked `Meilisearch\Client`, capture the submitted
`SearchQuery` objects through `toArray()`, and assert:

```php
$this->assertSame(
    ['بقیة الله', 'بقیة', 'الله'],
    array_column($serializedQueries, 'q')
);
$this->assertSame([10, 11, 12], array_column($result['hits'], 'id'));
```

The mock response must put IDs `10` and `11` in the phrase result, IDs `10`
and `12` in the first word result, and ID `11` in the second word result.
Add separate cases for repeated/extra whitespace, page slicing, and an empty
query that expects `multiSearch()` zero times.

- [ ] **Step 2: Run tests to verify RED**

Run:

```bash
docker exec app-begher php artisan test tests/Unit/SearchServiceTest.php
```

Expected: FAIL because the current service executes sequential `rawSearch`
requests and cannot receive an injected client.

- [ ] **Step 3: Implement the minimal multi-search service**

Change construction to:

```php
public function __construct(?Client $client = null)
{
    $this->client = $client ?? new Client(
        config('scout.meilisearch.host'),
        config('scout.meilisearch.key')
    );
}
```

Normalize the input with `preg_split('/\s+/u', trim($query))`, create the
ordered unique query list with the full normalized phrase first, and convert
each item to `Meilisearch\Contracts\SearchQuery`. Each query uses offset `0`
and limit `($page * $perPage) + 1`, plus the existing retrieve/highlight
options and the route's crop options.

Call `multiSearch()` once. Merge its `results` arrays in order with a keyed
`$seenIds` lookup, then apply:

```php
$hits = array_slice($mergedHits, ($page - 1) * $perPage, $perPage);
```

Return `nbHits` and `estimatedTotalHits` using the merged prefix size, adding
one when any component reports more results than were fetched so the view can
offer the next page without loading the entire index.

- [ ] **Step 4: Run tests to verify GREEN**

Run:

```bash
docker exec app-begher php artisan test tests/Unit/SearchServiceTest.php
```

Expected: all SearchService tests PASS.

### Task 2: Route integration and rendering contract

**Files:**
- Modify: `routes/web.php`
- Create: `tests/Feature/SearchRouteTest.php`

**Interfaces:**
- Consumes: `SearchService::search(string $query, int $page, int $perPage): array`
- Produces: the existing `site.search.results` view variables `results`,
  `search`, `page`, and `perPage`

- [ ] **Step 1: Write a failing route test**

Bind a `Mockery::mock(SearchService::class)` in the service container, expect:

```php
$service->shouldReceive('search')
    ->once()
    ->with('بقیة الله', 1, 20)
    ->andReturn([
        'hits' => [[
            'id' => 10,
            'book_title' => 'کتاب',
            'content' => 'بقیة الله',
            '_formatted' => [
                'book_title' => 'کتاب',
                'content' => '<mark class="search-result">بقیة الله</mark>',
            ],
        ]],
        'nbHits' => 1,
        'estimatedTotalHits' => 1,
    ]);
```

Request `/search?q=بقیة%20الله` and assert status `200`, the selected view,
the four view variables, and rendered result content.

- [ ] **Step 2: Run the route test to verify RED**

Run:

```bash
docker exec app-begher php artisan test tests/Feature/SearchRouteTest.php
```

Expected: FAIL because the route currently calls `BookContent::search()`
directly.

- [ ] **Step 3: Delegate the route to SearchService**

Resolve the service in the route closure:

```php
$results = app(SearchService::class)->search($search ?? '', $page, $perPage);
```

Remove the direct Scout callback and unused `BookContent` import. Preserve the
existing `SearchHighlighter::clean()` loop and view call.

- [ ] **Step 4: Run focused and full verification**

Run:

```bash
docker exec app-begher php artisan test tests/Unit/SearchServiceTest.php tests/Feature/SearchRouteTest.php tests/Unit/MeilisearchSettingsTest.php
docker exec app-begher php artisan test
```

Expected: focused tests and full suite PASS with no failures.

### Task 3: Live behavior check

**Files:**
- No production files

**Interfaces:**
- Consumes: the configured live `book_contents` index
- Produces: verification evidence for query `بقیة الله`

- [ ] **Step 1: Run the real query through SearchService**

Run a container-side Artisan/Tinker check that calls:

```php
app(\App\Services\SearchService::class)->search('بقیة الله', 1, 20)
```

Extract the marked tokens from `_formatted.content`.

- [ ] **Step 2: Verify the acceptance criteria**

Confirm that phrase matches occur first, IDs are unique, and highlights do not
contain `بنیّةٍ`, `بقید`, `بقوة`, or `البقرة` as substitutes for `بقیة`.
