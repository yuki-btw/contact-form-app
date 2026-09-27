<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new ExportContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_accepts_valid_filters(): void
    {
        $validator = $this->validate([
            'keyword' => 'test@example.com',
            'gender' => 3,
            'category_id' => Category::factory()->create()->id,
            'date' => '2026-09-27',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_invalid_gender(): void
    {
        $validator = $this->validate(['gender' => 5]);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_rejects_unknown_category_id(): void
    {
        $validator = $this->validate(['category_id' => 9999]);

        $this->assertTrue($validator->fails());
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
    }
}
