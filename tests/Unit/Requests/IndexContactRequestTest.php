<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\IndexContactRequest;
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

    public function test_accepts_keyword_gender_category_and_date_filters(): void
    {
        $validator = $this->validate([
            'keyword' => '山田',
            'gender' => 2,
            'category_id' => Category::factory()->create()->id,
            'date' => '2026-01-01',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_accepts_empty_filters_and_gender_zero(): void
    {
        $this->assertTrue($this->validate([])->passes());
        $this->assertTrue($this->validate(['gender' => 0])->passes());
    }

    public function test_rejects_invalid_gender(): void
    {
        $validator = $this->validate(['gender' => 4]);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_rejects_unknown_category_and_invalid_date(): void
    {
        $validator = $this->validate(['category_id' => 9999, 'date' => 'not-a-date']);

        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
        $this->assertTrue($validator->errors()->has('date'));
    }

    public function test_rejects_keyword_longer_than255_characters(): void
    {
        $this->assertTrue($this->validate(['keyword' => str_repeat('a', 256)])->fails());
    }
}
