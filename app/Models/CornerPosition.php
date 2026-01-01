<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CornerPosition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'race_info_id',
        'corner_number',
        'position_text',
    ];

    /**
     * レース情報とのリレーション
     */
    public function raceInfo(): BelongsTo
    {
        return $this->belongsTo(RaceInfo::class);
    }
}
