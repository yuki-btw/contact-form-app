<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_belongs_to_many_contacts_through_pivot(): void
    {
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(2)->create();
        foreach ($contacts as $contact) {
            $contact->tags()->attach($tag);
        }

        $this->assertCount(2, $tag->contacts);
        $this->assertEqualsCanonicalizing($contacts->pluck('id')->all(), $tag->contacts->pluck('id')->all());
        $this->assertDatabaseCount('contact_tag', 2);
    }
}
