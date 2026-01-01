<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaceResult extends Model
{
    /**
     * データベーステーブル名
     *
     * @var string
     */
    protected $table = 'race_results';

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'race_info_id',
        'order_of_finish',
        'frame_number',
        'horse_number',
        'horse_name',
        'sex',
        'age',
        'jockey_weight',
        'jockey_name',
        'time',
        'margin',
        'pop',
        'odds',
        'last_3F',
        'pass',
        'horse_weight',
        'stable',
        'horse_id',
        'jockey_id',
        'trainer_id',
        'owner_id',
        'position',
        'position_label',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'race_info_id' => 'integer',
        'order_of_finish' => 'integer',
        'frame_number' => 'integer',
        'horse_number' => 'integer',
        'age' => 'integer',
        'position' => 'integer',
        'position_label' => 'integer',
    ];

    /**
     * レース情報とのリレーション
     */
    public function raceInfo()
    {
        return $this->belongsTo(RaceInfo::class);
    }
}
