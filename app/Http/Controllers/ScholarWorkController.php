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

        $allowedSorts = [
            '-id',
            '+id',
            '+title',
            '-title',
            '+author_name',
            '-author_name',
        ];

        $rawSort = $request->query('sort', '-id');
        $candidateSort = '-id';

        if (is_string($rawSort)) {
            $trimmedSort = trim($rawSort);

            $candidateSort = in_array($trimmedSort, ['id', 'title', 'author_name'], true)
                ? '+' . $trimmedSort
                : $trimmedSort;
        }

        $sort = in_array($candidateSort, $allowedSorts, true) ? $candidateSort : '-id';
        $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $sortField = substr($sort, 1);

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
