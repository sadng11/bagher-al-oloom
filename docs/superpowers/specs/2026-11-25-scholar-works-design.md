# طراحی بخش «آثار علما و محققین»

## هدف و انگیزه

ایجاد بخش جدیدی با نام **«آثار علما و محققین»** تا مدیران سایت بتوانند آثار علما و محققین متعدد را بدون نیاز به ثبت یک رکورد جداگانه در بخش «نویسندگان» (`writers`) منتشر کنند، و کاربران سایت نیز از طریق یک منوی مستقل در نوار بالای سایت، این آثار را مشاهده، مطالعه، جستجو و دانلود کنند.

### اهداف
- ایجاد یک بخش مستقل در پنل مدیریت نوا با عنوان **«آثار علما و محققین»** زیرمجموعه منوی «محتوی و کتب» با همان فیلدها و روند بخش فعلی کتاب‌ها (`Title`، آپلود فایل ورد `.docx` برای تبدیل خودکار به متن کتاب، `Pdf File Address`، تعداد بازدید و دانلود، و مدیریت محتوای کتاب)، به طوری که به جای انتخاب `Writer`، فیلد متنی آزاد **«نام صاحب اثر»** (`author_name`) توسط ادمین وارد شود.
- حفظ کامل سلامت داده‌های فعلی جدول `books` با قرار دادن مقدار پیش‌فرض روی ستون‌های جدید، به گونه‌ای که کتاب‌های درج‌شده قبلی هیچ تغییری یا مشکلی پیدا نکنند.
- افزودن گزینه مستقل **«آثار علما و محققین»** در منوی دسکتاپ و موبایل سایت، همراه با صفحه لیست آثار (عنوان، نام صاحب اثر، تعداد بازدید و دانلود، جستجو در عنوان یا نام صاحب اثر، و مرتب‌سازی).
- استفاده مجدد از صفحه مطالعه آنلاین کتاب، دانلود PDF، شمارنده بازدید/دانلود، تبدیل فایل ورد به متن (`BookObserver`) و جستجوی متنی Meilisearch بدون دوباره‌کاری.

### موارد خارج از محدوده (Non-Goals & Anti-Goals)
- **عدم گروه‌بندی خودکار صاحبان آثار در یک صفحه مجزا:** چون نام صاحب اثر متنی است، لیست آثار در این بخش مستقیماً نمایش داده می‌شود (همراه با جستجو و فیلتر).
- **عدم تغییر در ساختار فعلی «کتابخانه» و «نویسندگان»:** بخش فعلی کتابخانه، نویسندگان و آدرس‌های آن‌ها دست‌نخورده باقی می‌ماند.
- **پرهیز از تکرار پایپ‌لاین:** از کپی کردن `BookObserver`، جدول `book_contents`، کامپوننت `BookContent.vue` یا ایجاد ایندکس دوم در Meilisearch خودداری می‌شود.
- **جلوگیری از خطا روی کتاب‌های فاقد `writer_id`:** تمام صفحات و نماهای مشترک (به‌ویژه صفحه مطالعه کتاب و نتایج جستجو) باید هنگام `null` بودن `writer` بدون خطا عمل کنند.

## محدودیت‌ها و پیش‌فرض‌های فنی
- رکوردهای فعلی جدول `books` دارای `writer_id` اجباری هستند و باید پس از اجرای migration با مقدار پیش‌فرض `'writer'` در ستون `section` همچنان بدون هیچ اختلالی به کار خود ادامه دهند.
- تبدیل فایل ورد در `BookObserver` از طریق `Nova::whenServing(...)` و سرویس `BOOK_SERVER_URL` انجام می‌شود.
- موتور جستجوی Meilisearch از ایندکس `book_contents` با فیلدهای `book_id` و `book_title` استفاده می‌کند.
- تست‌های PHPUnit روی SQLite حافظه‌ای (`phpunit.xml`) و محیط اصلی روی MySQL 8 (`bagher-mysql`) اجرا می‌شوند؛ بنابراین migration باید با هر دو سازگار باشد.

## روش‌های بررسی‌شده و انتخاب روش

