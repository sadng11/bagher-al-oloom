<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookContent;
use App\Models\ScholarWork;
use App\Models\ScholarWorkContent;
use App\Models\Writer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScholarWorkModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_table_defaults_section_to_writer_and_allows_nullable_writer_id(): void
    {
        $now = now();
        $writer = Writer::create([
            'name' => 'علامه',
            'nickname' => 'طباطبایی',
        ]);

        $writerBookId = DB::table('books')->insertGetId([
            'writer_id' => $writer->id,
            'title' => 'تفسیر المیزان',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $writerRow = DB::table('books')->where('id', $writerBookId)->first();
        $this->assertSame('writer', $writerRow->section);
        $this->assertNull($writerRow->author_name);

        $scholarBookId = DB::table('books')->insertGetId([
            'writer_id' => null,
            'section' => 'scholar',
            'author_name' => 'شیخ محقق',
            'title' => 'رساله علمی',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $scholarRow = DB::table('books')->where('id', $scholarBookId)->first();
        $this->assertNull($scholarRow->writer_id);
        $this->assertSame('scholar', $scholarRow->section);
        $this->assertSame('شیخ محقق', $scholarRow->author_name);
    }

    public function test_books_writer_id_foreign_key_remains_enforced_when_not_null(): void
    {
        $now = now();

        $this->expectException(QueryException::class);

        DB::table('books')->insert([
            'writer_id' => 999999,
            'section' => 'writer',
            'title' => 'کتاب نامعتبر',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function test_scholar_work_forces_section_scholar_and_null_writer_id_on_create_and_update(): void
    {
        $writer = Writer::create([
            'name' => 'شهید',
            'nickname' => 'مطهری',
        ]);

        $created = ScholarWork::create([
            'title' => 'رساله اول',
            'author_name' => 'محقق قمی',
        ]);

        $this->assertSame(Book::SECTION_SCHOLAR, $created->section);
        $this->assertNull($created->writer_id);

        $forcedOnCreate = ScholarWork::create([
            'title' => 'رساله دوم',
            'author_name' => 'محقق حلی',
            'writer_id' => $writer->id,
            'section' => Book::SECTION_WRITER,
        ]);

        $forcedOnCreate->refresh();
        $this->assertSame(Book::SECTION_SCHOLAR, $forcedOnCreate->section);
        $this->assertNull($forcedOnCreate->writer_id);

        $forcedOnCreate->update([
            'title' => 'رساله دوم ویرایش‌شده',
            'writer_id' => $writer->id,
            'section' => Book::SECTION_WRITER,
        ]);

        $forcedOnCreate->refresh();
        $this->assertSame('رساله دوم ویرایش‌شده', $forcedOnCreate->title);
        $this->assertSame(Book::SECTION_SCHOLAR, $forcedOnCreate->section);
        $this->assertNull($forcedOnCreate->writer_id);
    }

    public function test_scholar_work_global_scope_excludes_writer_books_while_book_remains_unscoped(): void
    {
        $writer = Writer::create([
            'name' => 'شیخ',
            'nickname' => 'انصاری',
        ]);

        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'مکاسب',
        ]);

        $scholarWork = ScholarWork::create([
            'title' => 'رساله اصولی',
            'author_name' => 'محقق خراسانی',
        ]);

        $this->assertSame(Book::SECTION_WRITER, $writerBook->section);

        $scholarWorks = ScholarWork::all();
        $this->assertCount(1, $scholarWorks);
        $this->assertSame($scholarWork->id, $scholarWorks->first()->id);

        $allBooks = Book::all();
        $this->assertCount(2, $allBooks);
        $this->assertEqualsCanonicalizing(
            [$writerBook->id, $scholarWork->id],
            $allBooks->pluck('id')->all()
        );
    }

    public function test_scholar_work_contents_and_scholar_work_content_scope_work_on_book_contents(): void
    {
        $writer = Writer::create([
            'name' => 'علامه',
            'nickname' => 'حلی',
        ]);

        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'تبصره المتعلمین',
        ]);
        BookContent::create([
            'book_id' => $writerBook->id,
            'content' => '<p>متن کتاب نویسنده</p>',
        ]);

        $scholarWork = ScholarWork::create([
            'title' => 'رساله عملیه محقق',
            'author_name' => 'آیت‌الله محقق',
        ]);
        $scholarContent = $scholarWork->contents()->create([
            'content' => '<p>متن اثر محقق</p>',
        ]);

        $this->assertSame('book_contents', (new ScholarWorkContent())->getTable());
        $this->assertSame('book_contents', (new ScholarWorkContent())->searchableAs());

        $this->assertCount(1, $scholarWork->contents);
        $this->assertSame($scholarContent->id, $scholarWork->contents->first()->id);

        $scopedContents = ScholarWorkContent::all();
        $this->assertCount(1, $scopedContents);
        $this->assertSame($scholarContent->id, $scopedContents->first()->id);

        $resolvedWork = $scopedContents->first()->scholarWork;
        $this->assertInstanceOf(ScholarWork::class, $resolvedWork);
        $this->assertSame($scholarWork->id, $resolvedWork->id);

        $resolvedBook = $scopedContents->first()->book;
        $this->assertInstanceOf(Book::class, $resolvedBook);
        $this->assertSame($scholarWork->id, $resolvedBook->id);

        $this->assertSame([
            'id' => $scholarContent->id,
            'book_id' => $scholarWork->id,
            'book_title' => 'رساله عملیه محقق',
            'content' => '<p>متن اثر محقق</p>',
        ], $scholarContent->fresh()->toSearchableArray());

        $this->assertCount(2, BookContent::all());
        $this->assertCount(1, Book::findOrFail($scholarWork->id)->contents);
    }

    public function test_book_display_author_is_scholar_work_and_get_book_work_for_both_sections(): void
    {
        $writer = Writer::create([
            'name' => 'امام',
            'nickname' => 'خمینی',
        ]);

        $writerBook = Book::create([
            'writer_id' => $writer->id,
            'title' => 'ولایت فقیه',
        ]);

        $scholarWork = ScholarWork::create([
            'title' => 'فقه پژوهشی',
            'author_name' => 'شیخ محقق',
        ]);

        $scholarAsBook = Book::getBook($scholarWork->id);
        $writerAsBook = Book::getBook($writerBook->id);

        $this->assertTrue($writerAsBook->relationLoaded('writer'));
        $this->assertTrue($scholarAsBook->relationLoaded('writer'));
        $this->assertNull($scholarAsBook->writer);

        $this->assertFalse($writerAsBook->isScholarWork());
        $this->assertTrue($scholarAsBook->isScholarWork());

        $this->assertSame($writer->full_name, $writerAsBook->display_author);
        $this->assertSame('شیخ محقق', $scholarAsBook->display_author);
    }

    public function test_get_scholar_works_filters_searches_and_falls_back_safely_on_invalid_sort(): void
    {
        $writer = Writer::create([
            'name' => 'نویسنده',
            'nickname' => 'اول',
        ]);

        Book::create([
            'writer_id' => $writer->id,
            'title' => 'کتاب قمی نویسنده',
        ]);

        $first = ScholarWork::create([
            'title' => 'رساله الف',
            'author_name' => 'محقق قمی',
            'views' => 10,
            'downloads' => 2,
        ]);

        $second = ScholarWork::create([
            'title' => 'پژوهش قمی',
            'author_name' => 'دکتر پژوهنده',
            'views' => 50,
            'downloads' => 8,
        ]);

        ScholarWork::create([
            'title' => 'متن نامرتبط',
            'author_name' => 'مولف دیگر',
            'views' => 100,
            'downloads' => 20,
        ]);

        $searchResult = Book::getScholarWorks('قمی', 'invalid_col', 'desc');
        $this->assertInstanceOf(LengthAwarePaginator::class, $searchResult);
        $this->assertSame(2, $searchResult->total());
        $this->assertSame([$second->id, $first->id], $searchResult->pluck('id')->all());

        $sortedByViews = Book::getScholarWorks('قمی', 'views', 'asc');
        $this->assertSame([$first->id, $second->id], $sortedByViews->pluck('id')->all());

        $invalidDirectionResult = Book::getScholarWorks('   ', 'title', 'sideways', 2);
        $this->assertSame(3, $invalidDirectionResult->total());
        $this->assertCount(2, $invalidDirectionResult->items());
    }
}
