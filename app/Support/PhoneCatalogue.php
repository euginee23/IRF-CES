<?php

namespace App\Support;

/**
 * The phones the shop repairs, 2020 to 2026, and what their parts cost.
 *
 * One list feeds the parts seeder and every brand dropdown, so a brand added
 * here shows up at intake, in inventory and in the parts catalogue together.
 *
 * Models are grouped by brand and given a price tier. The tier sets the
 * catalogue price of every part generated for the model; the shop edits
 * real prices in Parts Inventory as stock comes in.
 *
 * Models marked "best-effort" were announced late or only rumoured when
 * this list was written. Check the names against what actually comes in
 * through the door and correct them in Parts Inventory.
 *
 * @see \Database\Seeders\PartsTableSeeder
 */
final class PhoneCatalogue
{
    public const BUDGET = 'budget';

    public const MID = 'mid';

    public const UPPER = 'upper';

    public const FLAGSHIP = 'flagship';

    public const PREMIUM = 'premium';

    public const FOLDABLE = 'foldable';

    /** Short code each brand's SKUs start with. */
    public const BRAND_CODES = [
        'Apple' => 'APL',
        'Samsung' => 'SAM',
        'Xiaomi' => 'XIA',
        'Oppo' => 'OPP',
        'Vivo' => 'VIV',
        'Realme' => 'RLM',
        'Infinix' => 'INF',
        'Tecno' => 'TEC',
        'Huawei' => 'HUA',
        'Honor' => 'HON',
        'OnePlus' => 'ONE',
        'Google' => 'GOO',
        'Nokia' => 'NOK',
        'Cherry Mobile' => 'CHR',
    ];

    /**
     * The part types generated for every model.
     *
     * 'aliases' are the names an older, hand-entered part of the same kind
     * might go by, so the seeder does not add a second "charging port" next
     * to an existing "Lightning Connector".
     *
     * @var array<string, array{name: string, category: string, aliases: array<int, string>}>
     */
    public const PART_TYPES = [
        'SCR' => ['name' => 'Screen Assembly', 'category' => 'Display & Input Components', 'aliases' => ['Screen Assembly', 'LCD', 'Display']],
        'BAT' => ['name' => 'Battery', 'category' => 'Power & Charging Components', 'aliases' => ['Battery']],
        'CHG' => ['name' => 'Charging Port Flex', 'category' => 'Power & Charging Components', 'aliases' => ['Charging Port', 'Lightning Connector', 'USB-C Port']],
        'BCK' => ['name' => 'Back Glass / Back Cover', 'category' => 'Structural & Physical Components', 'aliases' => ['Back Glass', 'Back Cover', 'Back Housing']],
        'RCAM' => ['name' => 'Rear Camera', 'category' => 'Camera & Audio Components', 'aliases' => ['Rear Camera']],
        'FCAM' => ['name' => 'Front Camera', 'category' => 'Camera & Audio Components', 'aliases' => ['Front Camera', 'Selfie Camera']],
        'SPK' => ['name' => 'Loudspeaker', 'category' => 'Camera & Audio Components', 'aliases' => ['Loudspeaker', 'Speaker']],
        'EAR' => ['name' => 'Earpiece Speaker', 'category' => 'Camera & Audio Components', 'aliases' => ['Earpiece']],
    ];

    /** The extra part a foldable has: the big screen inside. */
    public const FOLDABLE_INNER_SCREEN = ['code' => 'ISCR', 'name' => 'Inner Screen Assembly', 'category' => 'Display & Input Components', 'aliases' => ['Inner Screen', 'Main Screen']];

