<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(Tag $tag, array $data): \Illuminate\Validation\Validator
    {
        $request = new UpdateTagRequest;
        $request->setRouteResolver(function () use ($tag) {
            $route = new Route('PUT', 'admin/tags/{tag}', []);
            $route->bind(Request::create("/admin/tags/{$tag->id}", 'PUT'));
            $route->setParameter('tag', $tag);

            return $route;
        });

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_allows_keeping_own_current_name(): void
    {
        $tag = Tag::factory()->create(['name' => '要望']);

        $this->assertTrue($this->validate($tag, ['name' => '要望'])->passes());
    }

    public function test_rejects_name_used_by_another_tag(): void
    {
        Tag::factory()->create(['name' => '質問']);
        $tag = Tag::factory()->create(['name' => '要望']);

        $validator = $this->validate($tag, ['name' => '質問']);

        $this->assertTrue($validator->fails());
        $this->assertSame('そのタグ名は既に使用されています', $validator->errors()->first('name'));
    }

    public function test_rejects_empty_and_too_long_name(): void
    {
        $tag = Tag::factory()->create();

        $this->assertSame('タグ名を入力してください', $this->validate($tag, ['name' => ''])->errors()->first('name'));
        $this->assertSame('タグ名は50文字以内で入力してください', $this->validate($tag, ['name' => str_repeat('あ', 51)])->errors()->first('name'));
    }
}
