<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactDestroyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_contact_and_returns204(): void
    {
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->create());

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        $this->assertModelMissing($contact);
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
    }

    public function test_returns404_for_unknown_id(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/9999');

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }
}
