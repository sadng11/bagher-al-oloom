# طراحی بخش «تصاویر» (آلبوم‌های تصاویر)

## هدف و انگیزه

ایجاد یک بخش مستقل با عنوان **«تصاویر»** در وب‌سایت و پنل مدیریت Laravel Nova برای انتشار و دسته‌بندی تصاویر در قالب آلبوم‌ها، به‌گونه‌ای که آلبوم‌ها بتوانند **بدون نام** ساخته شوند و هر زمان قابل **نام‌گذاری و ویرایش** باشند، هر تصویر دارای **توضیح تصویر (Caption)** باشد، حجم تصاویر هنگام بارگذاری به‌صورت خودکار بهینه‌سازی شود و در هنگام بازدید نیز بارگذاری سریع همراه با نمایش تعداد بازدید فراهم باشد.

### اهداف
- پشتیبانی از آلبوم‌های بدون نام (فیلد `title` اختیاری/Nullable با نمایش «آلبوم بدون نام» در سایت و «آلبوم بدون نام #ID» در پنل مدیریت) با امکان نام‌گذاری و ویرایش در هر زمان.
- امکان آپلود تکی تصاویر در هر آلبوم در پنل Laravel Nova به‌همراه ثبت و ویرایش توضیح تصویر (`caption`).
- پذیرش فرمت‌های رایج تصویر (`jpg`, `jpeg`, `png`, `webp`, `gif`, `bmp`, `avif`) با اعتبارسنجی MIME و سقف حجم **۲ مگابایت (`2048` کیلوبایت)** پیش از فشرده‌سازی خودکار.
- فشرده‌سازی و ریسایز خودکار تصویر پس از آپلود (حداکثر ابعاد `1920px`) و تولید خودکار تصویر بندانگشتی (`600px`) برای نمایش سریع در لیست آلبوم‌ها و گرید گالری.
- صفحه عمومی لیست آلبوم‌ها (`/images`) و صفحه نمایش آلبوم (`/images/{id}`) با Lazy Loading، صفحه‌بندی، لایت‌باکس Fancybox (بدون دکمه دانلود) و شمارنده بازدید آلبوم.

### موارد خارج از محدوده (Non-Goals)
- عدم نمایش یا ارائه لینک دانلود مستقیم عکس‌ها (طبق درخواست کاربر).
- عدم پیاده‌سازی آپلود گروهی/چندتایی هم‌زمان در این مرحله (آپلود تکی مدنظر است).
- عدم تغییر در عملکرد بخش فعلی `ImageSlider` صفحه اصلی.

## جزئیات فنی

### ۱. دیسک ذخیره‌سازی و محدودیت آپلود PHP
- افزودن دیسک `gallery` در `config/filesystems.php` متصل به `storage_path('app/public/gallery')` با آدرس `APP_URL/storage/gallery`.
- افزایش `upload_max_filesize = 4M` و `post_max_size = 10M` در `custom-opcache.ini` تا فایل‌های نزدیک به ۲ مگابایت بدون خطای سطح PHP به اعتبارسنجی لاراول برسند و سقف دقیق `max:2048` با پیام فارسی اعمال شود.

### ۲. ساختار پایگاه داده
- **جدول `photo_albums`:**
  - `id`
  - `title` (`string(500)->nullable()`)
  - `description` (`text()->nullable()`)
  - `cover_image` (`string()->nullable()`)
  - `cover_thumbnail` (`string()->nullable()`)
  - `status` (`boolean()->default(true)`)
  - `views` (`unsignedBigInteger()->default(0)`)
  - `sort_order` (`integer()->default(0)`)
  - `timestamps()`
- **جدول `photos`:**
  - `id`
  - `photo_album_id` (`foreignId->constrained('photo_albums')->cascadeOnDelete()`)
  - `image` (`string()`)
  - `thumbnail` (`string()->nullable()`)
  - `caption` (`text()->nullable()`)
  - `original_extension` (`string(20)->nullable()`)
  - `width` (`unsignedInteger()->nullable()`)
  - `height` (`unsignedInteger()->nullable()`)
  - `file_size` (`unsignedBigInteger()->nullable()`)
  - `sort_order` (`integer()->default(0)`)
  - `status` (`boolean()->default(true)`)
  - `timestamps()`

### ۳. سرویس بهینه‌سازی تصویر (`App\Services\ImageOptimizationService`) و آبزرورها
- پردازش تصویر با افزونه `GD` در PHP بدون نیاز به پکیج خارجی اضافه:
  - خواندن فایل ذخیره‌شده از دیسک `gallery` و اصلاح جهت EXIF (برای JPEG).
  - ریسایز متناسب تا حداکثر `1920px` برای نسخه نمایشی و `600px` برای بندانگشتی.
  - حفظ شفافیت PNG؛ ذخیره تصاویر بدون شفافیت به صورت WebP (در صورت پشتیبانی GD سرور) یا JPEG بهینه (`quality=82`).
  - برای GIF متحرک: حفظ انیمیشن فایل اصلی و تولید بندانگشتی ایستا؛ برای فرمت‌هایی که GD سیستم کدک رمزگشایی آن‌ها را ندارد (مثلاً WebP/AVIF در کانتینر بدون libwebp/libavif): نگهداری امن فایل اصلی بدون خطا و fallback بندانگشتی.
- آبزرورهای `PhotoObserver` و `PhotoAlbumObserver`:
  - در رویداد `saving` هنگام تغییر تصویر، فایل بهینه‌سازی و بندانگشتی تولید می‌شود و فایل خام/قبلی پاکسازی می‌گردد.
  - در رویداد `deleted` فایل‌های اصلی و بندانگشتی از دیسک `gallery` حذف می‌شوند.

### ۴. پنل مدیریت Laravel Nova
- منابع `App\Nova\PhotoAlbum` و `App\Nova\Photo` و ثبت در `NovaServiceProvider` (زیرمجموعه منوی «محتوی و کتب» یا «وب سایت» — قرارگیری «آلبوم تصاویر» و «تصاویر» در منوی سایت).
- فیلد `title` در `PhotoAlbum` کاملاً اختیاری (`nullable`) و قابل ویرایش؛ نمایش «آلبوم بدون نام» هنگام خالی بودن.
- فیلد `image` در `Photo` با قوانین `mimes:jpg,jpeg,png,webp,gif,bmp,avif` و `max:2048`، همراه با فیلد `caption` برای توضیح تصویر.
- افزودن ترجمه‌های فارسی به `lang/vendor/nova/fa.json` و `lang/fa/validation.php` (در صورت نیاز).

### ۵. بخش عمومی سایت
- مسیرها در `routes/web.php`:
  - `GET /images` (`images.index`)
  - `GET /images/{id}` (`images.show`)
- افزودن لینک **«تصاویر»** در منوی دسکتاپ و موبایل (`resources/views/site/partials/header.blade.php`).
- کنترلر `App\Http\Controllers\PhotoGalleryController`:
  - لیست آلبوم‌های منتشرشده با جستجو (`q`)، مرتب‌سازی (`-id`, `+id`, `-views`, `+title`, `-title`, `+sort_order`) و صفحه‌بندی ۱۲تایی.
  - صفحه آلبوم با شمارنده بازدید یکتا در نشست (`viewed_photo_album_{id}`) و لیست تصاویر منتشرشده با صفحه‌بندی ۲۴تایی.
- نماهای `resources/views/site/images/index.blade.php` و `resources/views/site/images/show.blade.php`:
  - رندر بندانگشتی‌ها با `loading="lazy"` و `decoding="async"`.
  - باز شدن نسخه بهینه‌شده در Fancybox همراه با `data-caption` و غیرفعال کردن دکمه `download` در تنظیمات Fancybox.
