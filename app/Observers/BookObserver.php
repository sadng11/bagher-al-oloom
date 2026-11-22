<?php

namespace App\Observers;

use App\Jobs\MakePdf;
use App\Models\Book;
use App\Models\BookContent;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use function PHPUnit\Framework\throwException;

class BookObserver
{
    /**
     * Handle the Book "created" event.
     *
     * @param \App\Models\Book $book
     * @return void
     */
    public function created(Book $book)
    {
        Nova::whenServing(function (NovaRequest $request) use ($book) {
            try {
                $serverUrl = config('services.book_server.url', env('BOOK_SERVER_URL'));
                if (empty($serverUrl)) {
                    throw new Exception("BOOK_SERVER_URL is not set or empty in configuration.");
                }

                $endpoint = rtrim($serverUrl, '/') . '/files/' . $book->file;
                $response = Http::acceptJson()->timeout(60 * 3)->get($endpoint)->onError(function ($e) use ($book) {
                    Storage::disk('word')->delete($book->file);
                    throw new \Exception("Server return error : " . $e->status());
                })->json();
                foreach ($response['data'] as $item) {
                    BookContent::create(['book_id' => $book->id, 'content' => $item]);
                }
                Storage::disk('word')->delete($book->file);
//                MakePdf::dispatch($book);
            } catch (Exception $e) {
                Storage::disk('word')->delete($book->file);
                \Log::error('Observer Error file id: '.$book->id);
                \Log::error('Observer Error: '.$e->getMessage());
                throw new \Exception("Internal Server return error : " . $e->getMessage());
            }

        });

    }

    /**
     * Handle the Book "updated" event.
     *
     * @param \App\Models\Book $book
     * @return void
     */
    public function updated(Book $book)
    {
        //
    }

    /**
     * Handle the Book "deleted" event.
     *
     * @param \App\Models\Book $book
     * @return void
     */
    public function deleted(Book $book)
    {
        //
    }

    /**
     * Handle the Book "restored" event.
     *
     * @param \App\Models\Book $book
     * @return void
     */
    public function restored(Book $book)
    {
        //
    }

    /**
     * Handle the Book "force deleted" event.
     *
     * @param \App\Models\Book $book
     * @return void
     */
    public function forceDeleted(Book $book)
    {
        //
    }
}
