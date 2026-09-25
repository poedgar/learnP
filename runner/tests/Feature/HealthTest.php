<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_check_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_the_runner_serves_no_web_pages(): void
    {
        $this->getJson('/')->assertNotFound();
    }
}
