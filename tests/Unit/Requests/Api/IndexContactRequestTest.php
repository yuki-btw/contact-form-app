<?php

namespace Tests\Unit\Requests\Api;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new IndexContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_accepts_all_filters_and_pagination(): void
    {
        $validator = $this->validate([
            'keyword' => '田中',
            'gender' => 1,
            'category_id' => Category::factory()->create()->id,
            'date' => '2026-01-15',
            'page' => 2,
            'per_page' => 100,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_gender_zero(): void
    {
        $validator = $this->validate(['gender' => 0]);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_rejects_unknown_category_and_invalid_date(): void
    {
        $validator = $this->validate(['category_id' => 9999, 'date' => '2026-13-45']);

        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
        $this->assertTrue($validator->errors()->has('date'));
    }

    /**
     * @dataProvider invalidPaginationProvider
     */
    public function test_rejects_out_of_range_pagination(array $data, string $field): void
    {
        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has($field));
    }

    public static function invalidPaginationProvider(): array
    {
        return [
            'per_page 0' => [['per_page' => 0], 'per_page'],
            'per_page 101' => [['per_page' => 101], 'per_page'],
            'per_page 文字列' => [['per_page' => 'abc'], 'per_page'],
            'page 0' => [['page' => 0], 'page'],
        ];
    }
}
