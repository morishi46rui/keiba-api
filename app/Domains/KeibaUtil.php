<?php

namespace App\Domains;

/**
 * 競馬データの正規化とユーティリティ機能を提供するクラス
 */
class KeibaUtil
{
    /**
     * 天候の種類
     *
     * @var array<string>
     */
    private const WEATHER_STATES = ['晴', '曇', '雨', '小雨', '雪'];

    /**
     * 馬場の種類
     *
     * @var array<string>
     */
    private const SURFACE_STATES = ['芝', 'ダ'];

    /**
     * 性別の種類
     *
     * @var array<string>
     */
    private const SEX_STATES = ['牡', '牝', 'セ'];

    /**
     * コースの種類
     *
     * @var array<string>
     */
    private const COURSE_STATES = ['直線', '右', '左', '外'];

    /**
     * 着差の種類
     *
     * @var array<string>
     */
    private const MARGIN_STATES = ['ハナ', 'クビ', 'アタマ'];

    /**
     * 馬券の種類
     *
     * @var array<string>
     */
    private const TICKET_TYPES = [
        '単勝',
        '複勝',
        '枠連',
        '馬連',
        'ワイド',
        '馬単',
        '三連複',
        '三連単',
    ];

    /**
     * クラスの種類
     *
     * @var array<string>
     */
    private const CLASS_TYPES = [
        'オープン',
        '1600万下',
        '1000万下',
        '500万下',
        '未勝利',
        '新馬',
    ];

    /**
     * 着順状態の種類
     *
     * @var array<string>
     */
    private const POSITION_STATES = [
        '(降)',
        '(再)',
        '中',
        '取',
        '失',
        '除',
    ];

    /**
     * 馬場状態の種類
     *
     * @var array<string>
     */
    private const SURFACE_CONDITIONS = [
        'ダート : 稍重',
        'ダート : 重',
        'ダート : 良',
        'ダート : 不良',
        '芝 : 良',
        '芝 : 稍重',
        '芝 : 重',
        '芝 : 不良',
    ];

    /**
     * 競馬場コードと名前のマッピング
     *
     * @var array<string, string>
     */
    private const TRACK_NAMES = [
        '01' => '札幌',
        '02' => '函館',
        '03' => '福島',
        '04' => '新潟',
        '05' => '東京',
        '06' => '中山',
        '07' => '中京',
        '08' => '京都',
        '09' => '阪神',
        '10' => '小倉',
    ];

    /**
     * 天候を正規化
     */
    public static function normalizeWeather(string $weather): string
    {
        foreach (self::WEATHER_STATES as $state) {
            if ($weather === $state) {
                return $state;
            }
        }
        return '他';
    }

    /**
     * 馬場を正規化
     */
    public static function normalizeSurface(string $surface): string
    {
        foreach (self::SURFACE_STATES as $state) {
            if (str_contains($surface, $state)) {
                return $state;
            }
        }
        return '他';
    }

    /**
     * 性別を正規化
     */
    public static function normalizeSex(string $sex): string
    {
        foreach (self::SEX_STATES as $state) {
            if ($sex === $state) {
                return $state;
            }
        }
        return '他';
    }

    /**
     * コースを正規化
     */
    public static function normalizeCourse(string $course): string
    {
        foreach (self::COURSE_STATES as $state) {
            if (str_contains($course, $state)) {
                return $state;
            }
        }
        return '他';
    }

    /**
     * 着差を正規化
     */
    public static function normalizeMargin(string $margin): string
    {
        foreach (self::MARGIN_STATES as $state) {
            if (str_contains($margin, $state)) {
                return $state;
            }
        }
        return '他';
    }

    /**
     * 馬券の種類を文字列から番号に変換
     */
    public static function ticketTypeToInt(string $ticketType): int
    {
        $index = array_search($ticketType, self::TICKET_TYPES, true);
        if ($index === false) {
            throw new \InvalidArgumentException("不明な馬券の種類: {$ticketType}");
        }
        return $index;
    }

    /**
     * クラスを文字列から番号に変換
     */
    public static function classToInt(string $class): int
    {
        $normalized = self::normalizeClass($class);
        foreach (self::CLASS_TYPES as $index => $type) {
            if (str_contains($normalized, $type)) {
                return $index;
            }
        }
        throw new \InvalidArgumentException("不明なクラス: {$class}");
    }

    /**
     * クラスを正規化（新しい表記を古い表記に変換）
     */
    public static function normalizeClass(string $class): string
    {
        $patterns = [
            '3歳以上1勝クラス' => '500万下',
            '4歳以上1勝クラス' => '500万下',
            '3歳1勝クラス' => '500万下',
            '2歳1勝クラス' => '500万下',
            '3歳以上2勝クラス' => '1000万下',
            '4歳以上2勝クラス' => '1000万下',
            '3歳2勝クラス' => '1000万下',
            '4歳以上3勝クラス' => '1600万下',
            '3歳以上3勝クラス' => '1600万下',
            '3歳3勝クラス' => '1600万下',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (str_contains($class, $pattern)) {
                return $class . $replacement;
            }
        }

        return $class;
    }

    /**
     * 着順とラベルを分解
     *
     * @return array{position: ?int, label: ?int}
     */
    public static function parsePosition(string $position): array
    {
        foreach (self::POSITION_STATES as $index => $state) {
            if (str_contains($position, $state)) {
                $pos = (int) str_replace($state, '', $position);
                return [
                    'position' => $pos > 0 ? $pos : null,
                    'label' => $index,
                ];
            }
        }

        // ラベルなしの場合
        $cleaned = preg_replace('/[^\d]/', '', $position);
        $pos = $cleaned !== '' ? (int) $cleaned : null;

        return [
            'position' => $pos,
            'label' => null,
        ];
    }

    /**
     * レースIDから競馬場コードを抽出
     */
    public static function parseTrackCode(string $raceId): string
    {
        if (strlen($raceId) >= 6) {
            return substr($raceId, 4, 2);
        }
        return 'unknown';
    }

    /**
     * 競馬場コードから競馬場名を取得
     */
    public static function getTrackName(string $trackCode): string
    {
        return self::TRACK_NAMES[$trackCode] ?? "track{$trackCode}";
    }

    /**
     * レースURLを構築
     */
    public static function buildRaceUrl(string $raceId): string
    {
        return "https://race.netkeiba.com/race/shutuba.html?race_id={$raceId}";
    }

    /**
     * レースIDのバリデーション
     */
    public static function isValidRaceId(string $raceId): bool
    {
        return preg_match('/^[0-9]{12}$/', $raceId) === 1;
    }
}