1. **روش ۱: استفاده از زیرساخت مشترک `books` و `book_contents` با تفکیک بخش (انتخاب‌شده)**
   - افزودن ستون `section` (با پیش‌فرض `'writer'`) و `author_name` (اختیاری با پیش‌فرض `null`) و اختیاری کردن `writer_id` (با پیش‌فرض `null`) روی جدول `books`.
   - تعریف مدل `App\Models\ScholarWork extends Book` متصل به جدول `books` همراه با Global Scope روی `section = 'scholar'`، و ایجاد منبع نوا `App\Nova\ScholarWork` در کنار محدود کردن لیست `App\Nova\Book::indexQuery` به `section = 'writer'`.
   - **مزایا:** عدم تکرار کد در `BookObserver`، `BookContent`، ایندکس `book_contents` در Meilisearch، کامپوننت مطالعه کتاب، دانلود PDF و جستجوی کلی `/search`. همچنین استفاده از کلاس مدل مجزای `ScholarWork` از تداخل کش داخلی `Nova::resourceForModel()` جلوگیری می‌کند.
   - **مصالحه‌ها:** کوئری‌های مربوط به بخش نویسندگان باید همچنان روی `writer_id` یا `section = 'writer'` فیلتر شوند و صفحه نمایش کتاب هنگام `null` بودن `writer` از نام صاحب اثر (`author_name`) استفاده کند.

2. **روش ۲: ساخت جداول و پایپ‌لاین کاملاً مجزا (`scholar_works` و `scholar_work_contents`)**
   - ایجاد جداول، مدل‌ها، Observer، مسیرهای خواندن و ایندکس دوم Meilisearch.
   - **مزایا:** جداسازی کامل جداول در دیتابیس.
   - **مصالحه‌ها:** تکرار تمام منطق تبدیل ورد، صفحه‌خوان، شمارنده‌ها و نیاز به جستجوی چندایدکسی در `SearchService` و `ConfigureMeilisearch`. به دلیل هزینه نگهداری بالا و شکنندگی رد شد.

## جزئیات طراحی و تغییرات

### ۱. تغییر ساختار دیتابیس (Migration روی `books`)
- افزودن ستون `section` از نوع `string(32)` با مقدار پیش‌فرض `'writer'` و ایندکس روی آن (تمام کتاب‌های قبلی خودکار `'writer'` می‌گیرند).
- افزودن ستون `author_name` از نوع `string(500)` با `nullable()->default(null)` (مطابق طول نام نویسنده در جدول `writers`؛ رکوردهای قبلی مقدار `null` می‌گیرند).
- تغییر ستون `writer_id` به `nullable()->default(null)` با حفظ کلید خارجی به `writers` (`onDelete('cascade')`) برای کتاب‌های متصل به نویسندگان.

### ۲. مدل‌ها (`App\Models\Book` و `App\Models\ScholarWork`)
- **مدل `App\Models\Book`:**
  - تعریف ثابت‌های `SECTION_WRITER = 'writer'` و `SECTION_SCHOLAR = 'scholar'`.
  - افزودن `'section'` و `'author_name'` به `$fillable`.
  - مشخص کردن صریح کلید خارجی `'book_id'` در رابطه `contents()`: `$this->hasMany(BookContent::class, 'book_id')` تا در کلاس فرزند `ScholarWork` کلید اشتباه `scholar_work_id` حدس زده نشود.
  - در `booted()` (رویداد `creating`): اگر `section` مقداردهی نشده بود، پیش‌فرض `self::SECTION_WRITER` قرار گیرد.
  - متد و صفت کمکی:
    - `isScholarWork(): bool` بررسی `section === self::SECTION_SCHOLAR`
    - `displayAuthor` (`display_author`) برگرداندن `$this->writer?->full_name ?? $this->author_name ?? ''`
    - متد `getScholarWorks(?string $search = null, string $sortField = 'id', string $sortDirection = 'desc', int $perPage = 15)` با لیست سفید ستون‌های مجاز مرتب‌سازی (`id`، `title`، `author_name`، `views`، `downloads`).
  - مدل `Book` بدون Global Scope می‌ماند تا `BookContent::book()`، `LibraryController::book()`، `LibraryController::download()` و لینک‌های نتایج جستجو برای هر دو بخش کار کنند.
