<?php

namespace App\Observers;

use App\Models\Writer;

class WriterObserver
{
    /**
     * Handle the Writer "created" event.
     *
     * @param  \App\Models\Writer  $writer
     * @return void
     */
    public function created(Writer $writer)
    {
        //
    }

    /**
     * Handle the Writer "updated" event.
     *
     * @param  \App\Models\Writer  $writer
     * @return void
     */
    public function updated(Writer $writer)
    {
        //
    }

    /**
     * Handle the Writer "deleted" event.
     *
     * @param  \App\Models\Writer  $writer
     * @return void
     */
    public function deleted(Writer $writer)
    {
        //
    }

    /**
     * Handle the Writer "restored" event.
     *
     * @param  \App\Models\Writer  $writer
     * @return void
     */
    public function restored(Writer $writer)
    {
        //
    }

    /**
     * Handle the Writer "force deleted" event.
     *
     * @param  \App\Models\Writer  $writer
     * @return void
     */
    public function forceDeleted(Writer $writer)
    {
        //
    }
}
