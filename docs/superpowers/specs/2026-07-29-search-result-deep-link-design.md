# Search Result Deep-Link Design

## Goal

Make every search-result card link to its exact `BookContent` record in the
corresponding book, scroll the reader to that record, and highlight the exact
phrase or word responsible for including the result.

## Search-result contract

`SearchService` already merges queries in this priority order:

1. The complete phrase
2. Each unique individual word

When a hit is first accepted into the deduplicated result set, the service
adds `_matchedQuery` containing the query responsible for that hit. Because
later duplicates are skipped, this preserves the highest-priority match.

The result view links each card to:

```text
/library/book/{book_id}?p={book_content_id}&q={matched_query}
```

The full card is clickable. `book_id` selects the book, `p` identifies the
exact `BookContent` record, and `q` identifies the text to highlight.

## Reader behavior

`LibraryController::book()` validates that `p` belongs to the requested book,
then prepares enough ordered book content for the Vue reader to render the
target record. Direct book links without `p` and `q` retain their existing
behavior.

`BookContent.vue` receives the target ID and matched query as props. Once the
target record exists in the rendered DOM, it:

1. Finds text matches while ignoring Arabic diacritics and normalizing common
   Persian/Arabic character variants such as `ی/ي`, `ک/ك`, and `ه/ة`.
2. Wraps only matching text nodes in `<mark class="search-target-match">`;
   HTML tags and attributes are not modified.
3. Adds `active_search` to the target record.
4. Scrolls the target into the center of the viewport.

The operation runs once per deep-link navigation and does not alter content
returned from the database.

## Error and edge-case behavior

- A missing or invalid target ID produces the normal book page without a
  target highlight.
- A target belonging to another book is ignored.
- If normalized matching finds no text, the target record is still focused
  and scrolled into view.
- Empty matched queries do not create highlight markup.
- Search snippets remain HTML-rendered as before.

## Testing and verification

- Unit tests verify that `_matchedQuery` records the highest-priority query.
- Feature tests verify result-card URLs and cross-book target rejection.
- Component logic tests cover Arabic/Persian normalization where practical.
- A browser test follows a live search result, verifies the resulting URL,
  target scroll position, active target class, and visible highlight.
