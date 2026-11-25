<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ScholarWorkController;
use App\Http\Controllers\SoundController;
use App\Services\SearchHighlighter;
use App\Services\SearchService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', [HomeController::class, 'index']);
Route::get('content/{type}', [ContentController::class, 'index'])->name('content');
Route::get('library', [LibraryController::class, 'index'])->name('library');
Route::get('library/writer/{id}', [LibraryController::class, 'writer'])->name('library.writer');
Route::get('library/book/{id}', [LibraryController::class, 'book'])->name('library.book');
Route::get('library/book/content/{id}', [LibraryController::class, 'content'])->name('library.content');
Route::get('library/book/download/{book_id}', [LibraryController::class, 'download'])->name('library.download');
Route::get('scholars-works', [ScholarWorkController::class, 'index'])->name('scholar_works.index');
Route::get('sound/category/', [SoundController::class, 'index'])->name('sound_category');
Route::get('sound/{id}', [SoundController::class, 'show'])->name('sound.detail');
Route::get('sound/category/{id}', [SoundController::class, 'show_category'])->name('sound_category.detail');
Route::get('contact', [ContactController::class, 'index'])->name('contact');
Route::post('contact', [ContactController::class, 'contact'])->name('contact.post');


Route::post('book/search', [BookController::class, 'search']);



Route::get('search', function (Request $request) {
    $search  = $request->get('q');
    $perPage = 20;
    $page    = (int) $request->get('page', 1);

    $results = app(SearchService::class)->search($search ?? '', $page, $perPage);

    // پاکسازی هایلایت‌های کوتاه/بی‌معنی مثل "ال" تنها
    foreach ($results['hits'] as &$hit) {
        if (isset($hit['_formatted']['content'])) {
            $hit['_formatted']['content'] = SearchHighlighter::clean($hit['_formatted']['content']);
        }
        if (isset($hit['_formatted']['book_title'])) {
            $hit['_formatted']['book_title'] = SearchHighlighter::clean($hit['_formatted']['book_title']);
        }
    }
    unset($hit);
    // dd($results);
    return view('site.search.results', compact('results', 'search', 'page', 'perPage'));
})->name('search');


// Route::get('/artisan', function () {
//     Artisan::call('config:clear');
//     Artisan::call('config:cache');
//     return 'Config cached successfully!<br><pre>' . Artisan::output() . '</pre>';
// });


// Route::get('/run-storage-link', function () {
//     try {
//         Artisan::call('storage:link');
//         return '✅ storage link created successfully.';
//     } catch (\Exception $e) {
//         return '❌ Error: ' . $e->getMessage();
//     }
// });
