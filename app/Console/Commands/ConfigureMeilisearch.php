<?php

namespace App\Console\Commands;

use App\Models\BookContent;
use Illuminate\Console\Command;
use Meilisearch\Client;

class ConfigureMeilisearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meilisearch:configure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اعمال تنظیمات جستجوی دقیق و اعراب روی ایندکس BookContent';

    protected array $nonSeparatorTokens = [
        'ّ', // شدّه / تشدید
        'ْ', // سکون
        'َ', // فتحه
        'ِ', // کسره
        'ُ', // ضمه
        'ً', // تنوین فتح
        'ٍ', // تنوین کسر
        'ٌ', // تنوین ضم
        'ٰ', // الف خنجری
        'ـ', // تطویل (کشیده)
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key'),
        );

        $indexUid = (new BookContent())->searchableAs();
        $index = $client->index($indexUid);

        $this->info("در حال اعمال تنظیمات روی ایندکس [{$indexUid}] ...");

        $settings = config("scout.meilisearch.index-settings.{$indexUid}", []);
        $settings['nonSeparatorTokens'] = $this->nonSeparatorTokens;

        $task = $index->updateSettings($settings);

        $taskUid = $task['taskUid'];
        $this->info("درخواست ثبت شد (taskUid: {$taskUid}). در حال بررسی وضعیت ...");

        // منتظر می‌مانیم تا تسک به نتیجه برسد
        $status = null;
        $attempts = 0;

        while ($attempts < 20) {
            $result = $client->getTask($taskUid);
            $status = $result['status'];

            if (in_array($status, ['succeeded', 'failed'], true)) {
                break;
            }

            $attempts++;
            usleep(500_000); // نیم ثانیه صبر
        }

        if ($status === 'succeeded') {
            $this->info('تنظیمات با موفقیت اعمال شد. ✅');
            $this->line('typo tolerance: disabled');
            $this->line('non-separator-tokens فعلی: ' . implode(', ', $index->getNonSeparatorTokens()));

            return self::SUCCESS;
        }

        $this->error("عملیات ناموفق بود یا در زمان انتظار به پایان نرسید. وضعیت فعلی: {$status}");
        $this->line(json_encode($result ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::FAILURE;
    }
}
