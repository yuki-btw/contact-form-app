<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_contact_and_returns201(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'user@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'category_id' => $category->id,
            'detail' => '商品の配送日について',
            'tag_ids' => $tags->pluck('id')->all(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.first_name', '山田');
        $response->assertJsonPath('data.category.id', $category->id);
        $response->assertJsonCount(2, 'data.tags');
        $this->assertDatabaseHas('contacts', ['email' => 'user@example.com', 'tel' => '09012345678']);
        $this->assertSame(2, Contact::first()->tags()->count());
    }

    public function test_returns422_with_japanese_messages(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'gender' => 9,
            'tel' => '090-1234-5678',
            'category_id' => 9999,
            'tag_ids' => [9999],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.first_name.0', '姓を入力してください');
        $response->assertJsonPath('errors.gender.0', '性別の値が不正です');
        $response->assertJsonPath('errors.tel.0', '電話番号はハイフンなしの10〜11桁で入力してください');
        $response->assertJsonPath('errors.category_id.0', '選択されたカテゴリーが存在しません');
        $response->assertJsonValidationErrors(['tag_ids.0' => '選択されたタグが存在しません']);
        $this->assertDatabaseCount('contacts', 0);
    }
}
