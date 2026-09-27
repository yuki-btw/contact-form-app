<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_page_shows_input(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $tag = Tag::factory()->create(['name' => '不具合報告']);

        $response = $this->post('/contacts/confirm', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 2,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
            'detail' => '商品が破損していました',
        ]);

        $response->assertOk();
        $response->assertViewIs('contact.confirm');
        $response->assertSee('山田 太郎');
        $response->assertSee('女性');
        $response->assertSee('test@example.com');
        $response->assertSee('商品トラブル');
        $response->assertSee('不具合報告');
        $response->assertSee('商品が破損していました');
    }

    public function test_split_tel_fields_are_combined(): void
    {
        $response = $this->post('/contacts/confirm', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel1' => '090',
            'tel2' => '1234',
            'tel3' => '5678',
            'address' => '東京都',
            'category_id' => Category::factory()->create()->id,
            'detail' => 'テスト',
        ]);

        $response->assertOk();
        $response->assertViewHas('validated', fn ($validated) => $validated['tel'] === '09012345678');
    }

    public function test_validation_error_redirects_back_with_errors(): void
    {
        $response = $this->from('/')->post('/contacts/confirm', []);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'first_name' => '姓を入力してください',
            'last_name' => '名を入力してください',
            'gender' => '性別を選択してください',
            'email' => 'メールアドレスを入力してください',
            'tel' => '電話番号を入力してください',
            'address' => '住所を入力してください',
            'category_id' => 'お問い合わせの種類を選択してください',
            'detail' => 'お問い合わせ内容を入力してください',
        ]);
    }
}
