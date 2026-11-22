<?php

use App\Models\BookContent;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>ایندکس مجدد BookContent با book_title</h2><hr>";

try {
    BookContent::query()
        ->with('book')                    // ← مهم: رابطه book را لود کن
        ->select(['id', 'book_id', 'content'])   // title کتاب را از رابطه می‌گیریم
        ->chunk(300, function ($items) {
            $items->searchable();
            echo "✅ پردازش شد: " . $items->count() . " رکورد<br>";
            flush();
        });

    echo "<hr><strong>✅ ایندکس کردن با موفقیت تمام شد!</strong><br>";
    echo "<a href='/search'>→ برو به صفحه جستجو</a>";

} catch (\Exception $e) {
    echo "❌ خطا: " . $e->getMessage();
}