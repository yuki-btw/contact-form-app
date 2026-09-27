<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    public const GENDER_LABELS = [
        1 => '男性',
        2 => '女性',
        3 => 'その他',
    ];

    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    protected $casts = [
        'gender' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function getGenderLabelAttribute(): string
    {
        return self::GENDER_LABELS[$this->gender] ?? '';
    }

    /**
     * 管理画面・CSV・APIで共通の検索条件を適用する
     *
     * @param  array{keyword?: ?string, gender?: int|string|null, category_id?: int|string|null, date?: ?string}  $filters
     */
    public function scopeSearch(Builder $query, array $filters): Builder
    {
        $keyword = $filters['keyword'] ?? null;
        $gender = (int) ($filters['gender'] ?? 0);
        $categoryId = $filters['category_id'] ?? null;
        $date = $filters['date'] ?? null;

        return $query
            ->when($keyword, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when($gender !== 0, fn (Builder $query) => $query->where('gender', $gender))
            ->when($categoryId, fn (Builder $query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($date, fn (Builder $query, string $date) => $query->whereDate('created_at', $date));
    }
}
