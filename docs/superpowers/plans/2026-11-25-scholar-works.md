# برنامه پیاده‌سازی بخش «آثار علما و محققین»

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal (هدف):** پیاده‌سازی کامل بخش «آثار علما و محققین» در دیتابیس، پنل مدیریت لاراول نوا، و وب‌سایت عمومی روی زیرساخت مشترک `books` و `book_contents` با حفظ کامل کتاب‌های قبلی.

**Architecture (معماری):** جدول `books` ستون‌های `section` (پیش‌فرض `'writer'`) و `author_name` (`nullable`, پیش‌فرض `null`) را می‌گیرد و `writer_id` اختیاری (`nullable`, پیش‌فرض `null`) می‌شود. دو مدل فرزند `ScholarWork extends Book` و `ScholarWorkContent extends BookContent` همراه با منبع‌های مجزای نوا ایجاد می‌شوند تا تداخل کش `Nova::resourceForModel()` و خطای اعتبارسنجی `Relatable` در نوا پیش نیاید و در عین حال `BookObserver`، صفحه‌خوان کتاب، دانلود PDF و ایندکس `book_contents` در Meilisearch بدون دوباره‌کاری مشترک باقی بمانند.

**Tech Stack:** PHP 8+, Laravel 9, Laravel Nova 4, MySQL 8 (محیط اجرا), SQLite `:memory:` (تست PHPUnit), Blade, Bootstrap, Vitest.

