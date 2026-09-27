<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'hanako@example.com',
            'tel' => '0312345678',
            'address' => '大阪府大阪市1-2-3',
            'building' => null,
            'category_id' => Category::factory()->create()->id,
            'detail' => '更新後の内容',
        ], $overrides);
    }

    public function test_updates_contact_syncs_tags_and_returns200(): void
    {
        $contact = Contact::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $contact->tags()->attach($oldTag);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $this->payload(['tag_ids' => [$newTag->id]]));

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.first_name', '佐藤');
        $response->assertJsonPath('data.tags', [['id' => $newTag->id, 'name' => $newTag->name]]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'email' => 'hanako@example.com']);
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $oldTag->id]);
    }

    public function test_returns404_for_unknown_id(): void
    {
        $response = $this->putJson('/api/v1/contacts/9999', $this->payload());

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }

    public function test_returns422_for_invalid_data(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $this->payload(['email' => 'invalid', 'detail' => '']));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email.0', 'メールアドレスはメール形式で入力してください');
        $response->assertJsonPath('errors.detail.0', 'お問い合わせ内容を入力してください');
    }
}
