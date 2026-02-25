<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shutuba extends Model
{
    /**
     * データベーステーブル名
     *
     * @var string
     */
    protected $table = 'shutubas';

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'race_info_id',
        'frame_number',
        'horse_number',
        'horse_name',
        'horse_id',
        'sex',
        'age',
        'jockey_weight',
        'jockey_name',
        'jockey_id',
        'trainer_name',
        'trainer_id',
        'horse_weight',
        'odds',
        'pop',
    ];

    /**
     * キャストする属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'race_info_id' => 'integer',
        'frame_number' => 'integer',
        'horse_number' => 'integer',
        'age' => 'integer',
    ];

    /**
     * レース情報とのリレーション
     */
    public function raceInfo()
    {
        return $this->belongsTo(RaceInfo::class);
    }
}
