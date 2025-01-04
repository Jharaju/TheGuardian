<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArticleTest extends TestCase
{
    /** @test */
    public function testValidSectionNameReturnsRssFeed()
    {
        $response = $this->get('http://127.0.0.1:8004/api/articles/politics');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml');
    }

    /** @test */
    public function testInvalidSectionNameReturnsError()
    {
        $response = $this->get('http://127.0.0.1:8004/api/articles/invalid-section-name');
        $response->assertStatus(200);
        $response->assertSeeText('');
    }
}
