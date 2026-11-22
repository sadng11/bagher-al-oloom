<?php

namespace App\Jobs;

use App\Models\Book;
use App\Models\BookStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class MakePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Book $book;
    private $book_status;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    public $timeout = 5*60;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Book $book)
    {
        $this->book = $book;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->book_status = BookStatus::updateOrCreate(['book_id' => $this->book->id], ['status' => BookStatus::START]);
        $pdf = \App\Helpers\Pdf::loadView('site.pdf', [
            'book' => $this->book,
        ]);
        $pdf->save(storage_path('pdf') . '/' . $this->book->id . '-main.pdf');
        BookStatus::find($this->book_status->id)->update(['status' => BookStatus::FINISHED]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        BookStatus::where('book_id', $this->book->id)->update(['status' => BookStatus::FAILED, 'detail' => $exception]);
    }
}
