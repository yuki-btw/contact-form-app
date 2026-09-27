<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_list_is_paginated_by_seven(): void
    {
        Contact::factory()->count(8)->create();

        $response = $this->actingAs($this->user)->get('/admin');
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->count() === 7 && $contacts->total() === 8);

        $response = $this->actingAs($this->user)->get('/admin?page=2');
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->count() === 1);
    }

    public function test_keyword_matches_name_and_email_partially(): void
    {
        $byName = Contact::factory()->create(['first_name' => '山田', 'last_name' => '太郎', 'email' => 'a@example.com']);
        $byEmail = Contact::factory()->create(['first_name' => '佐藤', 'last_name' => '花子', 'email' => 'yamada@example.com']);
        $other = Contact::factory()->create(['first_name' => '鈴木', 'last_name' => '一郎', 'email' => 'b@example.com']);

        $response = $this->actingAs($this->user)->get('/admin?keyword=山田');
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$byName->id]);

        $response = $this->actingAs($this->user)->get('/admin?keyword=太郎');
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$byName->id]);

        $response = $this->actingAs($this->user)->get('/admin?keyword=yamada');
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$byEmail->id]);
        $response->assertDontSee($other->email);
    }

    public function test_filters_by_gender_category_and_date(): void
    {
        $category = Category::factory()->create();
        $target = Contact::factory()->create([
            'gender' => 2,
            'category_id' => $category->id,
            'created_at' => '2026-01-15 10:00:00',
        ]);
        Contact::factory()->create(['gender' => 1, 'category_id' => $category->id, 'created_at' => '2026-01-15 10:00:00']);
        Contact::factory()->create(['gender' => 2, 'created_at' => '2026-01-15 10:00:00']);
        Contact::factory()->create(['gender' => 2, 'category_id' => $category->id, 'created_at' => '2026-01-16 10:00:00']);

        $response = $this->actingAs($this->user)->get('/admin?'.http_build_query([
            'keyword' => '',
            'gender' => 2,
            'category_id' => $category->id,
            'date' => '2026-01-15',
        ]));

        $response->assertOk();
        $response->assertViewHas('contacts', fn ($contacts) => $contacts->pluck('id')->all() === [$target->id]);
    }

    public function test_gender_zero_matches_all_genders(): void
    {
        Contact::factory()->create(['gender' => 1]);
        Contact::factory()->create(['gender' => 2]);
        Contact::factory()->create(['gender' => 3]);

        $response = $this->actingAs($this->user)->get('/admin?gender=0');

        $response->assertViewHas('contacts', fn ($contacts) => $contacts->total() === 3);
    }

    public function test_invalid_filter_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)->from('/admin')->get('/admin?gender=9');

        $response->assertRedirect('/admin');
        $response->assertSessionHasErrors(['gender' => '性別の値が不正です']);
    }

    public function test_show_displays_contact_with_category(): void
    {
        $category = Category::factory()->create(['content' => '商品の交換について']);
        $contact = Contact::factory()->create(['category_id' => $category->id, 'gender' => 3]);
        $contact->tags()->attach(Tag::factory()->create(['name' => 'ご意見']));

        $response = $this->actingAs($this->user)->get("/admin/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertViewIs('admin.show');
        $response->assertViewHas('contact', fn ($viewContact) => $viewContact->is($contact) && $viewContact->relationLoaded('category'));
        $response->assertSee('商品の交換について');
        $response->assertSee('その他');
        $response->assertSee('ご意見');
        $response->assertSee($contact->email);
    }

    public function test_destroy_deletes_contact_and_redirects(): void
    {
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->create());

        $response = $this->actingAs($this->user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');
        $this->assertModelMissing($contact);
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
    }

    public function test_guest_cannot_view_or_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $this->get("/admin/contacts/{$contact->id}")->assertRedirect('/login');
        $this->delete("/admin/contacts/{$contact->id}")->assertRedirect('/login');
        $this->assertModelExists($contact);
    }
}
