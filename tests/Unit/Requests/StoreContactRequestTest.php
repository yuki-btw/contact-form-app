<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreContactRequest;
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
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'category_id' => Category::factory()->create()->id,
            'detail' => 'お問い合わせ内容です',
            'tag_ids' => Tag::factory()->count(2)->create()->pluck('id')->all(),
        ], $overrides);
    }

    public function test_passes_with_all_required_fields_and_tags(): void
    {
        $this->assertTrue($this->validate($this->validData())->passes());
    }

    public function test_building_and_tags_are_optional(): void
    {
        $data = $this->validData(['building' => null]);
        unset($data['tag_ids']);

        $this->assertTrue($this->validate($data)->passes());
    }

    public function test_returns_japanese_messages_when_required_fields_are_missing(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertSame('姓を入力してください', $errors->first('first_name'));
        $this->assertSame('名を入力してください', $errors->first('last_name'));
        $this->assertSame('性別を選択してください', $errors->first('gender'));
        $this->assertSame('メールアドレスを入力してください', $errors->first('email'));
        $this->assertSame('電話番号を入力してください', $errors->first('tel'));
        $this->assertSame('住所を入力してください', $errors->first('address'));
        $this->assertSame('お問い合わせの種類を選択してください', $errors->first('category_id'));
        $this->assertSame('お問い合わせ内容を入力してください', $errors->first('detail'));
    }

    /**
     * @dataProvider invalidTelProvider
     */
    public function test_rejects_invalid_tel_format(string $tel): void
    {
        $validator = $this->validate($this->validData(['tel' => $tel]));

        $this->assertTrue($validator->fails());
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $validator->errors()->first('tel'));
    }

    public static function invalidTelProvider(): array
    {
        return [
            'ハイフンあり' => ['090-1234-5678'],
            '9桁' => ['090123456'],
            '12桁' => ['090123456789'],
            '数字以外' => ['0901234567a'],
        ];
    }

    public function test_accepts_ten_digit_tel(): void
    {
        $this->assertTrue($this->validate($this->validData(['tel' => '0312345678']))->passes());
    }

    public function test_rejects_invalid_email(): void
    {
        $validator = $this->validate($this->validData(['email' => 'invalid-email']));

        $this->assertSame('メールアドレスはメール形式で入力してください', $validator->errors()->first('email'));
    }

    public function test_rejects_detail_longer_than120_characters(): void
    {
        $this->assertTrue($this->validate($this->validData(['detail' => str_repeat('あ', 120)]))->passes());

        $validator = $this->validate($this->validData(['detail' => str_repeat('あ', 121)]));
        $this->assertSame('お問い合わせ内容は120文字以内で入力してください', $validator->errors()->first('detail'));
    }

    public function test_rejects_invalid_gender_category_and_tags(): void
    {
        $validator = $this->validate($this->validData([
            'gender' => 4,
            'category_id' => 9999,
            'tag_ids' => [9999],
        ]));

        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
        $this->assertSame('選択されたタグが存在しません', $validator->errors()->first('tag_ids.0'));
    }
}
