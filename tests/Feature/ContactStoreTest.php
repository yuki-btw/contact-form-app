<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_contact_with_tags_and_redirects_to_thanks(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $response = $this->post('/contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '',
            'category_id' => $category->id,
            'tag_ids' => $tags->pluck('id')->all(),
            'detail' => 'お問い合わせ内容',
        ]);

        $response->assertRedirect('/thanks');
        $this->assertDatabaseHas('contacts', [
            'first_name' => '山田',
            'email' => 'test@example.com',
            'category_id' => $category->id,
            'building' => null,
        ]);
        $contact = Contact::first();
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $tag->id]);
        }
    }

    public function test_validation_error_does_not_store_contact(): void
    {
        $response = $this->from('/')->post('/contacts', ['email' => 'invalid']);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
        $this->assertDatabaseCount('contacts', 0);
    }
}