- **مدل `App\Models\ScholarWork extends Book`:**
  - اتصال به جدول `books` (`protected $table = 'books';`).
  - در `booted()`: اعمال Global Scope برای فیلتر `section = Book::SECTION_SCHOLAR` و تنظیم اجباری `section = Book::SECTION_SCHOLAR` و `writer_id = null` در رویدادهای `creating` و `updating`.
  - رابطه `contents()` به `ScholarWorkContent::class` (با کلید خارجی صریح `'book_id'`) متصل می‌شود.
- **مدل `App\Models\ScholarWorkContent extends BookContent`:**
  - اتصال به همان جدول `book_contents` (`protected $table = 'book_contents';`) و استفاده از همان ایندکس Meilisearch (`book_contents`) در Laravel Scout.
  - در `booted()`: اعمال Global Scope برای محدود کردن ردیف‌ها به کتاب‌هایی که `section = Book::SECTION_SCHOLAR` دارند (`whereHas('book', ...)`).
  - تعریف رابطه `scholarWork()`: `$this->belongsTo(ScholarWork::class, 'book_id')` و حفظ `book()` با کلید خارجی `'book_id'` برای `toSearchableArray()`.

### ۳. پنل مدیریت لاراول نوا (`Book`، `BookContent`، `ScholarWork`، `ScholarWorkContent` و `NovaServiceProvider`)
- **`App\Nova\Book`:**
  - محدود کردن `indexQuery` و `relatableQuery` به `section = Book::SECTION_WRITER` تا در بخش «کتاب ها» فقط کتاب‌های متصل به نویسندگان دیده شوند.
- **`App\Nova\BookContent`:**
  - محدود کردن `indexQuery` به رکوردهای متصل به کتاب‌های `section = Book::SECTION_WRITER` تا «محتوای کتب» نیز با بخش «کتاب ها» هم‌خوان بماند *(انتخاب پیشنهادی تیم فنی؛ در صورت نیاز قابل مشترک‌سازی است)*.
- **`App\Nova\ScholarWork`:**
  - متصل به مدل `\App\Models\ScholarWork::class`، با `$title = 'title'` و جستجوی `['id', 'title', 'author_name']`.
  - عناوین فارسی: «آثار علما و محققین» (جمع) و «اثر علما و محققین» (مفرد).
  - فیلدها دقیقاً مشابه `Book.php`:
    1. شناسه (`ID`)
    2. عنوان اثر (`Title` — الزامی، حداقل ۳ کاراکتر، قابل مرتب‌سازی)
    3. نام صاحب اثر (`Author Name` / `author_name` — فیلد متنی آزاد، الزامی، حداکثر ۵۰۰ کاراکتر، قابل مرتب‌سازی)
    4. فایل ورد (`Word File` / `.docx` — مخفی در ویرایش و جزئیات، ذخیره در دیسک `word`)
    5. وضعیت داشتن PDF در لیست (`Has Pdf File`)
    6. تعداد بازدید (`Views` — فقط خواندنی، قابل مرتب‌سازی)
    7. تعداد دانلود (`Downloads` — فقط خواندنی، قابل مرتب‌سازی)
    8. آدرس فایل پی دی اف (`Pdf File Address` / `pdf_file`)
    9. محتوای اثر (`HasMany` به `ScholarWorkContent::class`)
- **`App\Nova\ScholarWorkContent`:**
  - متصل به مدل `\App\Models\ScholarWorkContent::class` با فیلدهای مشابه `BookContent.php` (با `BelongsTo::make(__('Scholar Work'), 'scholarWork', ScholarWork::class)` به جای `Book::class` تا مشاهده، ویرایش و بازگشت از پاراگراف‌ها در صفحه جزئیات «آثار علما و محققین» بدون خطای اعتبارسنجی `Relatable` انجام شود و در جستجوی Meilisearch نیز خودکار به‌روز بماند).
  - با `public static $displayInNavigation = false;` از شلوغ شدن منوی کناری نوا جلوگیری می‌شود و مدیران از داخل هر اثر به محتوای آن دسترسی دارند *(انتخاب پیشنهادی تیم فنی؛ در صورت نیاز می‌توان به منوی کناری هم اضافه کرد)*.