    /**
     * Catalogue price per part type and tier, in pesos: [cost, sale].
     *
     * @var array<string, array<string, array{0: float, 1: float}>>
     */
    public const PRICES = [
        'SCR' => [
            self::BUDGET => [600, 1100], self::MID => [1000, 1800], self::UPPER => [2200, 3800],
            self::FLAGSHIP => [4000, 6500], self::PREMIUM => [7000, 10500], self::FOLDABLE => [3500, 5800],
        ],
        'ISCR' => [
            self::FOLDABLE => [12000, 17500],
        ],
        'BAT' => [
            self::BUDGET => [350, 750], self::MID => [450, 900], self::UPPER => [550, 1100],
            self::FLAGSHIP => [800, 1500], self::PREMIUM => [1100, 2000], self::FOLDABLE => [1000, 1900],
        ],
        'CHG' => [
            self::BUDGET => [150, 400], self::MID => [200, 500], self::UPPER => [280, 650],
            self::FLAGSHIP => [450, 950], self::PREMIUM => [700, 1400], self::FOLDABLE => [600, 1200],
        ],
        'BCK' => [
            self::BUDGET => [150, 400], self::MID => [250, 600], self::UPPER => [400, 900],
            self::FLAGSHIP => [700, 1400], self::PREMIUM => [1200, 2200], self::FOLDABLE => [1100, 2000],
        ],
        'RCAM' => [
            self::BUDGET => [300, 700], self::MID => [500, 1000], self::UPPER => [800, 1500],
            self::FLAGSHIP => [1800, 3200], self::PREMIUM => [3500, 5500], self::FOLDABLE => [2000, 3500],
        ],
        'FCAM' => [
            self::BUDGET => [150, 400], self::MID => [220, 500], self::UPPER => [300, 700],
            self::FLAGSHIP => [600, 1200], self::PREMIUM => [1000, 1900], self::FOLDABLE => [700, 1400],
        ],
        'SPK' => [
            self::BUDGET => [100, 300], self::MID => [130, 350], self::UPPER => [160, 400],
            self::FLAGSHIP => [250, 600], self::PREMIUM => [400, 900], self::FOLDABLE => [350, 800],
        ],
        'EAR' => [
            self::BUDGET => [80, 250], self::MID => [100, 300], self::UPPER => [130, 350],
            self::FLAGSHIP => [200, 500], self::PREMIUM => [300, 700], self::FOLDABLE => [280, 650],
        ],
    ];

