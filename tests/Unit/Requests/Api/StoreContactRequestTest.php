<?php

namespace Tests\Unit\Requests\Api;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'user@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'category_id' => Category::factory()->create()->id,
            'detail' => '商品の配送日について',
            'tag_ids' => Tag::factory()->count(2)->create()->pluck('id')->all(),
        ], $overrides);
    }

    public function test_accepts_all_required_fields_and_tags(): void
    {
        $this->assertTrue($this->validate($this->validData())->passes());
    }

    public function test_rejects_invalid_values_with_japanese_messages(): void
    {
        $validator = $this->validate($this->validData([
            'first_name' => '',
            'gender' => 9,
            'email' => 'invalid',
            'tel' => '03-1234-5678',
            'category_id' => 9999,
            'detail' => str_repeat('a', 121),
            'tag_ids' => [9999],
        ]));

        $errors = $validator->errors();
        $this->assertSame('姓を入力してください', $errors->first('first_name'));
        $this->assertSame('性別の値が不正です', $errors->first('gender'));
        $this->assertSame('メールアドレスはメール形式で入力してください', $errors->first('email'));
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $errors->first('tel'));
        $this->assertSame('選択されたカテゴリーが存在しません', $errors->first('category_id'));
        $this->assertSame('お問い合わせ内容は120文字以内で入力してください', $errors->first('detail'));
        $this->assertSame('選択されたタグが存在しません', $errors->first('tag_ids.0'));
    }

    public function test_rejects_non_array_tag_ids(): void
    {
        $this->assertTrue($this->validate($this->validData(['tag_ids' => 'abc']))->errors()->has('tag_ids'));
    }

    public function test_update_request_has_same_rules_as_store_request(): void
    {
        $this->assertEquals((new StoreContactRequest)->rules(), (new UpdateContactRequest)->rules());
    }
}