- **`App\Providers\NovaServiceProvider`:**
  - ثبت `Observable::make(\App\Models\ScholarWork::class, BookObserver::class);` در کنار `Book::class`.
  - افزودن `MenuItem::resource(\App\Nova\ScholarWork::class)` در بخش «محتوی و کتب» منوی نوا.
- **`lang/vendor/nova/fa.json`:**
  - افزودن ترجمه‌های فارسی `Scholar Works`، `Scholar Work`، `Scholar Work Contents`، `Scholar Work Content`، `Author Name` و متن راهنمای `help scholar author name`.

### ۴. بخش عمومی سایت (مسیرها، کنترلر و نماها)
- **منوی بالای سایت و منوی موبایل (`resources/views/site/partials/header.blade.php`):**
  - افزودن آیتم **«آثار علما و محققین»** با لینک `route('scholar_works.index')` بلافاصله بعد از «کتابخانه» در هر دو منو.
- **مسیر و کنترلر (`routes/web.php` و `ScholarWorkController`):**
  - مسیر `GET /scholars-works` با نام `scholar_works.index`.
  - پشتیبانی از پارامتر `q` برای جستجو در عنوان یا نام صاحب اثر، و پارامتر `sort` (پیش‌فرض `-id` یعنی جدیدترین، همراه با `+id`، `+title`، `-title`، `+author_name`، `-author_name`) و صفحه‌بندی ۱۵ تایی همراه با حفظ کوئری استرینگ.
- **صفحه لیست آثار (`resources/views/site/scholar_works/index.blade.php`):**
  - هم‌شکل قالب فعلی سایت (`site.app`، بنر و breadcrumb با عنوان «آثار علما و محققین»).
  - باکس جستجو و مرتب‌سازی در بالای لیست.
  - نمایش کارت هر اثر با عنوان کتاب، «صاحب اثر: ...» و فوتر شامل تعداد بازدید و دانلود، متصل به صفحه مطالعه کتاب (`route('library.book', ['id' => $item->id])`).
- **صفحه مطالعه کتاب (`resources/views/site/library/book.blade.php` و `LibraryController::book`):**
  - بارگذاری رابطه `writer` در `Book::getBook($id)`.
  - استفاده امن از `$data['book']->display_author` در `<title>` صفحه.
  - تنظیم هوشمند breadcrumb: اگر اثر متعلق به بخش «آثار علما و محققین» بود مسیر `خانه > آثار علما و محققین > نام صاحب اثر (یا عنوان)` نمایش داده شود و اگر متعلق به کتابخانه نویسندگان بود همان مسیر قبلی `خانه > کتابخانه > کتب ...` حفظ شود.
- **جستجو:**
  - جستجوی کلی سایت (`/search`) و جستجوی «همه مولفین» در پنجره داخل کتاب‌خوان به صورت خودکار آثار علما و محققین را هم شامل می‌شوند و روی صفحه مطالعه باز می‌شوند.
  - فیلتر نویسنده خاص در جستجوی داخل کتاب و صفحه نویسنده در کتابخانه همچنان فقط کتاب‌های همان نویسنده را می‌آورند.

## موارد جزئی بر عهده پیاده‌ساز
- انتخاب متن دقیق راهنماها و placeholder های فارسی (مثل متن باکس جستجو در صفحه لیست آثار).
- اطمینان از سازگاری کامل migration تغییر ستون `writer_id` به nullable روی هر دو موتور SQLite و MySQL 8.
- نوشتن تست‌های خودکار Feature برای تایید مقدار پیش‌فرض کتاب‌های قبلی، جداسازی مدل‌ها، صفحه `/scholars-works` (جستجو و مرتب‌سازی) و باز شدن بدون خطای صفحه مطالعه کتاب برای آثار علما و محققین.