    /**
     * Brand => [model => tier], oldest first within each line.
     *
     * @return array<string, array<string, string>>
     */
    public static function models(): array
    {
        $b = self::BUDGET;
        $m = self::MID;
        $u = self::UPPER;
        $f = self::FLAGSHIP;
        $p = self::PREMIUM;
        $z = self::FOLDABLE;

        return [
            'Apple' => [
                'iPhone SE (2020)' => $m, 'iPhone SE (2022)' => $m,
                'iPhone 12 mini' => $u, 'iPhone 12' => $u, 'iPhone 12 Pro' => $f, 'iPhone 12 Pro Max' => $f,
                'iPhone 13 mini' => $u, 'iPhone 13' => $u, 'iPhone 13 Pro' => $f, 'iPhone 13 Pro Max' => $f,
                'iPhone 14' => $f, 'iPhone 14 Plus' => $f, 'iPhone 14 Pro' => $p, 'iPhone 14 Pro Max' => $p,
                'iPhone 15' => $f, 'iPhone 15 Plus' => $f, 'iPhone 15 Pro' => $p, 'iPhone 15 Pro Max' => $p,
                'iPhone 16e' => $u, 'iPhone 16' => $f, 'iPhone 16 Plus' => $f, 'iPhone 16 Pro' => $p, 'iPhone 16 Pro Max' => $p,
                'iPhone 17' => $f, 'iPhone Air' => $p, 'iPhone 17 Pro' => $p, 'iPhone 17 Pro Max' => $p,
                // best-effort: 2026 line-up
                'iPhone 17e' => $u,
                'iPhone 18' => $f, 'iPhone 18 Pro' => $p, 'iPhone 18 Pro Max' => $p,
            ],

            'Samsung' => [
                // S and Note
                'Galaxy S20' => $f, 'Galaxy S20+' => $f, 'Galaxy S20 Ultra' => $p, 'Galaxy S20 FE' => $u,
                'Galaxy Note 20' => $f, 'Galaxy Note 20 Ultra' => $p,
                'Galaxy S21' => $f, 'Galaxy S21+' => $f, 'Galaxy S21 Ultra' => $p, 'Galaxy S21 FE' => $u,
                'Galaxy S22' => $f, 'Galaxy S22+' => $f, 'Galaxy S22 Ultra' => $p,
                'Galaxy S23' => $f, 'Galaxy S23+' => $f, 'Galaxy S23 Ultra' => $p, 'Galaxy S23 FE' => $u,
                'Galaxy S24' => $f, 'Galaxy S24+' => $f, 'Galaxy S24 Ultra' => $p, 'Galaxy S24 FE' => $u,
                'Galaxy S25' => $f, 'Galaxy S25+' => $f, 'Galaxy S25 Ultra' => $p, 'Galaxy S25 Edge' => $f, 'Galaxy S25 FE' => $u,
                // best-effort: 2026 line-up
                'Galaxy S26' => $f, 'Galaxy S26+' => $f, 'Galaxy S26 Ultra' => $p,

                // Foldables
                'Galaxy Z Flip 5G' => $z, 'Galaxy Z Flip3' => $z, 'Galaxy Z Flip4' => $z, 'Galaxy Z Flip5' => $z,
                'Galaxy Z Flip6' => $z, 'Galaxy Z Flip7' => $z, 'Galaxy Z Flip7 FE' => $z,
                'Galaxy Z Fold2' => $z, 'Galaxy Z Fold3' => $z, 'Galaxy Z Fold4' => $z, 'Galaxy Z Fold5' => $z,
                'Galaxy Z Fold6' => $z, 'Galaxy Z Fold7' => $z,
                // best-effort: 2026 foldables
                'Galaxy Z Flip8' => $z, 'Galaxy Z Fold8' => $z,

                // A series
                'Galaxy A01' => $b, 'Galaxy A11' => $b, 'Galaxy A21s' => $b, 'Galaxy A31' => $m, 'Galaxy A51' => $m, 'Galaxy A71' => $u,
                'Galaxy A02' => $b, 'Galaxy A02s' => $b, 'Galaxy A12' => $b, 'Galaxy A22' => $m, 'Galaxy A32' => $m,
                'Galaxy A42 5G' => $m, 'Galaxy A52' => $u, 'Galaxy A52s' => $u, 'Galaxy A72' => $u,
                'Galaxy A03' => $b, 'Galaxy A03s' => $b, 'Galaxy A13' => $b, 'Galaxy A23' => $m, 'Galaxy A33' => $m, 'Galaxy A53' => $u, 'Galaxy A73' => $u,
                'Galaxy A04' => $b, 'Galaxy A04s' => $b, 'Galaxy A14' => $b, 'Galaxy A24' => $m, 'Galaxy A34' => $m, 'Galaxy A54' => $u,
                'Galaxy A05' => $b, 'Galaxy A05s' => $b, 'Galaxy A15' => $b, 'Galaxy A25' => $m, 'Galaxy A35' => $m, 'Galaxy A55' => $u,
                'Galaxy A06' => $b, 'Galaxy A16' => $b, 'Galaxy A26' => $m, 'Galaxy A36' => $m, 'Galaxy A56' => $u,
                'Galaxy A07' => $b, 'Galaxy A17' => $b,
                // best-effort: 2026 A series
                'Galaxy A37' => $m, 'Galaxy A57' => $u,

                // M series
                'Galaxy M11' => $b, 'Galaxy M21' => $m, 'Galaxy M31' => $m, 'Galaxy M51' => $m,
                'Galaxy M12' => $b, 'Galaxy M22' => $m, 'Galaxy M32' => $m, 'Galaxy M52' => $m,
                'Galaxy M13' => $b, 'Galaxy M23' => $m, 'Galaxy M33' => $m, 'Galaxy M53' => $u,
                'Galaxy M14' => $b, 'Galaxy M34' => $m, 'Galaxy M54' => $u,
                'Galaxy M15' => $b, 'Galaxy M35' => $m, 'Galaxy M55' => $u,
            ],

            'Xiaomi' => [
                // Xiaomi / Mi
                'Mi 10T' => $u, 'Mi 10T Pro' => $u, 'Mi 11' => $f, 'Mi 11 Lite' => $m,
                'Xiaomi 11T' => $u, 'Xiaomi 11T Pro' => $u, 'Xiaomi 12' => $f, 'Xiaomi 12 Pro' => $f,
                'Xiaomi 12T' => $u, 'Xiaomi 12T Pro' => $u, 'Xiaomi 13' => $f, 'Xiaomi 13T' => $u, 'Xiaomi 13T Pro' => $u,
                'Xiaomi 14' => $f, 'Xiaomi 14 Ultra' => $p, 'Xiaomi 14T' => $u, 'Xiaomi 14T Pro' => $f,
                'Xiaomi 15' => $f, 'Xiaomi 15 Ultra' => $p, 'Xiaomi 15T' => $u, 'Xiaomi 15T Pro' => $f,
                // best-effort: 2026 line-up
                'Xiaomi 17' => $f, 'Xiaomi 17 Ultra' => $p,

                // Redmi
                'Redmi 9' => $b, 'Redmi 9A' => $b, 'Redmi 9C' => $b, 'Redmi 9T' => $b,
                'Redmi 10' => $b, 'Redmi 10A' => $b, 'Redmi 10C' => $b,
                'Redmi 12' => $b, 'Redmi 12C' => $b, 'Redmi 13' => $b, 'Redmi 13C' => $b,
                'Redmi 14C' => $b, 'Redmi 15' => $b, 'Redmi 15C' => $b,
                'Redmi A1' => $b, 'Redmi A2' => $b, 'Redmi A3' => $b, 'Redmi A5' => $b,

                // Redmi Note
                'Redmi Note 9' => $b, 'Redmi Note 9S' => $m, 'Redmi Note 9 Pro' => $m,
                'Redmi Note 10' => $m, 'Redmi Note 10S' => $m, 'Redmi Note 10 Pro' => $m,
                'Redmi Note 11' => $m, 'Redmi Note 11S' => $m, 'Redmi Note 11 Pro' => $m,
                'Redmi Note 12' => $m, 'Redmi Note 12 Pro' => $m,
                'Redmi Note 13' => $m, 'Redmi Note 13 Pro' => $u, 'Redmi Note 13 Pro+' => $u,
                'Redmi Note 14' => $m, 'Redmi Note 14 Pro' => $u, 'Redmi Note 14 Pro+' => $u,
                // best-effort: 2026 line-up
                'Redmi Note 15' => $m, 'Redmi Note 15 Pro' => $u, 'Redmi Note 15 Pro+' => $u,

                // Poco
                'Poco X3' => $m, 'Poco X3 Pro' => $m, 'Poco F3' => $u, 'Poco M3' => $b,
                'Poco M4 Pro' => $m, 'Poco X4 Pro 5G' => $m, 'Poco F4' => $u,
                'Poco X5' => $m, 'Poco X5 Pro' => $m, 'Poco F5' => $u,
                'Poco X6' => $m, 'Poco X6 Pro' => $u, 'Poco F6' => $u, 'Poco M6 Pro' => $m, 'Poco C65' => $b,
                'Poco X7' => $m, 'Poco X7 Pro' => $u, 'Poco F7' => $u, 'Poco F7 Pro' => $f, 'Poco C75' => $b,
            ],

            'Oppo' => [
                'A15' => $b, 'A16' => $b, 'A17' => $b, 'A18' => $b, 'A38' => $b, 'A3x' => $b,
                'A54' => $m, 'A55' => $m, 'A57' => $b, 'A58' => $m, 'A60' => $m, 'A3' => $m,
                'A74' => $m, 'A76' => $m, 'A77' => $m, 'A78' => $m, 'A79' => $m, 'A98' => $m,
                'A5' => $m, 'A5 Pro' => $m,
                'Reno4' => $u, 'Reno5' => $u, 'Reno6' => $u, 'Reno7' => $u, 'Reno8' => $u, 'Reno8 T' => $u,
                'Reno10' => $u, 'Reno11' => $u, 'Reno11 F' => $m, 'Reno12' => $u, 'Reno12 F' => $m,
                'Reno13' => $u, 'Reno13 F' => $m, 'Reno14' => $u, 'Reno14 F' => $m,
                // best-effort: 2026 line-up
                'Reno15' => $u, 'Reno15 F' => $m,
                'Find X3 Pro' => $f, 'Find X5 Pro' => $f, 'Find X8' => $f, 'Find X8 Pro' => $p,
                'Find X9' => $f, 'Find X9 Pro' => $p, 'Find N3 Flip' => $z, 'Find N5' => $z,
            ],

            'Vivo' => [
                'Y11' => $b, 'Y12s' => $b, 'Y15s' => $b, 'Y16' => $b, 'Y17s' => $b, 'Y20' => $b,
                'Y21' => $b, 'Y22' => $b, 'Y27' => $b, 'Y28' => $b, 'Y03' => $b, 'Y18' => $b,
                'Y19' => $b, 'Y19s' => $b, 'Y29' => $b, 'Y04' => $b,
                'Y33s' => $m, 'Y35' => $m, 'Y36' => $m, 'Y51' => $m, 'Y100' => $m, 'Y39' => $m,
                'V20' => $u, 'V21' => $u, 'V23' => $u, 'V25' => $u, 'V27' => $u, 'V29' => $u,
                'V30' => $u, 'V40' => $u, 'V50' => $u, 'V60' => $u,
                // best-effort: 2026 line-up
                'V70' => $u,
                'X60 Pro' => $f, 'X70 Pro' => $f, 'X80 Pro' => $f, 'X90 Pro' => $f,
                'X100 Pro' => $p, 'X200' => $f, 'X200 Pro' => $p, 'X300' => $f, 'X300 Pro' => $p,
            ],

            'Realme' => [
                'C11' => $b, 'C15' => $b, 'C21' => $b, 'C25' => $b, 'C30' => $b, 'C33' => $b, 'C35' => $b,
                'C51' => $b, 'C53' => $b, 'C55' => $b, 'C61' => $b, 'C63' => $b, 'C65' => $b, 'C67' => $b,
                'C71' => $b, 'C75' => $b, 'Note 50' => $b, 'Note 60' => $b,
                'Realme 7' => $m, 'Realme 7 Pro' => $m, 'Realme 8' => $m, 'Realme 8 Pro' => $m,
                'Realme 9' => $m, 'Realme 9 Pro' => $m, 'Realme 10' => $m, 'Realme 10 Pro' => $m,
                'Realme 11' => $m, 'Realme 11 Pro' => $u, 'Realme 12' => $m, 'Realme 12 Pro' => $u,
                'Realme 13' => $m, 'Realme 13 Pro' => $u, 'Realme 14' => $m, 'Realme 14 Pro' => $u,
                'Realme 15' => $m, 'Realme 15 Pro' => $u,
                'GT' => $u, 'GT 2 Pro' => $f, 'GT 6' => $u, 'GT 7' => $u, 'GT 7 Pro' => $f, 'GT 8 Pro' => $f,
            ],

            'Infinix' => [
                'Smart 5' => $b, 'Smart 6' => $b, 'Smart 7' => $b, 'Smart 8' => $b, 'Smart 9' => $b, 'Smart 10' => $b,
                'Hot 10' => $b, 'Hot 11' => $b, 'Hot 12' => $b, 'Hot 20' => $b, 'Hot 30' => $b,
                'Hot 40' => $b, 'Hot 40 Pro' => $b, 'Hot 50' => $b, 'Hot 50 Pro' => $m, 'Hot 60' => $b, 'Hot 60 Pro' => $m,
                'Note 10' => $m, 'Note 11' => $m, 'Note 12' => $m, 'Note 30' => $m,
                'Note 40' => $m, 'Note 40 Pro' => $m, 'Note 50' => $m, 'Note 50 Pro' => $m,
                'Zero 8' => $m, 'Zero X Pro' => $m, 'Zero 20' => $m, 'Zero 30' => $u, 'Zero 40' => $u, 'Zero Flip' => $z,
                'GT 20 Pro' => $u, 'GT 30 Pro' => $u,
            ],

            'Tecno' => [
                'Spark 6' => $b, 'Spark 7' => $b, 'Spark 8' => $b, 'Spark 9' => $b, 'Spark 10' => $b,
                'Spark 20' => $b, 'Spark 20 Pro' => $b, 'Spark 30' => $b, 'Spark 30 Pro' => $m,
                'Spark 40' => $b, 'Spark 40 Pro' => $m,
                'Camon 16' => $m, 'Camon 17' => $m, 'Camon 18' => $m, 'Camon 19' => $m, 'Camon 20' => $m,
                'Camon 30' => $m, 'Camon 40' => $m, 'Camon 40 Pro' => $u,
                'Pova 2' => $b, 'Pova 3' => $b, 'Pova 4' => $b, 'Pova 5' => $m, 'Pova 6' => $m, 'Pova 7' => $m,
                'Phantom V Fold' => $z, 'Phantom V Flip' => $z,
            ],

            'Huawei' => [
                'P40' => $f, 'P40 Pro' => $f, 'P50 Pro' => $f, 'Pura 70' => $f, 'Pura 70 Pro' => $p, 'Pura 80 Pro' => $p,
                'Mate 40 Pro' => $f, 'Mate 50 Pro' => $f, 'Mate X6' => $z,
                'Nova 7i' => $m, 'Nova 8i' => $m, 'Nova 9' => $u, 'Nova 10' => $u, 'Nova 11' => $u,
                'Nova 12' => $u, 'Nova 13' => $u, 'Nova 14' => $u, 'Nova Y70' => $b, 'Nova Y90' => $m,
            ],

            'Honor' => [
                'X6' => $b, 'X7' => $b, 'X7b' => $b, 'X8' => $m, 'X8a' => $m, 'X8b' => $m, 'X8c' => $m,
                'X9a' => $m, 'X9b' => $m, 'X9c' => $m,
                'Honor 90' => $u, 'Honor 200' => $u, 'Honor 200 Pro' => $f, 'Honor 400' => $u, 'Honor 400 Pro' => $f,
                'Magic5 Pro' => $f, 'Magic6 Pro' => $f, 'Magic7 Pro' => $p,
                'Magic V2' => $z, 'Magic V3' => $z, 'Magic V5' => $z,
            ],

            'OnePlus' => [
                'OnePlus 8T' => $f, 'OnePlus 9' => $f, 'OnePlus 9 Pro' => $f, 'OnePlus 10 Pro' => $f, 'OnePlus 10T' => $u,
                'OnePlus 11' => $f, 'OnePlus 12' => $f, 'OnePlus 12R' => $u, 'OnePlus 13' => $f, 'OnePlus 13R' => $u,
                'OnePlus 15' => $f,
                'Nord' => $m, 'Nord 2' => $m, 'Nord CE 2' => $m, 'Nord 3' => $m, 'Nord CE 3' => $m,
                'Nord 4' => $u, 'Nord CE 4' => $m, 'Nord 5' => $u, 'Nord CE 5' => $m,
            ],

            'Google' => [
                'Pixel 4a' => $m, 'Pixel 5' => $u, 'Pixel 5a' => $m,
                'Pixel 6' => $u, 'Pixel 6 Pro' => $f, 'Pixel 6a' => $m,
                'Pixel 7' => $u, 'Pixel 7 Pro' => $f, 'Pixel 7a' => $m,
                'Pixel 8' => $u, 'Pixel 8 Pro' => $f, 'Pixel 8a' => $m,
                'Pixel 9' => $f, 'Pixel 9 Pro' => $f, 'Pixel 9 Pro XL' => $p, 'Pixel 9a' => $m, 'Pixel 9 Pro Fold' => $z,
                'Pixel 10' => $f, 'Pixel 10 Pro' => $f, 'Pixel 10 Pro XL' => $p, 'Pixel 10 Pro Fold' => $z,
                // best-effort: 2026 line-up
                'Pixel 10a' => $m,
            ],

            'Nokia' => [
                'Nokia 5.4' => $b, 'Nokia 8.3' => $m, 'C20' => $b, 'C21' => $b, 'C30' => $b, 'C32' => $b,
                'G10' => $b, 'G20' => $b, 'G21' => $b, 'G22' => $b, 'G42' => $m, 'X20' => $m,
            ],

            // best-effort: model names vary by batch; confirm against stock.
            'Cherry Mobile' => [
                'Flare S8' => $b, 'Flare S8 Plus' => $b, 'Aqua S9' => $b, 'Aqua S10' => $b, 'Aqua S10 Pro' => $b,
            ],
        ];
    }

