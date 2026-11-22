<?php

namespace Tests\Unit;

use Tests\TestCase;

class MeilisearchSettingsTest extends TestCase
{
    public function test_book_contents_index_disables_typo_tolerance(): void
    {
        $settings = config('scout.meilisearch.index-settings.book_contents');

        $this->assertFalse($settings['typoTolerance']['enabled']);
    }

    public function test_book_contents_index_can_be_filtered_by_book_id(): void
    {
        $settings = config('scout.meilisearch.index-settings.book_contents');

        $this->assertSame(['book_id'], $settings['filterableAttributes']);
    }
}
