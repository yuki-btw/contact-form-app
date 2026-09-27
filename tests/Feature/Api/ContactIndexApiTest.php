<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactIndexApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_paginated_json_with_meta(): void
    {
        Contact::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 2);
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonStructure([
            'data' => [[
                'id', 'category' => ['id', 'content'], 'first_name', 'last_name', 'gender', 'email',
                'tel', 'address', 'building', 'detail', 'tags', 'created_at', 'updated_at',
            ]],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_per_page_and_page_parameters(): void
    {
        Contact::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=2&page=3');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.current_page', 3);
        $response->assertJsonPath('meta.per_page', 2);
    }

    public function test_filters_by_keyword_gender_category_and_date(): void
    {
        $category = Category::factory()->create();
        $target = Contact::factory()->create([
            'first_name' => '田中',
            'gender' => 1,
            'category_id' => $category->id,
            'created_at' => '2026-01-15 12:00:00',
        ]);
        $target->tags()->attach(Tag::factory()->create(['name' => '質問']));
        Contact::factory()->create(['first_name' => '田中', 'gender' => 2, 'category_id' => $category->id, 'created_at' => '2026-01-15 12:00:00']);
        Contact::factory()->create(['first_name' => '鈴木', 'gender' => 1, 'category_id' => $category->id, 'created_at' => '2026-01-15 12:00:00']);
        Contact::factory()->create(['first_name' => '田中', 'gender' => 1, 'created_at' => '2026-01-15 12:00:00']);
        Contact::factory()->create(['first_name' => '田中', 'gender' => 1, 'category_id' => $category->id, 'created_at' => '2026-01-16 12:00:00']);

        $response = $this->getJson('/api/v1/contacts?'.http_build_query([
            'keyword' => '田中',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-01-15',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $target->id);
        $response->assertJsonPath('data.0.tags.0.name', '質問');
    }

    public function test_returns422_with_japanese_messages(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=0&category_id=9999&per_page=101');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.gender.0', '性別の値が不正です');
        $response->assertJsonPath('errors.category_id.0', '選択されたカテゴリーが存在しません');
        $response->assertJsonValidationErrors('per_page');
    }

    public function test_returns_json_errors_without_accept_header(): void
    {
        $response = $this->get('/api/v1/contacts?gender=9');

        $response->assertStatus(422);
        $response->assertJsonPath('errors.gender.0', '性別の値が不正です');
    }
}
