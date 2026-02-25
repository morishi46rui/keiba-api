<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jockey extends Model
{
    /**
     * データベーステーブル名
     *
     * @var string
     */
    protected $table = 'jockeys';

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'netkeiba_id',
        'name',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * netkeiba_idで検索し、なければ作成する
     */
    public static function findOrCreateByUniqueKey(array $data): self
    {
        return self::firstOrCreate(
            ['netkeiba_id' => $data['netkeiba_id']],
            $data
        );
    }
}
