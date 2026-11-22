<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_search_page_returns_a_successful_response()
    {
        $response = $this->get('/search');

        $response->assertStatus(200);
    }
}
