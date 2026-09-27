<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    private function csvRows(string $content): array
    {
        $lines = array_filter(explode("\n", substr($content, 3)));

        return array_values(array_map('str_getcsv', $lines));
    }

    public function test_exports_all_contacts_newest_first_with_bom(): void
    {
        $category = Category::factory()->create(['content' => 'その他']);
        $old = Contact::factory()->create(['category_id' => $category->id, 'gender' => 1, 'created_at' => '2026-01-01 09:00:00']);
        $new = Contact::factory()->create(['category_id' => $category->id, 'gender' => 2, 'created_at' => '2026-02-01 09:00:00']);

        $response = $this->actingAs(User::factory()->create())->get('/contacts/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $rows = $this->csvRows($content);
        $this->assertSame(['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時'], $rows[0]);
        $this->assertCount(3, $rows);
        $this->assertSame((string) $new->id, $rows[1][0]);
        $this->assertSame((string) $old->id, $rows[2][0]);
        $this->assertSame("{$new->first_name} {$new->last_name}", $rows[1][1]);
        $this->assertSame('女性', $rows[1][2]);
        $this->assertSame('その他', $rows[1][7]);
        $this->assertSame('2026-02-01 09:00:00', $rows[1][9]);
    }

    public function test_exports_only_matching_contacts(): void
    {
        $target = Contact::factory()->create(['first_name' => '山田', 'gender' => 1]);
        Contact::factory()->create(['first_name' => '山田', 'gender' => 2]);
        Contact::factory()->create(['first_name' => '佐藤', 'gender' => 1]);

        $response = $this->actingAs(User::factory()->create())->get('/contacts/export?keyword=山田&gender=1');

        $rows = $this->csvRows($response->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame((string) $target->id, $rows[1][0]);
    }

    public function test_invalid_filter_returns_validation_error(): void
    {
        $response = $this->actingAs(User::factory()->create())->from('/admin')->get('/contacts/export?gender=9');

        $response->assertRedirect('/admin');
        $response->assertSessionHasErrors('gender');
    }

    public function test_guest_cannot_export(): void
    {
        $this->get('/contacts/export')->assertRedirect('/login');
    }
}
