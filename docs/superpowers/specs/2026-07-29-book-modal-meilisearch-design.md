# Book Modal Meilisearch Design

## Goal

Make the in-book search modal use the same exact, priority-ordered
Meilisearch behavior as `/search`, render book titles and highlighted
snippets, and deep-link every result to the exact highlighted location in the
book reader.

## Index settings

The `book_contents` index adds `book_id` to `filterableAttributes`. Typo
tolerance remains disabled. The existing Meilisearch configuration command
applies both settings without rebuilding application data manually.

## Search service

`SearchService::search()` accepts an optional list of allowed book IDs. Every
query in the single multi-search request receives the same `book_id` filter.
Without an allowed-ID list, behavior remains unchanged. An explicitly empty
allowed-ID list returns no hits without contacting Meilisearch.

The service retains phrase-first and individual-word priority, exact quoted
queries, deduplication, `_matchedQuery`, highlighted title/content, and
cropped snippets.

## Modal API

`POST /book/search` validates:

- `keyword`: required string, minimum 3 characters
- `writerId`: optional integer or `-1`

For a selected writer, the controller resolves that writer's book IDs and
passes them to `SearchService`. For all writers, no filter is applied. The
search returns at most 100 hits and groups them by book while preserving hit
priority.

The JSON response is:

```json
[
  {
    "book": {"id": 30, "title": "Book title"},
    "search": [
      {
        "id": 6668,
        "book_id": 30,
        "content": "highlighted cropped HTML",
        "matched_query": "بقیه الله"
      }
    ]
  }
]
```

## Vue modal

`SearchContent.vue` keeps the writer selector and form. It renders the
highlighted book title and snippet returned by the API. Each result links to:

```text
/library/book/{book_id}?p={content_id}&q={matched_query}
```

The obsolete `count` parameter is removed. Loading, empty-result, and
validation feedback remain inside the modal.

## Safety and testing

- PHP tests are forced to in-memory SQLite and Scout `null` unless a mocked
  Meilisearch client is injected.
- Unit tests verify filter construction and empty filters.
- Feature tests mock `SearchService` and verify validation, writer filtering,
  grouping, highlighted fields, and response limits.
- Component/browser verification checks rendered links and the existing
  deep-link highlight behavior.
