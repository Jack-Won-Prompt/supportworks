<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedFileCategory extends Model
{
    public const MAX_DEPTH = 3;

    protected $fillable = [
        'company_group_id', 'parent_id', 'user_id', 'name', 'color', 'sort_order',
    ];

    /** 개인 폴더 여부 (user_id 가 설정되어 있으면 해당 사용자의 개인 폴더) */
    public function isPersonal(): bool
    {
        return $this->user_id !== null;
    }

    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(SharedFile::class, 'category_id');
    }

    /** 루트부터 본인까지의 깊이 (루트=1, 손자=3) */
    public function depth(): int
    {
        $d = 1;
        $p = $this->parent;
        while ($p) {
            $d++;
            $p = $p->parent;
            if ($d > self::MAX_DEPTH + 5) break; // 순환 안전장치
        }
        return $d;
    }
}