    /**
     * Brands for the intake and inventory dropdowns, with "Other" last for
     * anything not on the list.
     *
     * @return array<int, string>
     */
    public static function brands(): array
    {
        return [...array_keys(self::BRAND_CODES), 'Other'];
    }

    /** What a model is called on a part: "Samsung Galaxy S24", "iPhone 15". */
    public static function displayName(string $brand, string $model): string
    {
        if ($brand === 'Apple' || stripos($model, $brand) === 0) {
            return $model;
        }

        return "{$brand} {$model}";
    }

    /** A stable SKU: brand code, model, part code — "APL-IPHONE15PROMAX-SCR". */
    public static function sku(string $brand, string $model, string $partCode): string
    {
        $slug = strtoupper(str_replace('+', 'PLUS', preg_replace('/[^A-Za-z0-9+]/', '', $model)));

        return (self::BRAND_CODES[$brand] ?? 'GEN')."-{$slug}-{$partCode}";
    }

    /**
     * Every catalogue part, ready to save.
     *
     * @return \Generator<int, array{sku: string, name: string, category: string, aliases: array<int, string>, manufacturer: string, model: string, unit_cost_price: float, unit_sale_price: float}>
     */
    public static function parts(): \Generator
    {
        foreach (self::models() as $brand => $models) {
            foreach ($models as $model => $tier) {
                $types = self::PART_TYPES;

                if ($tier === self::FOLDABLE) {
                    $types = [self::FOLDABLE_INNER_SCREEN['code'] => self::FOLDABLE_INNER_SCREEN] + $types;
                }

                foreach ($types as $code => $type) {
                    [$cost, $sale] = self::PRICES[$code][$tier];

                    yield [
                        'sku' => self::sku($brand, $model, $code),
                        'name' => $type['name'].' - '.self::displayName($brand, $model),
                        'category' => $type['category'],
                        'aliases' => $type['aliases'],
                        'manufacturer' => $brand,
                        'model' => $model,
                        'unit_cost_price' => (float) $cost,
                        'unit_sale_price' => (float) $sale,
                    ];
                }
            }
        }
    }
}
