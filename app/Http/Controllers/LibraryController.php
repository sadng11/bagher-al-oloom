<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookContent;
use App\Models\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->get('sort', null)) {
            return redirect()->route('library', ['sort' => '-id']);
        }
        $sort = $request->get('sort');
        $type = ($request->get('sort')[0] == '-' ? 'desc' : 'asc');
        $field = Str::replace($request->get('sort')[0], '', $sort);
        $writer = Writer::getAllWriter(null, true, $field, $type);
        return view('site.library.writers', ['data' => $writer]);
    }

    public function writer(Request $request, $id)
    {
        $writer = Writer::findOrFail($id);
//        if (!$request->get('sort', null)) {
//            return redirect()->route('library.writer', ['id' => $id, 'sort' => '-id']);
//        }
        $sort = $request->get('sort');
        $type = ($request->get('sort')[0] == '-' ? 'desc' : 'asc');
        $field = Str::replace($request->get('sort')[0], '', $sort);
        $books = Book::getWriterBooks($id, $field, $type);
        return view('site.library.writer_books', ['data' => $books, 'writer' => $writer]);
    }

    public function book(Request $request, $id)
    {
        $book = Book::getBook($id);

        if (!session()->has('viewed_book_' . $book->id)) {
            $book->increment('views');
            session()->put('viewed_book_' . $book->id, true);
        }

        $requestedTargetId = $request->integer('p') ?: null;
        $requestedQuery = trim((string) $request->query('q', ''));
        $target = $requestedTargetId
            ? BookContent::where('book_id', $book->id)->find($requestedTargetId)
            : null;
        $targetId = $target?->id;
        $targetQuery = $target && $requestedQuery !== '' ? $requestedQuery : null;
        $initialPage = 1;

        if ($target) {
            $targetPosition = BookContent::where('book_id', $book->id)
                ->where('id', '<=', $target->id)
                ->count();
            $initialPage = max(1, (int) ceil($targetPosition / 30));
        }

        $writers = Writer::getAllWriter(['id', 'name', 'nickname'], false);

        return view('site.library.book', [
            'data' => [
                'book' => $book,
                'book_content' => ['data' => []],
            ],
            'writers' => $writers,
            'targetId' => $targetId,
            'targetQuery' => $targetQuery,
            'initialPage' => $initialPage,
        ]);
    }

    public function content(Request $request, $book_id)
    {
        return BookContent::where('book_id', $book_id)
            ->orderBy('id', 'asc')
            ->paginate(30, ['content', 'id']);
    }

    public function download(Request $request, $book_id)
    {
        $book = Book::findOrFail($book_id);
        if ($book) {
            $book->increment('downloads');
            $s = Storage::disk('pdf')->getConfig()['root'];
            $headers = array(
                'Content-Type: application/pdf',
            );
            return redirect()->away($book->pdf_file);
        }
    }
}
