<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookContent;
use App\Models\ScholarWork;
use App\Models\Writer;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ScholarWorkSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_header_renders_scholar_works_navigation_link(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('آثار علما و محققین')
            ->assertSee('/scholars-works');

        $this->assertGreaterThanOrEqual(
            2,
            substr_count($response->getContent(), 'آثار علما و محققین')
        );

        $scholarWorksHref = route('scholar_works.index');
        $anchorCount = preg_match_all(
            '/<a\b[^>]*\bhref="' . preg_quote($scholarWorksHref, '/') . '"[^>]*>/',
            $response->getContent()
        );

        $this->assertGreaterThanOrEqual(2, $anchorCount);
    }

    public function test_scholar_works_index_lists_only_scholar_section_works_with_author_and_stats(): void
    {
        $emptyResponse = $this->get('/scholars-works');
        $emptyResponse->assertOk()
            ->assertViewIs('site.scholar_works.index')
            ->assertSee('هیچ داده ای در این قسمت وجود ندارد.');

        $writer = Writer::create([
            'name' => 'علامه',
            'nickname' => 'طباطبایی',
        ]);

        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'کتاب اختصاصی نویسنده',
        ]);

        $firstWork = ScholarWork::create([
            'title' => 'رساله اصولیه',
            'author_name' => 'آیت الله محقق قمی',
            'views' => 1234,
            'downloads' => 56,
        ]);

        $secondWork = ScholarWork::create([
            'title' => 'پژوهش فقهی',
            'author_name' => 'حجت الاسلام پژوهنده',
            'views' => 78,
            'downloads' => 9,
        ]);

        $response = $this->get('/scholars-works');

        $response->assertOk()
            ->assertViewIs('site.scholar_works.index')
            ->assertSee('آثار علما و محققین')
            ->assertSee($firstWork->title)
            ->assertSee($firstWork->author_name)
            ->assertSee('صاحب اثر:')
            ->assertSee('1,234')
            ->assertSee('56')
            ->assertSee(route('library.book', ['id' => $firstWork->id]))
            ->assertSee($secondWork->title)
            ->assertSee($secondWork->author_name)
            ->assertSee(route('library.book', ['id' => $secondWork->id]))
            ->assertDontSee($writerBook->title);
    }

    public function test_scholar_works_index_searches_sorts_paginates_and_handles_invalid_sort_safely(): void
    {
        ScholarWork::create([
            'title' => 'تفسیر نور',
            'author_name' => 'دکتر الف',
            'views' => 200,
            'downloads' => 10,
        ]);

        ScholarWork::create([
            'title' => 'مقاله علمی',
            'author_name' => 'شیخ نوری',
            'views' => 50,
            'downloads' => 300,
        ]);

        ScholarWork::create([
            'title' => 'رساله مستقل',
            'author_name' => 'محقق ب',
            'views' => 100,
            'downloads' => 50,
        ]);

        $searchByTitleAndAuthor = $this->get('/scholars-works?q=' . urlencode('نور'));
        $searchByTitleAndAuthor->assertOk()
            ->assertViewHas('search', 'نور')
            ->assertSee('تفسیر نور')
            ->assertSee('مقاله علمی')
            ->assertDontSee('رساله مستقل');

        $searchByAuthorOnly = $this->get('/scholars-works?q=' . urlencode('محقق ب'));
        $searchByAuthorOnly->assertOk()
            ->assertSee('رساله مستقل')
            ->assertDontSee('تفسیر نور')
            ->assertDontSee('مقاله علمی');

        foreach (['-hack', '-', urlencode('+'), '--id', ''] as $invalidSortValue) {
            $this->get('/scholars-works?sort=' . $invalidSortValue)
                ->assertOk()
                ->assertViewHas('sort', '-id')
                ->assertSeeInOrder(['رساله مستقل', 'مقاله علمی', 'تفسیر نور']);
        }

        $arraySort = $this->get('/scholars-works?sort[]=bad');
        $arraySort->assertOk()
            ->assertViewHas('sort', '-id')
            ->assertSeeInOrder(['رساله مستقل', 'مقاله علمی', 'تفسیر نور']);

        $unencodedPlusSort = $this->get('/scholars-works?sort=+author_name');
        $unencodedPlusSort->assertOk()
            ->assertViewHas('sort', '+author_name')
            ->assertSeeInOrder(['تفسیر نور', 'مقاله علمی', 'رساله مستقل']);

        $titleAsc = $this->get('/scholars-works?sort=' . urlencode('+title'));
        $titleAsc->assertOk()
            ->assertViewHas('sort', '+title')
            ->assertSeeInOrder(['تفسیر نور', 'رساله مستقل', 'مقاله علمی']);

        $this->get('/scholars-works?sort=-views')
            ->assertOk()
            ->assertViewHas('sort', '-views')
            ->assertSeeInOrder(['تفسیر نور', 'رساله مستقل', 'مقاله علمی']);

        $this->get('/scholars-works?sort=' . urlencode('+views'))
            ->assertOk()
            ->assertViewHas('sort', '+views')
            ->assertSeeInOrder(['مقاله علمی', 'رساله مستقل', 'تفسیر نور']);

        $this->get('/scholars-works?sort=views')
            ->assertOk()
            ->assertViewHas('sort', '+views')
            ->assertSeeInOrder(['مقاله علمی', 'رساله مستقل', 'تفسیر نور']);

        $this->get('/scholars-works?sort=-downloads')
            ->assertOk()
            ->assertViewHas('sort', '-downloads')
            ->assertSeeInOrder(['مقاله علمی', 'رساله مستقل', 'تفسیر نور']);

        $this->get('/scholars-works?sort=+downloads')
            ->assertOk()
            ->assertViewHas('sort', '+downloads')
            ->assertSeeInOrder(['تفسیر نور', 'رساله مستقل', 'مقاله علمی']);

        for ($i = 1; $i <= 16; $i++) {
            ScholarWork::create([
                'title' => sprintf('اثر صفحه‌بندی %02d', $i),
                'author_name' => 'محقق صفحه‌بندی',
            ]);
        }

        $pageOne = $this->get('/scholars-works?q=' . urlencode('صفحه‌بندی') . '&sort=' . urlencode('+title'));
        $pageOne->assertOk()
            ->assertSee('page=2');

        $content = $pageOne->getContent();
        $this->assertMatchesRegularExpression('/href="[^"]*q=[^"]*page=2[^"]*"/', $content);
    }

    public function test_scholar_work_reader_and_download_work_without_writer_and_track_stats(): void
    {
        $scholarWork = ScholarWork::create([
            'title' => 'رساله علمی محقق',
            'author_name' => 'شیخ محقق',
            'pdf_file' => 'https://example.com/files/scholar-work.pdf',
            'views' => 0,
            'downloads' => 0,
        ]);

        $targetContent = $scholarWork->contents()->create([
            'content' => '<p>متن پژوهشی حضرت بقیه الله</p>',
        ]);

        $readerResponse = $this->get(route('library.book', ['id' => $scholarWork->id]));
        $readerResponse->assertOk()
            ->assertSee('<title>' . config('app.name') . ' - کتاب رساله علمی محقق اثر شیخ محقق</title>', false)
            ->assertSee('آثار علما و محققین')
            ->assertSee(route('scholar_works.index'))
            ->assertSee('شیخ محقق');

        $this->assertSame(1, $scholarWork->fresh()->views);

        $deepLinkResponse = $this->get(route('library.book', [
            'id' => $scholarWork->id,
            'p' => $targetContent->id,
            'q' => 'بقیه الله',
        ]));
        $deepLinkResponse->assertOk()
            ->assertViewHas('targetId', $targetContent->id)
            ->assertViewHas('targetQuery', 'بقیه الله')
            ->assertViewHas('initialPage', 1)
            ->assertSee(':target-id="' . $targetContent->id . '"', false);

        $this->assertSame(1, $scholarWork->fresh()->views);

        $downloadResponse = $this->get(route('library.download', ['book_id' => $scholarWork->id]));
        $downloadResponse->assertRedirect('https://example.com/files/scholar-work.pdf');
        $this->assertSame(1, $scholarWork->fresh()->downloads);

        $contentResponse = $this->getJson(route('library.content', ['id' => $scholarWork->id]));
        $contentResponse->assertOk()
            ->assertJsonPath('data.0.id', $targetContent->id);

        $authorlessWork = ScholarWork::create([
            'title' => 'رساله بدون نام مولف',
            'author_name' => null,
        ]);

        $authorlessResponse = $this->get(route('library.book', ['id' => $authorlessWork->id]));
        $authorlessResponse->assertOk()
            ->assertSee('<title>' . config('app.name') . ' - کتاب رساله بدون نام مولف</title>', false)
            ->assertSee('آثار علما و محققین');

        $orphanWriterBook = Book::create([
            'writer_id' => null,
            'section' => Book::SECTION_WRITER,
            'title' => 'کتاب بدون نویسنده',
        ]);

        $orphanResponse = $this->get(route('library.book', ['id' => $orphanWriterBook->id]));
        $orphanResponse->assertOk()
            ->assertSee('<title>' . config('app.name') . ' - کتاب کتاب بدون نویسنده</title>', false)
            ->assertSee(route('library'));

        $writer = Writer::create([
            'name' => 'شهید',
            'nickname' => 'مطهری',
        ]);
        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'عدل الهی',
        ]);

        $writerBookResponse = $this->get(route('library.book', ['id' => $writerBook->id]));
        $writerBookResponse->assertOk()
            ->assertSee('<title>' . config('app.name') . ' - کتاب عدل الهی اثر شهید مطهری</title>', false)
            ->assertSee(route('library.writer', ['id' => $writer->id]))
            ->assertSee('کتب شهید مطهری');
    }

    public function test_library_pages_and_writer_scoped_search_exclude_scholar_works_while_global_search_includes_both(): void
    {
        $writer = Writer::create([
            'name' => 'علامه',
            'nickname' => 'امینی',
        ]);

        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'الغدیر',
        ]);
        $writerContent = BookContent::create([
            'book_id' => $writerBook->id,
            'content' => '<p>متن کتاب الغدیر درباره ولایت</p>',
        ]);

        $scholarWork = ScholarWork::create([
            'title' => 'تحقیق ولایت',
            'author_name' => 'دکتر محقق',
        ]);
        $scholarContent = $scholarWork->contents()->create([
            'content' => '<p>متن اثر محقق درباره ولایت</p>',
        ]);

        $libraryResponse = $this->get('/library?sort=-id');
        $libraryResponse->assertOk()
            ->assertSee($writer->full_name)
            ->assertSee('تعداد کتب 1 عدد')
            ->assertDontSee($scholarWork->title)
            ->assertDontSee($scholarWork->author_name);

        $writerResponse = $this->get(route('library.writer', ['id' => $writer->id]));
        $writerResponse->assertOk()
            ->assertSee($writerBook->title)
            ->assertDontSee($scholarWork->title);

        $scopedSearch = Mockery::mock(SearchService::class);
        $scopedSearch->shouldReceive('search')
            ->once()
            ->with('ولایت', 1, 100, [$writerBook->id])
            ->andReturn([
                'hits' => [[
                    'id' => $writerContent->id,
                    'book_id' => $writerBook->id,
                    'book_title' => $writerBook->title,
                    'content' => 'متن کتاب الغدیر درباره ولایت',
                    '_matchedQuery' => 'ولایت',
                ]],
                'nbHits' => 1,
                'estimatedTotalHits' => 1,
            ]);
        $this->app->instance(SearchService::class, $scopedSearch);

        $this->postJson('/book/search', [
            'keyword' => 'ولایت',
            'writerId' => $writer->id,
        ])->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.book.id', $writerBook->id);

        $globalSearch = Mockery::mock(SearchService::class);
        $globalSearch->shouldReceive('search')
            ->once()
            ->with('ولایت', 1, 100, null)
            ->andReturn([
                'hits' => [
                    [
                        'id' => $writerContent->id,
                        'book_id' => $writerBook->id,
                        'book_title' => $writerBook->title,
                        'content' => 'متن کتاب الغدیر درباره ولایت',
                        '_matchedQuery' => 'ولایت',
                    ],
                    [
                        'id' => $scholarContent->id,
                        'book_id' => $scholarWork->id,
                        'book_title' => $scholarWork->title,
                        'content' => 'متن اثر محقق درباره ولایت',
                        '_matchedQuery' => 'ولایت',
                    ],
                ],
                'nbHits' => 2,
                'estimatedTotalHits' => 2,
            ]);
        $this->app->instance(SearchService::class, $globalSearch);

        $this->postJson('/book/search', [
            'keyword' => 'ولایت',
            'writerId' => -1,
        ])->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.book.id', $writerBook->id)
            ->assertJsonPath('1.book.id', $scholarWork->id);
    }

    public function test_scholar_works_index_orders_tied_sort_values_deterministically_across_pages(): void
    {
        $works = [];
        for ($i = 1; $i <= 16; $i++) {
            $works[] = ScholarWork::create([
                'title' => sprintf('اثر هم‌رتبه %02d', $i),
                'author_name' => 'محقق یکسان',
                'views' => 100,
                'downloads' => 10,
            ]);
        }

        $expectedDescIds = collect($works)->pluck('id')->reverse()->values();
        $pageOneExpectedTitles = $expectedDescIds->take(15)->map(
            fn (int $id): string => ScholarWork::findOrFail($id)->title
        )->all();
        $pageTwoExpectedTitle = ScholarWork::findOrFail($expectedDescIds->last())->title;

        foreach (['+author_name', '-views'] as $sort) {
            $pageOne = $this->get('/scholars-works?sort=' . urlencode($sort) . '&page=1');
            $pageOne->assertOk()
                ->assertSeeInOrder($pageOneExpectedTitles)
                ->assertDontSee($pageTwoExpectedTitle);

            $pageTwo = $this->get('/scholars-works?sort=' . urlencode($sort) . '&page=2');
            $pageTwo->assertOk()
                ->assertSee($pageTwoExpectedTitle)
                ->assertDontSee($pageOneExpectedTitles[0]);
        }
    }
}
