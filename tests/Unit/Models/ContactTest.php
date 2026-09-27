<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertTrue($contact->category->is($category));
    }

    public function test_contact_can_sync_multiple_tags(): void
    {
        $contact = Contact::factory()->create();
        [$first, $second, $third] = Tag::factory()->count(3)->create();

        $contact->tags()->sync([$first->id, $second->id]);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $contact->fresh()->tags->pluck('id')->all());

        $contact->tags()->sync([$third->id]);
        $this->assertEquals([$third->id], $contact->fresh()->tags->pluck('id')->all());
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $first->id]);
    }

    public function test_gender_label_returns_text(): void
    {
        $this->assertSame('男性', Contact::factory()->make(['gender' => 1])->gender_label);
        $this->assertSame('女性', Contact::factory()->make(['gender' => 2])->gender_label);
        $this->assertSame('その他', Contact::factory()->make(['gender' => 3])->gender_label);
    }

    public function test_deleting_contact_cascades_pivot_records(): void
    {
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->create());

        $contact->delete();

        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
    }
}
