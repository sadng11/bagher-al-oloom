# Meilisearch Union Search Design

## Goal

For a multi-word query such as `بقیة الله`, return the ordered union of:

1. Results matching the complete query `بقیة الله`
2. Results matching `بقیة`
3. Results matching `الله`

Typo tolerance remains disabled, so unrelated similar words such as `بقوة`
and `البقرة` are not accepted as matches.

## Architecture and data flow

The `/search` route delegates searching to `SearchService`.
`SearchService` normalizes whitespace, builds one query for the complete input
followed by one query for each unique word, and sends them together through
Meilisearch's multi-search API. This keeps the operation to one network
round-trip.

Results are merged in query order. A document already returned by a
higher-priority query is skipped in later result sets, using its indexed `id`
as the deduplication key.

Pagination is applied to the merged, deduplicated result set so page boundaries
do not expose duplicate documents. Highlighting, cropping, and the existing
highlight cleanup remain available to the view.

## Error and edge-case behavior

- Repeated words create only one word-level query.
- Consecutive whitespace is normalized before query construction.
- An empty query returns no hits without contacting Meilisearch.
- Meilisearch errors continue to follow Laravel's existing exception handling.

## Testing

Unit tests cover query construction, priority-preserving deduplication,
pagination, repeated words, and empty input. A route-level test verifies that
the route delegates to the service and preserves the response structure used
by the current view.
