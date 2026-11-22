# Book Modal Meilisearch Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the in-book modal's database scan with the existing exact Meilisearch service, return at most 100 priority-ordered results, and deep-link each highlighted result to its exact book-content row.

**Architecture:** Extend `SearchService` with an optional book-ID scope that is applied to every phrase/word query. Keep the HTTP controller responsible for validation, resolving a writer to book IDs, and grouping the service's ordered hits. Keep the Vue component responsible only for modal state, rendering trusted Meilisearch formatted fields, and building encoded deep links.

**Tech Stack:** Laravel, Eloquent, Laravel Scout configuration, Meilisearch PHP SDK, Vue 3, Axios, PHPUnit, Vitest/jsdom.

## Global Constraints

- Return at most 100 hits.
- Preserve phrase-first then individual-word priority and deduplicate hits.
- Keep typo tolerance disabled.
- Use in-memory SQLite and Scout `null` for PHP tests; mock the Meilisearch client/service.
- Do not manually flush or rebuild application data.
- Link format is `/library/book/{book_id}?p={content_id}&q={matched_query}` with URL-encoded query values.

---

### Task 1: Book-scoped Meilisearch queries

**Files:**
- Modify: `tests/Unit/SearchServiceTest.php`
- Modify: `app/Services/SearchService.php`
- Modify: `tests/Unit/MeilisearchSettingsTest.php`
- Modify: `config/scout.php`

**Interfaces:**
- Consumes: existing `SearchService::search(string $query, int $page, int $perPage)`
- Produces: `SearchService::search(string $query, int $page, int $perPage, ?array $allowedBookIds = null): array`

- [ ] **Step 1: Write failing unit tests**

Add tests that call `search('الله', 1, 100, [47, 30, 47])` and assert every serialized `SearchQuery` has filter `book_id IN [47, 30]`; call it with `[]` and assert the empty result contract without `multiSearch`; assert `filterableAttributes` equals `['book_id']`.

- [ ] **Step 2: Run tests to verify RED**

Run: `php artisan test tests/Unit/SearchServiceTest.php tests/Unit/MeilisearchSettingsTest.php`

Expected: failures because the fourth argument/filter and filterable setting do not exist.

- [ ] **Step 3: Implement the minimal service and configuration**

Add the optional argument, normalize IDs to unique integers, return the existing empty response for an explicit empty list, pass IDs into `makeSearchQuery()`, and call:

```php
->setFilter('book_id IN [' . implode(', ', $allowedBookIds) . ']')
```

only when the scope is non-null. Add:

```php
'filterableAttributes' => ['book_id'],
```

to the `book_contents` settings.

- [ ] **Step 4: Run tests to verify GREEN**

Run: `php artisan test tests/Unit/SearchServiceTest.php tests/Unit/MeilisearchSettingsTest.php`

Expected: all selected tests pass.

### Task 2: Modal search API

**Files:**
- Create: `tests/Feature/BookSearchTest.php`
- Modify: `app/Http/Controllers/BookController.php`

**Interfaces:**
- Consumes: `SearchService::search($keyword, 1, 100, $allowedBookIds)`
- Produces: JSON groups shaped as `[{book: {id, title}, search: [{id, book_id, content, matched_query}]}]`

- [ ] **Step 1: Write failing feature tests**

Create tests proving:

```php
$this->postJson('/book/search', ['keyword' => 'ال'])->assertUnprocessable();
$this->postJson('/book/search', ['keyword' => 'الله', 'writerId' => 'bad'])->assertUnprocessable();
```

For all writers, bind a mocked `SearchService`, return ordered hits with `_formatted.book_title`, `_formatted.content`, and `_matchedQuery`, then assert the fourth service argument is `null`, only the first 100 hits are grouped, group order follows first hit occurrence, and formatted fields appear in JSON. For a selected writer, create books in in-memory SQLite and assert their IDs are passed as the fourth argument; a writer with no books must pass `[]`.

- [ ] **Step 2: Run the feature test to verify RED**

Run: `php artisan test tests/Feature/BookSearchTest.php`

Expected: failures because the controller still scans `book_contents` and does not use `SearchService`.

- [ ] **Step 3: Implement the controller**

Inject `SearchService`, validate `keyword` and `writerId`, resolve selected-writer IDs via `Book::where('writer_id', $writerId)->pluck('id')`, call the service with per-page `100`, slice defensively to 100, and group hits in encounter order. Map `_formatted.book_title ?? book_title`, `_formatted.content ?? content`, and `_matchedQuery` to the response contract.

- [ ] **Step 4: Run the feature test to verify GREEN**

Run: `php artisan test tests/Feature/BookSearchTest.php`

Expected: all modal API tests pass without contacting the real database or Meilisearch.

### Task 3: Vue highlighted results and exact links

**Files:**
- Create: `resources/js/components/SearchContent.test.js`
- Modify: `resources/js/components/SearchContent.vue`
- Modify: `vite.config.js` only if the existing test include pattern excludes component tests

**Interfaces:**
- Consumes: modal API response from Task 2
- Produces: highlighted title/snippet HTML and encoded deep links without `count`

- [ ] **Step 1: Write a failing component test**

Mount the component with a stubbed `window.axios.post()` response containing highlighted title/content and `matched_query: 'بقیه الله'`. Submit the form and assert rendered HTML includes both `<mark>` values and that the link pathname/query are:

```text
/library/book/47
p=15353
q=بقیه الله
```

Also assert `count` is absent and an empty response renders an explicit no-results message.

- [ ] **Step 2: Run the component test to verify RED**

Run: `npm test -- --run resources/js/components/SearchContent.test.js`

Expected: failure because the title uses interpolation, the URL uses the raw keyword and `count`, and no empty state exists.

- [ ] **Step 3: Implement the Vue behavior**

Render the API-provided title with `v-html`, render each formatted snippet with `v-html`, build the link with `URLSearchParams({p: line.id, q: line.matched_query})`, remove `count`, make validation feedback local to the modal, and show loading/error/empty states.

- [ ] **Step 4: Run the component test to verify GREEN**

Run: `npm test -- --run resources/js/components/SearchContent.test.js`

Expected: all component tests pass.

### Task 4: End-to-end verification

**Files:**
- Verify only; no expected production edits

**Interfaces:**
- Consumes: Tasks 1-3
- Produces: fresh evidence that backend, frontend, build, and test isolation remain valid

- [ ] **Step 1: Apply Meilisearch settings**

Run: `php artisan meilisearch:configure`

Expected: `book_contents` settings update succeeds without flushing or reimporting documents.

- [ ] **Step 2: Run the complete PHP suite**

Run: `php artisan test`

Expected: zero failures using in-memory SQLite.

- [ ] **Step 3: Run the complete JavaScript suite**

Run: `npm test -- --run`

Expected: zero failures.

- [ ] **Step 4: Build production assets**

Run: `npm run build`

Expected: Vite exits successfully.

- [ ] **Step 5: Verify the live modal**

Open `/library/book/47`, search `الله`, confirm no more than 100 results, highlighted title/snippet rendering, and click a result to confirm the reader opens the matching `p` row, scrolls to it, and highlights the matched query.
