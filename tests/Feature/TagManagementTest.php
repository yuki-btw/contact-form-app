<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_admin_shows_tag_list(): void
    {
        Tag::factory()->create(['name' => '要望']);

        $this->actingAs($this->user)->get('/admin')->assertSee('要望');
    }

    public function test_store_creates_tag_and_redirects(): void
    {
        $response = $this->actingAs($this->user)->post('/admin/tags', ['name' => '新機能の要望']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新機能の要望']);
    }

    public function test_store_returns_validation_errors(): void
    {
        Tag::factory()->create(['name' => '質問']);

        $this->actingAs($this->user)->from('/admin')->post('/admin/tags', ['name' => ''])
            ->assertRedirect('/admin')
            ->assertSessionHasErrors(['name' => 'タグ名を入力してください']);
        $this->actingAs($this->user)->from('/admin')->post('/admin/tags', ['name' => str_repeat('あ', 51)])
            ->assertSessionHasErrors(['name' => 'タグ名は50文字以内で入力してください']);
        $this->actingAs($this->user)->from('/admin')->post('/admin/tags', ['name' => '質問'])
            ->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);
    }

    public function test_edit_shows_current_name(): void
    {
        $tag = Tag::factory()->create(['name' => '不具合報告']);

        $response = $this->actingAs($this->user)->get("/admin/tags/{$tag->id}/edit");

        $response->assertOk();
        $response->assertViewIs('admin.tags.edit');
        $response->assertSee('value="不具合報告"', false);
    }

    public function test_update_changes_name_and_redirects(): void
    {
        $tag = Tag::factory()->create(['name' => '要望']);

        $response = $this->actingAs($this->user)->put("/admin/tags/{$tag->id}", ['name' => 'ご要望']);

        $response->assertRedirect('/admin');
        $this->assertSame('ご要望', $tag->fresh()->name);
    }

    public function test_update_rejects_duplicate_name(): void
    {
        Tag::factory()->create(['name' => '質問']);
        $tag = Tag::factory()->create(['name' => '要望']);

        $response = $this->actingAs($this->user)
            ->from("/admin/tags/{$tag->id}/edit")
            ->put("/admin/tags/{$tag->id}", ['name' => '質問']);

        $response->assertRedirect("/admin/tags/{$tag->id}/edit");
        $response->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);
        $this->assertSame('要望', $tag->fresh()->name);
    }

    public function test_destroy_deletes_tag_and_pivot_records(): void
    {
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        $contact->tags()->attach($tag);

        $response = $this->actingAs($this->user)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');
        $this->assertModelMissing($tag);
        $this->assertDatabaseMissing('contact_tag', ['tag_id' => $tag->id]);
        $this->assertModelExists($contact);
    }

    public function test_guest_cannot_manage_tags(): void
    {
        $tag = Tag::factory()->create(['name' => '要望']);

        $this->get("/admin/tags/{$tag->id}/edit")->assertRedirect('/login');
        $this->post('/admin/tags', ['name' => '新規'])->assertRedirect('/login');
        $this->put("/admin/tags/{$tag->id}", ['name' => '変更'])->assertRedirect('/login');
        $this->delete("/admin/tags/{$tag->id}")->assertRedirect('/login');

        $this->assertDatabaseMissing('tags', ['name' => '新規']);
        $this->assertSame('要望', $tag->fresh()->name);
    }
}
