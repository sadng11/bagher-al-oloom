<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class AddSiteContent extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\SiteContent::create([
            'type' => SiteContent::ABOUT_USE,
            'content' => '<p>متن درباره ما</p>',
            'published' => true,
        ]);
        \App\Models\SiteContent::create([
            'type' => SiteContent::ADVICE,
            'content' => '<p>متن توصیه مهم از بزرگان دین</p>',
            'published' => true,
        ]);
    }
}
