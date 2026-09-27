<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_shows_categories_and_tags(): void
    {
        $category = Category::factory()->create(['content' => '商品のお届けについて']);
        $tag = Tag::factory()->create(['name' => '要望']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('contact.index');
        $response->assertViewHas('categories', fn ($categories) => $categories->contains($category));
        $response->assertViewHas('tags', fn ($tags) => $tags->contains($tag));
        $response->assertSee('商品のお届けについて');
        $response->assertSee('要望');
    }

    public function test_thanks_page_is_displayed(): void
    {
        $response = $this->get('/thanks');

        $response->assertOk();
        $response->assertViewIs('contact.thanks');
        $response->assertSee('お問い合わせありがとうございました');
    }
}
