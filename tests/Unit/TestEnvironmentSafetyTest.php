<?php

namespace Tests\Unit;

use Tests\TestCase;

class TestEnvironmentSafetyTest extends TestCase
{
    public function test_test_suite_is_forced_to_an_in_memory_database(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('null', config('scout.driver'));
    }
}
