<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaceInfo extends Model
{
    /**
     * データベーステーブル名
     *
     * @var string
     */
    protected $table = 'race_infos';

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'race_name',
        'surface',
        'distance',
        'weather',
        'surface_state',
        'race_start',
        'race_number',
        'date',
        'place_detail',
        'race_class',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'race_start' => 'datetime:H:i',
        'distance' => 'integer',
        'race_number' => 'integer',
    ];

    /**
     * レース結果とのリレーション
     */
    public function raceResults()
    {
        return $this->hasMany(RaceResult::class);
    }

    /**
     * ユニークキーでレース情報を検索または作成
     */
    public static function findOrCreateByUniqueKey(array $data): self
    {
        return self::firstOrCreate(
            [
                'date' => $data['date'],
                'race_number' => $data['race_number'],
                'place_detail' => $data['place_detail'],
            ],
            $data
        );
    }
}
