<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\SearchHighlighter;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function search(
        Request $request,
        SearchService $searchService
    ): JsonResponse {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:3'],
            'writerId' => ['nullable', 'integer'],
        ]);

        $keyword = trim(convert_char($validated['keyword']));
        $writerId = (int) ($validated['writerId'] ?? -1);
        $allowedBookIds = null;

        if ($writerId !== -1) {
            $allowedBookIds = Book::query()
                ->where('writer_id', $writerId)
                ->pluck('id')
                ->map(static fn ($bookId): int => (int) $bookId)
                ->all();
        }

        $results = $searchService->search(
            $keyword,
            1,
            100,
            $allowedBookIds
        );
        $groups = [];

        foreach (array_slice($results['hits'] ?? [], 0, 100) as $hit) {
            if (!isset($hit['id'], $hit['book_id'])) {
                continue;
            }

            $bookId = (int) $hit['book_id'];
            $formatted = $hit['_formatted'] ?? [];
            $bookTitle = $formatted['book_title']
                ?? $hit['book_title']
                ?? '';
            $content = $formatted['content'] ?? $hit['content'] ?? '';

            if (!isset($groups[$bookId])) {
                $groups[$bookId] = [
                    'book' => [
                        'id' => $bookId,
                        'title' => SearchHighlighter::clean($bookTitle),
                    ],
                    'search' => [],
                ];
            }

            $groups[$bookId]['search'][] = [
                'id' => (int) $hit['id'],
                'book_id' => $bookId,
                'content' => SearchHighlighter::clean($content),
                'matched_query' => $hit['_matchedQuery'] ?? $keyword,
            ];
        }

        return response()->json(array_values($groups));
    }
}
