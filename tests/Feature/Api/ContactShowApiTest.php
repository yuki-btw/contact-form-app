<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactShowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_contact_with_category_and_tags(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $contact = Contact::factory()->create(['category_id' => $category->id]);
        $tag = Tag::factory()->create(['name' => '不具合報告']);
        $contact->tags()->attach($tag);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.first_name', $contact->first_name);
        $response->assertJsonPath('data.category', ['id' => $category->id, 'content' => '商品トラブル']);
        $response->assertJsonPath('data.tags', [['id' => $tag->id, 'name' => '不具合報告']]);
    }

    public function test_returns404_json_for_unknown_id(): void
    {
        $response = $this->getJson('/api/v1/contacts/9999');

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }
}