**Spec (سند طراحی):** [2026-11-25-scholar-works-design.md](file:///f:/Project/bagher-al-oloom/docs/superpowers/specs/2026-11-25-scholar-works-design.md)

## محدودیت‌های سراسری (Global Constraints)

- ستون `section` روی جدول `books` باید مقدار پیش‌فرض `'writer'` داشته باشد تا تمام کتاب‌های درج‌شده قبلی بدون هیچ تغییری معتبر بمانند.
- ستون‌های `author_name` (`string(500)`) و `writer_id` روی جدول `books` باید `nullable` با پیش‌فرض `null` باشند و کلید خارجی `writer_id -> writers.id` (`onDelete('cascade')`) حفظ شود.
- فیلد `author_name` در نوا (`App\Nova\ScholarWork`) الزامی (`required`, `max:500`) است و `writer_id` در آثار علما و محققین همیشه `null` ذخیره می‌شود.
- تبدیل فایل ورد `.docx` (`BookObserver`)، دانلود PDF، شمارنده `views`/`downloads` و جستجوی Meilisearch (`book_contents`) بدون ایجاد جدول محتوای دوم یا ایندکس دوم مشترک می‌مانند.
- Migration باید هم روی SQLite حافظه‌ای (`phpunit.xml`) و هم روی MySQL 8 (`bagher-mysql`) بدون خطا اجرا شود.
- تست‌های PHP همیشه با `docker exec app-begher php artisan test` (با SQLite `:memory:` و Scout `null`) اجرا شوند؛ داده‌های دیتابیس اصلی پاک یا ریست نشوند.

## تمرکز بازبینی (Review Focus)

- **حفظ کتاب‌های قبلی پس از Migration:** رکوردهای قدیمی `books` (فاقد مقدار صریح `section`) پس از migration باید خودکار `section = 'writer'` و `author_name = null` بگیرند، در `/library/writer/{id}` دیده شوند و هرگز در `/scholars-works` ظاهر نشوند. *(تست‌شده در Task 1 و Task 3)*
- **جداسازی مدل‌ها و منابع نوا و سازگاری رابطه محتوا:** ذخیره `ScholarWork` باید اجباراً `section = 'scholar'` و `writer_id = null` بگذارد؛ `ScholarWork::contents()` باید روی کلید خارجی `'book_id'` کوئری بزند؛ `App\Nova\Book::indexQuery`/`relatableQuery` و `App\Nova\BookContent::indexQuery` نباید آثار علما را نشان دهند؛ و `ScholarWorkContent` باید `BelongsTo ScholarWork` را بدون خطای `Relatable` پاس کند. *(تست‌شده در Task 1 و Task 2)*
- **پارامترهای نامعتبر یا خالی در `/scholars-works`:** ارسال `sort` نامعتبر (مثل `?sort=-invalid` یا کاراکترهای غیرمجاز) یا جستجوی خالی/فاصله نباید باعث خطای SQL شود و باید امن به `-id` برگردد؛ جستجو با `q` باید زیررشته را در هر دو فیلد `title` و `author_name` پیدا کند. *(تست‌شده در Task 3)*
- **صفحه مطالعه کتاب و دانلود برای اثر بدون نویسنده (`writer_id = null`):** باز کردن `/library/book/{id}` (هم ساده و هم با `?p=...&q=...`) و `/library/book/download/{id}` برای یک `ScholarWork` نباید روی `$book->writer` کرش کند؛ عنوان صفحه و breadcrumb باید «آثار علما و محققین» و `author_name` را نمایش دهند. *(تست‌شده در Task 3)*
- **حفظ حریم جستجو و شمارش کتاب‌های نویسندگان:** صفحات `/library` و `/library/writer/{id}` و جستجوی فیلترشده روی یک `writerId` مشخص در `POST /book/search` نباید هیچ‌گاه آثار بخش علما و محققین را برگردانند، در حالی که جستجوی «همه مولفین» (`writerId = -1`) در `POST /book/search` و `/search` هر دو بخش را پوشش می‌دهد. *(تست‌شده در Task 3)*

---

### Task 1: Migration جدول `books` و مدل‌های `Book`، `ScholarWork` و `ScholarWorkContent`

**فایل‌ها (Files):**
- Create: `database/migrations/2026_11_25_000001_add_scholar_work_fields_to_books_table.php`
- Create: `app/Models/ScholarWork.php`
- Create: `app/Models/ScholarWorkContent.php`
- Modify: `app/Models/Book.php:1-91`
- Test: `tests/Feature/ScholarWorkModelTest.php`

**رابط‌ها (Interfaces):**
- Consumes: جدول‌های `books` و `book_contents`، مدل `App\Models\BookContent`
- Produces:
  - `Book::SECTION_WRITER = 'writer'` و `Book::SECTION_SCHOLAR = 'scholar'`
  - `Book::contents(): HasMany` (با کلید خارجی `'book_id'`)
  - `Book::isScholarWork(): bool`
  - `Book::displayAuthor(): Attribute` (`$this->display_author`)
  - `Book::getBook($book_id): Book` (همراه با `with('writer')`)
  - `Book::getScholarWorks(?string $search = null, string $sortField = 'id', string $sortDirection = 'desc', int $perPage = 15): LengthAwarePaginator`
  - `App\Models\ScholarWork extends Book` (جدول `books`، Global Scope روی `section = 'scholar'`، و `contents(): HasMany` به `ScholarWorkContent::class`)
  - `App\Models\ScholarWorkContent extends BookContent` (جدول `book_contents`، Global Scope روی `book.section = 'scholar'`، و `scholarWork(): BelongsTo`)

- [ ] **Step 1: نوشتن تست‌های شکست‌خورنده مدل و Migration در `tests/Feature/ScholarWorkModelTest.php`**

تست‌هایی بنویسید که موارد زیر را بررسی کنند:
- درج رکورد مستقیم در `books` بدون مقدار `section` مقدار پیش‌فرض `'writer'` و `author_name = null` می‌گیرد؛ درج رکورد با `writer_id = null` و `section = 'scholar'` و `author_name = 'شیخ محقق'` موفق است.
- `ScholarWork::create(['title' => 'رساله اول', 'author_name' => 'محقق قمی'])` به صورت خودکار `section = 'scholar'` و `writer_id = null` ذخیره می‌کند، حتی اگر `writer_id` یا `section` اشتباه پاس داده شده باشد.
- `ScholarWork::all()` فقط آثار علما و محققین را برمی‌گرداند و کتاب‌های `writer` را برنمی‌گرداند.
- `$scholarWork->contents` روی `book_contents.book_id` کار می‌کند و `ScholarWorkContent::all()` فقط محتوای آثار علما را برمی‌گرداند و `scholarWork()` آن همان رکورد `ScholarWork` را برمی‌گرداند.
- `$writerBook->display_author` نام کامل نویسنده و `$scholarBook->display_author` مقدار `author_name` را برمی‌گرداند و `$scholarBook->isScholarWork()` مقدار `true` دارد.
- `Book::getScholarWorks('قمی', 'invalid_col', 'desc')` با ستون نامعتبر خطا نمی‌دهد و به مرتب‌سازی امن `id desc` برمی‌گردد و فقط آثار مطابق جستجو در `title` یا `author_name` را می‌آورد.

- [ ] **Step 2: اجرای تست برای اطمینان از شکست (RED)**

Run: `docker exec app-begher php artisan test tests/Feature/ScholarWorkModelTest.php`
Expected: FAIL به دلیل نبودن ستون‌های `section` و `author_name` و کلاس‌های `ScholarWork` و `ScholarWorkContent`.

- [ ] **Step 3: پیاده‌سازی Migration و مدل‌ها**

1. ساخت `database/migrations/2026_11_25_000001_add_scholar_work_fields_to_books_table.php`:
   - در `up()`: اضافه کردن `section` (`string('section', 32)->default('writer')->after('writer_id')->index()`) و `author_name` (`string('author_name', 500)->nullable()->default(null)->after('section')`)، و در یک `Schema::table` جداگانه `writer_id` را با `->nullable()->default(null)->change()` اختیاری کنید (تست‌شده روی SQLite و MySQL).
   - در `down()`: پاک کردن `author_name` و `section`، و بازگرداندن `writer_id` در صورت نبودن مقدار `null`.
2. بروزرسانی `app/Models/Book.php`:
   - تعریف ثابت‌های `SECTION_WRITER = 'writer'` و `SECTION_SCHOLAR = 'scholar'`.
   - تعریف `$fillable = ['title', 'writer_id', 'section', 'author_name', 'file', 'pdf_file', 'views', 'downloads']`.
   - تنظیم پیش‌فرض `section = self::SECTION_WRITER` در `booted()` روی `creating` اگر خالی بود.
   - تغییر `contents()` به `return $this->hasMany(BookContent::class, 'book_id');`.
   - افزودن `isScholarWork(): bool`، `displayAuthor(): Attribute`، بروزرسانی `getBook($book_id)` با `with('writer')` و افزودن `getScholarWorks(...)` با لیست سفید ستون‌های `['id', 'title', 'author_name', 'views', 'downloads']` و جهت `['asc', 'desc']`.
3. ساخت `app/Models/ScholarWork.php`:
   - ارث‌بری از `Book`، `$table = 'books'`، اعمال Global Scope روی `$query->where($query->qualifyColumn('section'), self::SECTION_SCHOLAR)`، اجباری کردن `section = self::SECTION_SCHOLAR` و `writer_id = null` در `creating` و `updating`، و تعریف `contents(): HasMany` با `ScholarWorkContent::class` و کلید `'book_id'`.
4. ساخت `app/Models/ScholarWorkContent.php`:
   - ارث‌بری از `BookContent`، `$table = 'book_contents'`، اعمال Global Scope روی `whereHas('book', fn ($q) => $q->where('section', Book::SECTION_SCHOLAR))`، تعریف `scholarWork(): BelongsTo` به `ScholarWork::class` با `'book_id'` و حفظ `book()` با `'book_id'` برای `toSearchableArray()`.

- [ ] **Step 4: اجرای تست برای اطمینان از پاس شدن (GREEN)**

Run: `docker exec app-begher php artisan test tests/Feature/ScholarWorkModelTest.php`
Expected: PASS برای تمام تست‌های `ScholarWorkModelTest`.

- [ ] **Step 5: کامیت**

```bash
git add database/migrations/2026_11_25_000001_add_scholar_work_fields_to_books_table.php app/Models/Book.php app/Models/ScholarWork.php app/Models/ScholarWorkContent.php tests/Feature/ScholarWorkModelTest.php
git commit -m "feat: add scholar work schema and models on books table"
```

---

### Task 2: پنل مدیریت لاراول نوا (`ScholarWork`، `ScholarWorkContent`، جداسازی `Book` و ترجمه فارسی)

**فایل‌ها (Files):**
- Create: `app/Nova/ScholarWork.php`
- Create: `app/Nova/ScholarWorkContent.php`
- Modify: `app/Nova/Book.php:70-115`
- Modify: `app/Nova/BookContent.php:95-130`
- Modify: `app/Providers/NovaServiceProvider.php:34-75`
- Modify: `lang/vendor/nova/fa.json:460-514`
- Test: `tests/Feature/NovaScholarWorkTest.php`

**رابط‌ها (Interfaces):**
- Consumes: `App\Models\Book`، `App\Models\ScholarWork`، `App\Models\ScholarWorkContent`، `App\Observers\BookObserver` از Task 1
- Produces:
  - `App\Nova\ScholarWork` (`uriKey` = `scholar-works`, `$model = \App\Models\ScholarWork::class`)
  - `App\Nova\ScholarWorkContent` (`uriKey` = `scholar-work-contents`, `$model = \App\Models\ScholarWorkContent::class`, `$displayInNavigation = false`)
  - `App\Nova\Book::indexQuery` و `relatableQuery` محدود به `Book::SECTION_WRITER`
  - `App\Nova\BookContent::indexQuery` محدود به `whereHas('book', fn ($q) => $q->where('section', Book::SECTION_WRITER))`

- [ ] **Step 1: نوشتن تست‌های شکست‌خورنده نوا در `tests/Feature/NovaScholarWorkTest.php`**

تست‌هایی بنویسید که موارد زیر را بررسی کنند:
- `Nova::resourceForModel(Book::class)` کلاس `App\Nova\Book::class` و `Nova::resourceForModel(ScholarWork::class)` کلاس `App\Nova\ScholarWork::class` را برگرداند (عدم تداخل کش مدل‌ها).
- `App\Nova\Book::indexQuery` و `App\Nova\Book::relatableQuery` فقط رکوردهای `section = 'writer'` را برگردانند و `ScholarWork` را حذف کنند.
- `App\Nova\BookContent::indexQuery` فقط محتوای کتاب‌های `section = 'writer'` را برگرداند.
- `App\Nova\ScholarWork::indexQuery` فقط رکوردهای `section = 'scholar'` را برگرداند و فیلدهای آن شامل `title`، `author_name` (بدون `writer`)، `file`، `views`، `downloads`، `pdf_file` و `HasMany` به `ScholarWorkContent::class` باشند.
- `App\Nova\ScholarWorkContent::fields` دارای `BelongsTo` به `ScholarWork::class` (`scholarWork`) و `authorizedToCreate` آن `false` باشد و در دیتابیس روی یک `ScholarWorkContent` واقعی اعتبارسنجی `relatable` (درخواست `ScholarWork::relatableQuery`) رکورد `ScholarWork` را پیدا کند.
- کلیدهای فارسی `Scholar Works`، `Scholar Work`، `Scholar Work Contents`، `Scholar Work Content`، `Author Name` و `help scholar author name` در `lang/vendor/nova/fa.json` موجود و JSON فایل معتبر باشد.

- [ ] **Step 2: اجرای تست برای اطمینان از شکست (RED)**

Run: `docker exec app-begher php artisan test tests/Feature/NovaScholarWorkTest.php`
Expected: FAIL به دلیل نبودن کلاس‌های نوا و کلیدهای ترجمه و نداشتن اسکوپ `indexQuery`.

- [ ] **Step 3: پیاده‌سازی منابع نوا، Observer، منو و ترجمه‌ها**

1. ساخت `app/Nova/ScholarWork.php`:
   - کپی ساختار `App\Nova\Book` با `$model = \App\Models\ScholarWork::class`، `$search = ['id', 'title', 'author_name']`، `label() => __('Scholar Works')`، `singularLabel() => __('Scholar Work')`.
   - فیلدها دقیقاً مشابه `Book`: `ID`، `Text::make(__('Title'), 'title')`، `Text::make(__('Author Name'), 'author_name')->rules('required', 'max:500')->sortable()->help(__('help scholar author name'))`، `File::make(__('Word File'), 'file')`، `Boolean` داشتن PDF در index، `Number` برای `views` و `downloads`، `URL::make(__('Pdf File Address'), 'pdf_file')` و `HasMany::make(__('Book Contents'), 'contents', ScholarWorkContent::class)`.
2. ساخت `app/Nova/ScholarWorkContent.php`:
   - کپی ساختار `App\Nova\BookContent` با `$model = \App\Models\ScholarWorkContent::class`، `public static $displayInNavigation = false;`، `authorizedToCreate` برابر `false`، `label() => __('Scholar Work Contents')`، `singularLabel() => __('Scholar Work Content')`، و `BelongsTo::make(__('Scholar Work'), 'scholarWork', ScholarWork::class)->searchable()->rules('required')->sortable()`.
3. بروزرسانی `app/Nova/Book.php`:
   - اضافه کردن `indexQuery(NovaRequest $request, $query)` و `relatableQuery(NovaRequest $request, $query)` برای فیلتر `$query->where('section', \App\Models\Book::SECTION_WRITER)`.
4. بروزرسانی `app/Nova/BookContent.php`:
   - اضافه کردن `indexQuery(NovaRequest $request, $query)` برای فیلتر `$query->whereHas('book', fn ($q) => $q->where('section', \App\Models\Book::SECTION_WRITER))`.
5. بروزرسانی `app/Providers/NovaServiceProvider.php`:
   - ثبت `Observable::make(\App\Models\ScholarWork::class, BookObserver::class);` در `boot()`.
   - افزودن `MenuItem::resource(\App\Nova\ScholarWork::class)` در `MenuSection::make(__('Content & Book'), ...)` کنار `Book` و `Writer`.
6. بروزرسانی `lang/vendor/nova/fa.json`:
   - افزودن کلیدهای `"Scholar Works": "آثار علما و محققین"`، `"Scholar Work": "اثر علما و محققین"`، `"Scholar Work Contents": "محتوای آثار علما و محققین"`، `"Scholar Work Content": "محتوای اثر علما و محققین"`، `"Author Name": "نام صاحب اثر"` و `"help scholar author name": "نام کامل صاحب اثر یا محقق را وارد نمایید"`.

- [ ] **Step 4: اجرای تست برای اطمینان از پاس شدن (GREEN)**

Run: `docker exec app-begher php artisan test tests/Feature/NovaScholarWorkTest.php`
Expected: PASS برای تمام تست‌های `NovaScholarWorkTest`.

- [ ] **Step 5: کامیت**

```bash
git add app/Nova/ScholarWork.php app/Nova/ScholarWorkContent.php app/Nova/Book.php app/Nova/BookContent.php app/Providers/NovaServiceProvider.php lang/vendor/nova/fa.json tests/Feature/NovaScholarWorkTest.php
git commit -m "feat: add Nova ScholarWork resources and Persian translations"
```

---

### Task 3: کنترلر، مسیرهای سایت، منو و نماهای وب‌سایت (`/scholars-works` و صفحه مطالعه کتاب)

**فایل‌ها (Files):**
- Create: `app/Http/Controllers/ScholarWorkController.php`
- Create: `resources/views/site/scholar_works/index.blade.php`
- Modify: `routes/web.php:26-35`
- Modify: `resources/views/site/partials/header.blade.php:20-75`
- Modify: `resources/views/site/library/book.blade.php:1-25`
- Test: `tests/Feature/ScholarWorkSiteTest.php`

**رابط‌ها (Interfaces):**
- Consumes: `Book::getScholarWorks(...)`، `Book::getBook($id)`، `Book::isScholarWork()`، `Book::display_author` از Task 1
- Produces:
  - مسیر `GET /scholars-works` با نام `scholar_works.index` و متد `ScholarWorkController::index(Request $request)`
  - نمای `site.scholar_works.index` با نمایش عنوان اثر، `صاحب اثر`، بازدید، دانلود، جستجو و مرتب‌سازی و لینک به `route('library.book', ['id' => $item->id])`
  - پشتیبانی بدون خطا در `site.library.book` برای آثار علما و محققین (با breadcrumb به `scholar_works.index`)

- [ ] **Step 1: نوشتن تست‌های شکست‌خورنده وب‌سایت در `tests/Feature/ScholarWorkSiteTest.php`**

تست‌هایی بنویسید که موارد زیر را بررسی کنند:
- صفحه اصلی (`/`) یا هر صفحه دارای هدر، لینک «آثار علما و محققین» به `/scholars-works` را در منوی سایت رندر می‌کند.
- `GET /scholars-works` وضعیت `200` برمی‌گرداند، آثار `section = 'scholar'` را همراه با عنوان و `نام صاحب اثر` نمایش می‌دهد، و کتاب‌های بخش نویسندگان (`section = 'writer'`) را نمایش نمی‌دهد.
- جستجو روی `/scholars-works?q=...` هم بر اساس عنوان اثر و هم بر اساس نام صاحب اثر فیلتر می‌کند؛ ارسال `sort` نامعتبر (مثل `?sort=-hack`) خطا نمی‌دهد و به صورت امن پاسخ `200` برمی‌گرداند.
- باز کردن `GET /library/book/{id}` (هم عادی و هم با `?p=...&q=...`) و `GET /library/book/download/{id}` برای یک `ScholarWork` با `writer_id = null` بدون خطا `200` (و ریدایرکت دانلود) برمی‌گرداند، در صفحه نام صاحب اثر و breadcrumb «آثار علما و محققین» وجود دارد و `views`/`downloads` افزایش می‌یابد.
- صفحات `/library` و `/library/writer/{id}` و `POST /book/search` با `writerId` مشخص هیچ اثر بخش علما و محققین را برنمی‌گردانند، در حالی که `POST /book/search` با `writerId = -1` می‌تواند نتایج هر دو بخش را برگرداند.

- [ ] **Step 2: اجرای تست برای اطمینان از شکست (RED)**

Run: `docker exec app-begher php artisan test tests/Feature/ScholarWorkSiteTest.php`
Expected: FAIL به دلیل نبودن مسیر `scholar_works.index` و خطای `$data['book']->writer->full_name` در `book.blade.php`.

- [ ] **Step 3: پیاده‌سازی کنترلر، مسیر، منو و نماهای Blade**

1. ساخت `app/Http/Controllers/ScholarWorkController.php`:
   - متد `index(Request $request)`: خواندن `q` (`trim`), خواندن `sort` (پیش‌فرض `'-id'`), استخراج جهت (`'-'` => `'desc'`، در غیر این صورت `'asc'`) و نام فیلد (از میان `['id', 'title', 'author_name', 'views', 'downloads']` و پیش‌فرض `'id'`)، فراخوانی `Book::getScholarWorks(...)` و بازگشت `view('site.scholar_works.index', ['data' => $works, 'search' => $search, 'sort' => $sort])`.
2. افزودن مسیر در `routes/web.php`:
   - `Route::get('scholars-works', [ScholarWorkController::class, 'index'])->name('scholar_works.index');`
3. بروزرسانی `resources/views/site/partials/header.blade.php`:
   - افزودن لینک `آثار علما و محققین` (`route('scholar_works.index')`) بعد از `کتابخانه` هم در منوی دسکتاپ (`navbar-nav mx-auto`) و هم در منوی موبایل (`side-nav`).
4. ساخت `resources/views/site/scholar_works/index.blade.php`:
   - ساختار و استایل هماهنگ با `writer_books.blade.php` و `sound/category/list.blade.php`: بنر صفحه، breadcrumb با `['آثار علما و محققین']`، فرم GET جستجو (`q`) + مرتب‌سازی (`sort`: جدیدترین، قدیمی‌ترین، عنوان صعودی/نزولی، نام صاحب اثر صعودی/نزولی)، کارت‌های آثار (عنوان، `صاحب اثر: ...`، فوتر بازدید و دانلود، لینک به `route('library.book', ['id' => $item->id])`)، `@include('site.partials.empty_result')` و `{{ $data->links() }}`.
5. بروزرسانی `resources/views/site/library/book.blade.php`:
   - استفاده از `$data['book']->display_author` در `@section('title', ...)` (در صورت خالی بودن بدون کلمه `اثر`).
   - تنظیم شرطی breadcrumb: اگر `$data['book']->isScholarWork()` بود `[route('scholar_works.index') => 'آثار علما و محققین', $data['book']->display_author ?: $data['book']->title]`، و اگر `$data['book']->writer` وجود داشت همان breadcrumb قبلی `کتابخانه -> کتب ...`.

- [ ] **Step 4: اجرای تست برای اطمینان از پاس شدن (GREEN)**

Run: `docker exec app-begher php artisan test tests/Feature/ScholarWorkSiteTest.php`
Expected: PASS برای تمام تست‌های `ScholarWorkSiteTest`.

- [ ] **Step 5: کامیت**

```bash
git add app/Http/Controllers/ScholarWorkController.php resources/views/site/scholar_works/index.blade.php routes/web.php resources/views/site/partials/header.blade.php resources/views/site/library/book.blade.php tests/Feature/ScholarWorkSiteTest.php
git commit -m "feat: add scholar works public listing, navigation, and safe reader breadcrumbs"
```

---

### Task 4: راستی‌آزمایی کامل، اجرای Migration روی دیتابیس و بررسی نهایی

**فایل‌ها (Files):**
- بدون تغییر مورد انتظار در کد (صرفاً تایید سلامت کامل و اجرای migration محیط توسعه)

**رابط‌ها (Interfaces):**
- Consumes: Task 1، Task 2 و Task 3
- Produces: تایید اجرای کل تست‌های PHP، JS، بیلد Vite و سلامت رکوردهای قبلی در دیتابیس MySQL

- [ ] **Step 1: اجرای کل مجموعه تست‌های PHP**

Run: `docker exec app-begher php artisan test`
Expected: PASS بدون هیچ خطایی (از جمله `BookSearchTest`، `LibraryBookDeepLinkTest`، `SearchRouteTest` و تست‌های جدید).

- [ ] **Step 2: اجرای تست‌های JavaScript و بررسی بیلد Vite**

Run: `npm test -- --run`
Expected: تمام تست‌های Vitest پاس شوند.

Run: `npm run build`
Expected: بیلد بدون خطا کامل شود (در صورت تغییر فایل‌های بیلدشده‌ای که ردیابی می‌شوند بررسی شود).

- [ ] **Step 3: بررسی و اجرای امن Migration جدید در کانتینر و تایید سلامت کتاب‌های قبلی**

Run: `docker exec app-begher php artisan migrate:status`
> توجه: در این محیط ممکن است migrationهای قدیمی‌ترِ `Pending` وجود داشته باشند؛ ابتدا بررسی کنید کدام جداول/ستون‌ها وجود دارند و فقط migration جدید خودمان را با `--path=database/migrations/2026_11_25_000001_add_scholar_work_fields_to_books_table.php` (یا در صورت نیاز پس از هماهنگی) اجرا کنید.

Run: `docker exec app-begher php artisan migrate --path=database/migrations/2026_11_25_000001_add_scholar_work_fields_to_books_table.php --force`
Run: `docker exec app-begher php artisan tinker --execute="echo json_encode(['total' => \App\Models\Book::count(), 'writer_section' => \App\Models\Book::where('section', 'writer')->count(), 'scholar_section' => \App\Models\ScholarWork::count()]);"`
Expected: migration جدید با موفقیت اجرا شود و تمام کتاب‌های قبلی `section = 'writer'` داشته باشند.

- [ ] **Step 4: بررسی وضعیت Git و ثبت کامیت نهایی (در صورت نیاز)**

Run: `git status`
Expected: فقط تغییرات برنامه‌ریزی‌شده وجود داشته باشند و هیچ فایل ناخواسته‌ای جا نمانده باشد.
