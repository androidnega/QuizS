<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_returns_200(): void
    {
        $response = $this->get(route('student.landing'));
        $response->assertStatus(200);
    }

    public function test_landing_page_contains_start_quiz_form(): void
    {
        $response = $this->get(route('student.landing'));
        $response->assertSee('Start Quiz', false);
        $response->assertSee('link', false);
    }
}
