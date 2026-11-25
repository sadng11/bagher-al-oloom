<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ScholarWorkController extends Controller
{
    /**
     * Display the paginated public listing of scholar works with search and sorting.
     */
    public function index(Request $request): View
    {
        $rawSearch = $request->query('q', '');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';

        $allowedSortFields = ['id', 'title', 'author_name', 'views', 'downloads'];
        $rawSort = $request->query('sort', '-id');
        $trimmedSort = is_string($rawSort) ? trim($rawSort) : '';

        $hasSign = str_starts_with($trimmedSort, '-') || str_starts_with($trimmedSort, '+');
        $candidatePrefix = str_starts_with($trimmedSort, '-') ? '-' : '+';
        $candidateField = $hasSign ? substr($trimmedSort, 1) : $trimmedSort;

        if (! in_array($candidateField, $allowedSortFields, true)) {
            $sort = '-id';
            $sortDirection = 'desc';
            $sortField = 'id';
        } else {
            $sort = $candidatePrefix . $candidateField;
            $sortDirection = $candidatePrefix === '-' ? 'desc' : 'asc';
            $sortField = $candidateField;
        }

        $works = Book::getScholarWorks(
            $search !== '' ? $search : null,
            $sortField,
            $sortDirection
        )->withQueryString();

        return view('site.scholar_works.index', [
            'data' => $works,
            'search' => $search,
            'sort' => $sort,
        ]);
    }
}
