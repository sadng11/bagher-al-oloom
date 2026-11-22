# Search Result Deep-Link Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Link each search result to its exact book-content record, scroll the book reader to that record, and highlight the phrase or word that caused the match.

**Architecture:** `SearchService` tags each deduplicated hit with its highest-priority query. The search Blade view builds a deep link from `book_id`, hit `id`, and that query. `LibraryController` validates and exposes the target, while `BookContent.vue` performs normalized DOM-safe highlighting and scrolling after rendering.

**Tech Stack:** PHP 8, Laravel 9, Meilisearch, Blade, Vue 3, Vite, PHPUnit 9, Playwright.

## Global Constraints

- Complete-phrase matches retain priority over word-only matches.
- A deep-link target must belong to the requested book.
- Persian/Arabic letter variants and diacritics must not prevent highlighting.
- Database content is never mutated to add highlight markup.
- Normal book URLs without target parameters retain existing behavior.

---

### Task 1: Preserve the winning query on every hit

**Files:**
- Modify: `app/Services/SearchService.php`
- Modify: `tests/Unit/SearchServiceTest.php`

**Interfaces:**
- Produces: hit field `_matchedQuery: string`

- [ ] Add a failing assertion that merged IDs `10`, `11`, and `12` have
  `_matchedQuery` values `بقیة الله`, `بقیة الله`, and `بقیة`.
- [ ] Run `docker exec app-begher php artisan test tests/Unit/SearchServiceTest.php`
  and verify the assertion fails because the field is absent.
- [ ] During ordered result merging, attach the unquoted query term belonging
  to the current result set before storing a previously unseen hit.
- [ ] Re-run the unit test and verify it passes.

### Task 2: Render deep links from search cards

**Files:**
- Modify: `resources/views/site/search/results.blade.php`
- Modify: `tests/Feature/SearchRouteTest.php`

**Interfaces:**
- Produces: `/library/book/{book_id}?p={id}&q={_matchedQuery}`

- [ ] Update the route-test fixture with `book_id` and `_matchedQuery`, then
  assert the response contains the encoded deep-link URL.
- [ ] Run the feature test and verify it fails before the link exists.
- [ ] Wrap each result card in an anchor generated with `route('library.book')`,
  passing `p` and `q`, while keeping result markup and highlighting visible.
- [ ] Re-run the feature test and verify it passes.

### Task 3: Validate and load the book target

**Files:**
- Modify: `app/Http/Controllers/LibraryController.php`
- Create: `tests/Feature/LibraryBookDeepLinkTest.php`
- Modify: `resources/views/site/library/book.blade.php`

**Interfaces:**
- Consumes: request parameters `p: int`, `q: string`
- Produces Vue props: `target-id` and `target-query`

- [ ] Create feature tests with database records proving a valid target is
  passed to the view and a target from another book is ignored.
- [ ] Run the tests and verify RED.
- [ ] Replace direct `$inputs['p']` access with validated request values.
  Query the target through the requested book relation. Load ordered content
  through the page containing that target and retain the paginator path.
- [ ] Pass nullable target values to the Blade view and then to
  `<book-content>` using JSON-safe bindings.
- [ ] Re-run the controller tests and verify GREEN.

### Task 4: DOM-safe normalized highlighting and scrolling

**Files:**
- Create: `resources/js/searchTarget.js`
- Create: `resources/js/searchTarget.test.js`
- Modify: `resources/js/components/BookContent.vue`
- Modify: `package.json`

**Interfaces:**
- Produces:
  `normalizeSearchText(string): string`
  and `highlightSearchTarget(Element, string): number`

- [ ] Add Vitest and jsdom tests for exact text, Arabic diacritics, `ی/ي`,
  `ک/ك`, `ه/ة`, and preservation of nested HTML.
- [ ] Run `npm test -- --run` and verify RED.
- [ ] Implement normalization with an index map back to original text offsets.
  Walk text nodes with `TreeWalker`, replace matched ranges with
  `<mark class="search-target-match">`, and return the match count.
- [ ] In `BookContent.vue`, accept `targetId` and `targetQuery`, wait for
  `nextTick`, locate `#p_{targetId}`, highlight it, add `active_search`, and
  call `scrollIntoView({behavior: 'smooth', block: 'center'})`.
- [ ] Re-run Vitest and verify GREEN.
- [ ] Run `npm run build`.

### Task 5: Full and browser verification

**Files:**
- No additional production files

**Interfaces:**
- Consumes the live search and book routes on port 7000.

- [ ] Run `docker exec app-begher php artisan test` and verify zero failures.
- [ ] Open the live search URL in Playwright and follow a result card.
- [ ] Verify the URL contains the correct `p` and `q`, the target element has
  `active_search`, at least one `search-target-match` is visible, and no
  JavaScript console error occurs.
