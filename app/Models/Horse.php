<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horse extends Model
{
    /**
     * データベーステーブル名
     *
     * @var string
     */
    protected $table = 'horses';

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'netkeiba_id',
        'name',
        'sex',
        'coat_color',
        'birth_year',
        'sire',
        'dam',
        'sire_of_dam',
        'sire_sire',
        'dam_dam',
        'trainer_id',
        'owner_id',
        'breeder_id',
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
