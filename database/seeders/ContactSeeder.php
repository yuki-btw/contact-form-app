<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id');
        $tagIds = Tag::pluck('id');

        Contact::factory()
            ->count(20)
            ->state(fn () => ['category_id' => $categoryIds->random()])
            ->create()
            ->each(function (Contact $contact) use ($tagIds) {
                $contact->tags()->attach($tagIds->random(rand(1, 3)));
            });
    }
}
