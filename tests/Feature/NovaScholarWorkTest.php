<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookContent;
use App\Models\ScholarWork;
use App\Models\ScholarWorkContent;
use App\Models\User;
use App\Models\Writer;
use App\Nova\Book as NovaBook;
use App\Nova\BookContent as NovaBookContent;
use App\Nova\ScholarWork as NovaScholarWork;
use App\Nova\ScholarWorkContent as NovaScholarWorkContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Fields\URL;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Tests\TestCase;

class NovaScholarWorkTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsNovaUser(): User
    {
        $user = User::factory()->create();
        Gate::define('viewNova', fn () => true);
        $this->actingAs($user);

        return $user;
    }

    private function bootNova(): NovaRequest
    {
        Nova::flushState();
        ServingNova::dispatch(Request::create('/manage'));

        return NovaRequest::create('/manage');
    }

    public function test_nova_resolves_distinct_resources_for_book_and_scholar_work_without_cache_collision(): void
    {
        $this->bootNova();

        $this->assertSame(NovaBook::class, Nova::resourceForModel(Book::class));
        $this->assertSame(NovaScholarWork::class, Nova::resourceForModel(ScholarWork::class));
        $this->assertSame(NovaBook::class, Nova::resourceForModel(new Book()));
        $this->assertSame(NovaScholarWork::class, Nova::resourceForModel(new ScholarWork()));

        $this->assertSame(NovaBookContent::class, Nova::resourceForModel(BookContent::class));
        $this->assertSame(NovaScholarWorkContent::class, Nova::resourceForModel(ScholarWorkContent::class));

        $this->assertSame('scholar-works', NovaScholarWork::uriKey());
        $this->assertSame('scholar-work-contents', NovaScholarWorkContent::uriKey());
        $this->assertFalse(NovaScholarWorkContent::$displayInNavigation);
    }

    public function test_nova_book_index_and_relatable_queries_exclude_scholar_works(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $writer = Writer::create(['name' => 'علامه', 'nickname' => 'طباطبایی']);
        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'تفسیر المیزان',
        ]);
        $scholarWork = ScholarWork::create([
            'title' => 'رساله علمی محقق',
            'author_name' => 'آیت‌الله محقق',
        ]);

        $indexIds = NovaBook::indexQuery($request, Book::query())->pluck('id')->all();
        $this->assertSame([$writerBook->id], $indexIds);

        $relatableIds = NovaBook::relatableQuery($request, Book::query())->pluck('id')->all();
        $this->assertSame([$writerBook->id], $relatableIds);

        $joinedIndexIds = NovaBook::indexQuery(
            $request,
            Book::query()->leftJoin('writers', 'writers.id', '=', 'books.writer_id')->select('books.*')
        )->pluck('books.id')->all();
        $this->assertSame([$writerBook->id], $joinedIndexIds);

        $joinedRelatableIds = NovaBook::relatableQuery(
            $request,
            Book::query()->leftJoin('writers', 'writers.id', '=', 'books.writer_id')->select('books.*')
        )->pluck('books.id')->all();
        $this->assertSame([$writerBook->id], $joinedRelatableIds);

        $response = $this->getJson('/nova-api/books')->assertOk();
        $returnedIds = collect($response->json('resources'))->pluck('id.value')->all();
        $this->assertSame([$writerBook->id], $returnedIds);
        $this->assertNotContains($scholarWork->id, $returnedIds);
    }

    public function test_nova_book_content_index_query_excludes_scholar_work_contents(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $writer = Writer::create(['name' => 'شیخ', 'nickname' => 'انصاری']);
        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'مکاسب',
        ]);
        $writerContent = BookContent::create([
            'book_id' => $writerBook->id,
            'content' => '<p>متن کتاب نویسنده</p>',
        ]);

        $scholarWork = ScholarWork::create([
            'title' => 'پژوهش علمی',
            'author_name' => 'محقق حلی',
        ]);
        $scholarContent = $scholarWork->contents()->create([
            'content' => '<p>متن اثر محقق</p>',
        ]);

        $indexIds = NovaBookContent::indexQuery($request, BookContent::query())->pluck('id')->all();
        $this->assertSame([$writerContent->id], $indexIds);

        $response = $this->getJson('/nova-api/book-contents')->assertOk();
        $returnedIds = collect($response->json('resources'))->pluck('id.value')->all();
        $this->assertSame([$writerContent->id], $returnedIds);
        $this->assertNotContains($scholarContent->id, $returnedIds);

        $associatableBooks = $this->getJson('/nova-api/book-contents/associatable/book')->assertOk();
        $associatableBookIds = collect($associatableBooks->json('resources'))->pluck('value')->all();
        $this->assertSame([$writerBook->id], $associatableBookIds);
        $this->assertNotContains($scholarWork->id, $associatableBookIds);
    }

    public function test_nova_book_blocks_cross_section_detail_edit_update_and_delete_for_scholar_works(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $writer = Writer::create(['name' => 'علامه', 'nickname' => 'امینی']);
        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'الغدیر',
        ]);
        $scholarWork = ScholarWork::create([
            'title' => 'رساله تخصصی',
            'author_name' => 'آیت‌الله محقق',
        ]);

        $this->assertSame([$writerBook->id], NovaBook::detailQuery($request, Book::query())->pluck('id')->all());
        $this->assertSame([$writerBook->id], NovaBook::editQuery($request, Book::query())->pluck('id')->all());

        $this->get('/manage/resources/books/' . $scholarWork->id)->assertNotFound();
        $this->get('/manage/resources/books/' . $scholarWork->id . '/edit')->assertNotFound();
        $this->getJson('/nova-api/books/' . $scholarWork->id)->assertNotFound();
        $this->getJson('/nova-api/books/' . $scholarWork->id . '/update-fields')->assertNotFound();

        $this->putJson('/nova-api/books/' . $scholarWork->id, [
            'title' => 'تغییر غیرمجاز',
            'writer' => $writer->id,
        ])->assertNotFound();

        $scholarWork->refresh();
        $this->assertSame('رساله تخصصی', $scholarWork->title);
        $this->assertSame(Book::SECTION_SCHOLAR, $scholarWork->section);
        $this->assertNull($scholarWork->writer_id);

        $this->deleteJson('/nova-api/books?resources[]=' . $scholarWork->id)->assertOk();
        $this->assertDatabaseHas('books', [
            'id' => $scholarWork->id,
            'section' => Book::SECTION_SCHOLAR,
        ]);

        $this->getJson('/nova-api/books/' . $writerBook->id)->assertOk();
        $this->getJson('/nova-api/books/' . $writerBook->id . '/update-fields')->assertOk();
        $this->putJson('/nova-api/books/' . $writerBook->id, [
            'title' => 'الغدیر فی الکتاب و السنه',
            'writer' => $writer->id,
        ])->assertOk();
        $this->assertSame('الغدیر فی الکتاب و السنه', $writerBook->fresh()->title);

        $this->getJson('/nova-api/scholar-works/' . $writerBook->id)->assertNotFound();
        $this->putJson('/nova-api/scholar-works/' . $writerBook->id, [
            'title' => 'تغییر غیرمجاز کتاب نویسنده',
            'author_name' => 'محقق جعلی',
        ])->assertNotFound();
        $this->assertSame(Book::SECTION_WRITER, $writerBook->fresh()->section);
    }

    public function test_nova_book_content_blocks_cross_section_detail_edit_relatable_update_and_delete(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $writer = Writer::create(['name' => 'شیخ', 'nickname' => 'مفید']);
        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'الارشاد',
        ]);
        $writerContent = BookContent::create([
            'book_id' => $writerBook->id,
            'content' => '<p>متن کتاب نویسنده</p>',
        ]);

        $scholarWork = ScholarWork::create([
            'title' => 'مقاله پژوهشی',
            'author_name' => 'استاد محقق',
        ]);
        $scholarContent = $scholarWork->contents()->create([
            'content' => '<p>متن اثر محقق</p>',
        ]);

        $this->assertSame([$writerContent->id], NovaBookContent::detailQuery($request, BookContent::query())->pluck('id')->all());
        $this->assertSame([$writerContent->id], NovaBookContent::editQuery($request, BookContent::query())->pluck('id')->all());
        $this->assertSame([$writerContent->id], NovaBookContent::relatableQuery($request, BookContent::query())->pluck('id')->all());

        $this->get('/manage/resources/book-contents/' . $scholarContent->id)->assertNotFound();
        $this->get('/manage/resources/book-contents/' . $scholarContent->id . '/edit')->assertNotFound();
        $this->getJson('/nova-api/book-contents/' . $scholarContent->id)->assertNotFound();
        $this->getJson('/nova-api/book-contents/' . $scholarContent->id . '/update-fields')->assertNotFound();

        $this->putJson('/nova-api/book-contents/' . $scholarContent->id, [
            'book' => $writerBook->id,
            'content' => '<p>انتقال غیرمجاز محتوا</p>',
        ])->assertNotFound();

        $scholarContent->refresh();
        $this->assertSame($scholarWork->id, $scholarContent->book_id);
        $this->assertSame('<p>متن اثر محقق</p>', $scholarContent->content);

        $this->deleteJson('/nova-api/book-contents?resources[]=' . $scholarContent->id)->assertOk();
        $this->assertDatabaseHas('book_contents', [
            'id' => $scholarContent->id,
            'book_id' => $scholarWork->id,
        ]);

        $this->getJson('/nova-api/book-contents/' . $writerContent->id)->assertOk();
        $this->putJson('/nova-api/book-contents/' . $writerContent->id, [
            'book' => $writerBook->id,
            'content' => '<p>متن ویرایش‌شده کتاب</p>',
        ])->assertOk();
        $this->assertSame('<p>متن ویرایش‌شده کتاب</p>', $writerContent->fresh()->content);

        $this->getJson('/nova-api/scholar-work-contents/' . $writerContent->id)->assertNotFound();
        $this->putJson('/nova-api/scholar-work-contents/' . $writerContent->id, [
            'scholarWork' => $scholarWork->id,
            'content' => '<p>انتقال غیرمجاز به آثار</p>',
        ])->assertNotFound();
        $this->assertSame($writerBook->id, $writerContent->fresh()->book_id);
    }

    public function test_nova_scholar_work_index_query_and_fields(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $writer = Writer::create(['name' => 'شهید', 'nickname' => 'مطهری']);
        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'کتاب نویسنده',
        ]);
        $scholarWork = ScholarWork::create([
            'title' => 'اثر محقق',
            'author_name' => 'دکتر محقق',
        ]);

        $indexIds = NovaScholarWork::indexQuery($request, ScholarWork::query())->pluck('id')->all();
        $this->assertSame([$scholarWork->id], $indexIds);

        $response = $this->getJson('/nova-api/scholar-works')->assertOk();
        $returnedIds = collect($response->json('resources'))->pluck('id.value')->all();
        $this->assertSame([$scholarWork->id], $returnedIds);
        $this->assertNotContains($writerBook->id, $returnedIds);

        $this->assertSame(['id', 'title', 'author_name'], NovaScholarWork::$search);

        $resource = new NovaScholarWork($scholarWork);
        $fields = collect($resource->fields($request));

        $this->assertNull($fields->firstWhere('attribute', 'writer'));

        $idField = $fields->firstWhere('attribute', 'id');
        $this->assertInstanceOf(ID::class, $idField);

        $titleField = $fields->firstWhere('attribute', 'title');
        $this->assertInstanceOf(Text::class, $titleField);

        $authorField = $fields->firstWhere('attribute', 'author_name');
        $this->assertInstanceOf(Text::class, $authorField);
        $this->assertContains('required', $authorField->rules);
        $this->assertContains('max:500', $authorField->rules);

        $fileField = $fields->firstWhere('attribute', 'file');
        $this->assertInstanceOf(File::class, $fileField);

        $hasPdfField = $fields->first(fn ($field) => $field instanceof Boolean);
        $this->assertInstanceOf(Boolean::class, $hasPdfField);

        $viewsField = $fields->firstWhere('attribute', 'views');
        $this->assertInstanceOf(Number::class, $viewsField);

        $downloadsField = $fields->firstWhere('attribute', 'downloads');
        $this->assertInstanceOf(Number::class, $downloadsField);

        $pdfField = $fields->firstWhere('attribute', 'pdf_file');
        $this->assertInstanceOf(URL::class, $pdfField);

        $contentsField = $fields->firstWhere('attribute', 'contents');
        $this->assertInstanceOf(HasMany::class, $contentsField);
        $this->assertSame(NovaScholarWorkContent::class, $contentsField->resourceClass);
    }

    public function test_nova_scholar_work_content_fields_authorization_and_relatable_validation(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $writer = Writer::create(['name' => 'نویسنده', 'nickname' => 'نمونه']);
        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'کتاب نویسنده',
        ]);
        $scholarWork = ScholarWork::create([
            'title' => 'اثر علمی',
            'author_name' => 'محقق قمی',
        ]);
        $scholarContent = $scholarWork->contents()->create([
            'content' => '<p>متن اولیه اثر</p>',
        ]);

        $this->assertFalse(NovaScholarWorkContent::authorizedToCreate($request));

        $fields = collect((new NovaScholarWorkContent($scholarContent))->fields($request));

        $belongsToField = $fields->firstWhere('attribute', 'scholarWork');
        $this->assertInstanceOf(BelongsTo::class, $belongsToField);
        $this->assertSame(NovaScholarWork::class, $belongsToField->resourceClass);
        $this->assertContains('required', $belongsToField->rules);

        $trixField = $fields->first(fn ($field) => $field instanceof Trix);
        $this->assertInstanceOf(Trix::class, $trixField);
        $this->assertSame('content', $trixField->attribute);

        $associatableResponse = $this->getJson('/nova-api/scholar-work-contents/associatable/scholarWork')->assertOk();
        $associatableIds = collect($associatableResponse->json('resources'))->pluck('value')->all();
        $this->assertSame([$scholarWork->id], $associatableIds);
        $this->assertNotContains($writerBook->id, $associatableIds);

        $this->putJson('/nova-api/scholar-work-contents/' . $scholarContent->id, [
            'scholarWork' => $scholarWork->id,
            'content' => '<p>متن ویرایش‌شده اثر</p>',
        ])->assertOk();

        $this->assertSame('<p>متن ویرایش‌شده اثر</p>', $scholarContent->fresh()->content);

        $this->putJson('/nova-api/scholar-work-contents/' . $scholarContent->id, [
            'scholarWork' => $writerBook->id,
            'content' => '<p>تلاش نامعتبر</p>',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('scholarWork');

        $viaResponse = $this->getJson(
            '/nova-api/scholar-work-contents?viaResource=scholar-works&viaResourceId=' . $scholarWork->id . '&viaRelationship=contents'
        )->assertOk();
        $viaIds = collect($viaResponse->json('resources'))->pluck('id.value')->all();
        $this->assertSame([$scholarContent->id], $viaIds);
    }

    public function test_nova_service_provider_registers_book_observer_for_scholar_work_and_menu_item(): void
    {
        $this->actingAsNovaUser();
        $request = $this->bootNova();

        $this->assertTrue(Event::hasListeners('eloquent.created: ' . ScholarWork::class));

        $menu = collect(Nova::resolveMainMenu($request));
        $contentSection = $menu->first(
            fn ($item) => $item instanceof MenuSection && $item->name === __('Content & Book')
        );
        $this->assertInstanceOf(MenuSection::class, $contentSection);

        $paths = collect($contentSection->items)
            ->filter(fn ($item) => $item instanceof MenuItem)
            ->pluck('path')
            ->all();

        $this->assertContains('/resources/books', $paths);
        $this->assertContains('/resources/writers', $paths);
        $this->assertContains('/resources/scholar-works', $paths);

        Storage::fake('word');
        config(['services.book_server.url' => 'http://book-server.test']);
        Http::fake([
            'http://book-server.test/files/*' => Http::response([
                'data' => ['<p>صفحه اول اثر</p>', '<p>صفحه دوم اثر</p>'],
            ], 200),
        ]);

        $this->post('/nova-api/scholar-works', [
            'title' => 'رساله بدون نویسنده',
            'file' => UploadedFile::fake()->create('missing-author.docx', 16),
        ], ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors('author_name');

        $response = $this->post('/nova-api/scholar-works', [
            'title' => 'رساله جدید نوا',
            'author_name' => 'محقق اصفهانی',
            'file' => UploadedFile::fake()->create('scholar.docx', 16),
        ], ['Accept' => 'application/json'])->assertCreated();

        $createdId = $response->json('id');
        $createdWork = ScholarWork::findOrFail($createdId);

        $this->assertSame(Book::SECTION_SCHOLAR, $createdWork->section);
        $this->assertNull($createdWork->writer_id);
        $this->assertSame('محقق اصفهانی', $createdWork->author_name);
        $this->assertCount(2, $createdWork->contents);
        $this->assertCount(2, ScholarWorkContent::where('book_id', $createdWork->id)->get());

        $this->putJson('/nova-api/scholar-works/' . $createdId, [
            'title' => 'رساله ویرایش‌شده نوا',
            'author_name' => 'محقق اصفهانی (ویرایش)',
            'pdf_file' => 'https://example.com/scholar.pdf',
        ])->assertOk();

        $createdWork->refresh();
        $this->assertSame('رساله ویرایش‌شده نوا', $createdWork->title);
        $this->assertSame('محقق اصفهانی (ویرایش)', $createdWork->author_name);
        $this->assertSame('https://example.com/scholar.pdf', $createdWork->pdf_file);
        $this->assertSame(Book::SECTION_SCHOLAR, $createdWork->section);
        $this->assertNull($createdWork->writer_id);
    }

    public function test_nova_persian_translations_json_is_valid_and_contains_scholar_work_keys(): void
    {
        $path = lang_path('vendor/nova/fa.json');
        $raw = file_get_contents($path);
        $this->assertNotFalse($raw);

        $translations = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        $expected = [
            'Scholar Works' => 'آثار علما و محققین',
            'Scholar Work' => 'اثر علما و محققین',
            'Scholar Work Contents' => 'محتوای آثار علما و محققین',
            'Scholar Work Content' => 'محتوای اثر علما و محققین',
            'Author Name' => 'نام صاحب اثر',
            'help scholar author name' => 'نام کامل صاحب اثر یا محقق را وارد نمایید',
        ];

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $translations);
            $this->assertSame($value, $translations[$key]);
        }
    }
}
