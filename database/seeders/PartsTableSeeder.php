<?php

namespace Database\Seeders;

use App\Models\Part;
use Illuminate\Database\Seeder;

class PartsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parts = [
            // Samsung (multiple models)
            ['sku' => 'SAM-S21-SCR', 'name' => 'Screen Assembly - Samsung Galaxy S21', 'category' => 'Display', 'manufacturer' => 'Samsung', 'model' => 'Galaxy S21', 'unit_cost_price' => 1400.00, 'unit_sale_price' => 2600.00, 'in_stock' => 6],
            ['sku' => 'SAM-S20-SCR', 'name' => 'Screen Assembly - Samsung Galaxy S20', 'category' => 'Display', 'manufacturer' => 'Samsung', 'model' => 'Galaxy S20', 'unit_cost_price' => 1200.00, 'unit_sale_price' => 2200.00, 'in_stock' => 5],
            ['sku' => 'SAM-A52-SCR', 'name' => 'Screen Assembly - Samsung Galaxy A52', 'category' => 'Display', 'manufacturer' => 'Samsung', 'model' => 'Galaxy A52', 'unit_cost_price' => 950.00, 'unit_sale_price' => 1700.00, 'in_stock' => 7],
            ['sku' => 'SAM-A32-BAT', 'name' => 'Battery - Samsung Galaxy A32', 'category' => 'Battery', 'manufacturer' => 'Samsung', 'model' => 'Galaxy A32', 'unit_cost_price' => 420.00, 'unit_sale_price' => 800.00, 'in_stock' => 12],
            ['sku' => 'SAM-S21-CHG', 'name' => 'Charging Port - Samsung Galaxy S21', 'category' => 'Power & Charging', 'manufacturer' => 'Samsung', 'model' => 'Galaxy S21', 'unit_cost_price' => 220.00, 'unit_sale_price' => 450.00, 'in_stock' => 9],
            ['sku' => 'SAM-S21-CAM', 'name' => 'Rear Camera Module - Samsung Galaxy S21', 'category' => 'Camera', 'manufacturer' => 'Samsung', 'model' => 'Galaxy S21', 'unit_cost_price' => 750.00, 'unit_sale_price' => 1400.00, 'in_stock' => 4],

            // Apple (multiple models)
            ['sku' => 'APL-12-SCR', 'name' => 'Screen Assembly - iPhone 12', 'category' => 'Display', 'manufacturer' => 'Apple', 'model' => 'iPhone 12', 'unit_cost_price' => 1800.00, 'unit_sale_price' => 3400.00, 'in_stock' => 4],
            ['sku' => 'APL-13-SCR', 'name' => 'Screen Assembly - iPhone 13', 'category' => 'Display', 'manufacturer' => 'Apple', 'model' => 'iPhone 13', 'unit_cost_price' => 1900.00, 'unit_sale_price' => 3500.00, 'in_stock' => 5],
            ['sku' => 'APL-14-BAT', 'name' => 'Battery - iPhone 14', 'category' => 'Battery', 'manufacturer' => 'Apple', 'model' => 'iPhone 14', 'unit_cost_price' => 850.00, 'unit_sale_price' => 1600.00, 'in_stock' => 6],
            ['sku' => 'APL-13-CHG', 'name' => 'Lightning Connector - iPhone 13', 'category' => 'Power & Charging', 'manufacturer' => 'Apple', 'model' => 'iPhone 13', 'unit_cost_price' => 320.00, 'unit_sale_price' => 700.00, 'in_stock' => 5],

            // Xiaomi / Redmi / Poco
            ['sku' => 'XIA-RED-N11-SCR', 'name' => 'Screen Assembly - Redmi Note 11', 'category' => 'Display', 'manufacturer' => 'Xiaomi', 'model' => 'Redmi Note 11', 'unit_cost_price' => 850.00, 'unit_sale_price' => 1500.00, 'in_stock' => 8],
            ['sku' => 'XIA-MI11-BAT', 'name' => 'Battery - Xiaomi Mi 11', 'category' => 'Battery', 'manufacturer' => 'Xiaomi', 'model' => 'Mi 11', 'unit_cost_price' => 320.00, 'unit_sale_price' => 620.00, 'in_stock' => 12],
            ['sku' => 'POC-X3-CHG', 'name' => 'Charging Port - Poco X3', 'category' => 'Power & Charging', 'manufacturer' => 'Xiaomi', 'model' => 'Poco X3', 'unit_cost_price' => 130.00, 'unit_sale_price' => 270.00, 'in_stock' => 14],

            // Oppo
            ['sku' => 'OPP-A54-SCR', 'name' => 'Screen Assembly - Oppo A54', 'category' => 'Display', 'manufacturer' => 'Oppo', 'model' => 'A54', 'unit_cost_price' => 780.00, 'unit_sale_price' => 1450.00, 'in_stock' => 7],
            ['sku' => 'OPP-RENO5-BAT', 'name' => 'Battery - Oppo Reno5', 'category' => 'Battery', 'manufacturer' => 'Oppo', 'model' => 'Reno5', 'unit_cost_price' => 300.00, 'unit_sale_price' => 560.00, 'in_stock' => 10],

            // Vivo
            ['sku' => 'VIV-Y20-SCR', 'name' => 'Screen Assembly - Vivo Y20', 'category' => 'Display', 'manufacturer' => 'Vivo', 'model' => 'Y20', 'unit_cost_price' => 700.00, 'unit_sale_price' => 1290.00, 'in_stock' => 9],
            ['sku' => 'VIV-V20-BAT', 'name' => 'Battery - Vivo V20', 'category' => 'Battery', 'manufacturer' => 'Vivo', 'model' => 'V20', 'unit_cost_price' => 250.00, 'unit_sale_price' => 480.00, 'in_stock' => 13],

            // Realme
            ['sku' => 'RLM-8-SCR', 'name' => 'Screen Assembly - Realme 8', 'category' => 'Display', 'manufacturer' => 'Realme', 'model' => 'Realme 8', 'unit_cost_price' => 620.00, 'unit_sale_price' => 1150.00, 'in_stock' => 11],
            ['sku' => 'RLM-9-BAT', 'name' => 'Battery - Realme 9', 'category' => 'Battery', 'manufacturer' => 'Realme', 'model' => 'Realme 9', 'unit_cost_price' => 230.00, 'unit_sale_price' => 420.00, 'in_stock' => 18],

            // Huawei
            ['sku' => 'HUA-P30-SCR', 'name' => 'Screen Assembly - Huawei P30', 'category' => 'Display', 'manufacturer' => 'Huawei', 'model' => 'P30', 'unit_cost_price' => 820.00, 'unit_sale_price' => 1500.00, 'in_stock' => 6],
            ['sku' => 'HUA-P40-BAT', 'name' => 'Battery - Huawei P40', 'category' => 'Battery', 'manufacturer' => 'Huawei', 'model' => 'P40', 'unit_cost_price' => 340.00, 'unit_sale_price' => 620.00, 'in_stock' => 9],

            // Infinix
            ['sku' => 'INF-HOT10-SCR', 'name' => 'Screen Assembly - Infinix Hot 10', 'category' => 'Display', 'manufacturer' => 'Infinix', 'model' => 'Hot 10', 'unit_cost_price' => 320.00, 'unit_sale_price' => 640.00, 'in_stock' => 15],
            ['sku' => 'INF-ZERO5-BAT', 'name' => 'Battery - Infinix Zero 5', 'category' => 'Battery', 'manufacturer' => 'Infinix', 'model' => 'Zero 5', 'unit_cost_price' => 280.00, 'unit_sale_price' => 520.00, 'in_stock' => 12],

            // Tecno
            ['sku' => 'TEC-SPARK7-SCR', 'name' => 'Screen Assembly - Tecno Spark 7', 'category' => 'Display', 'manufacturer' => 'Tecno', 'model' => 'Spark 7', 'unit_cost_price' => 310.00, 'unit_sale_price' => 600.00, 'in_stock' => 10],

            // Cherry Mobile
            ['sku' => 'CHR-FLARE-BAT', 'name' => 'Battery - Cherry Mobile Flare', 'category' => 'Battery', 'manufacturer' => 'Cherry Mobile', 'model' => 'Flare', 'unit_cost_price' => 120.00, 'unit_sale_price' => 240.00, 'in_stock' => 20],

            // OnePlus / Honor
            ['sku' => 'ONE-9-SCR', 'name' => 'Screen Assembly - OnePlus 9', 'category' => 'Display', 'manufacturer' => 'OnePlus', 'model' => '9', 'unit_cost_price' => 1050.00, 'unit_sale_price' => 1950.00, 'in_stock' => 4],
            ['sku' => 'HON-10-BAT', 'name' => 'Battery - Honor 10', 'category' => 'Battery', 'manufacturer' => 'Honor', 'model' => '10', 'unit_cost_price' => 260.00, 'unit_sale_price' => 520.00, 'in_stock' => 8],

            // Common small parts (assigned to concrete manufacturers where applicable)
            ['sku' => 'SPK-SAM-01', 'name' => 'Speaker (Earpiece) - Samsung', 'category' => 'Audio', 'manufacturer' => 'Samsung', 'model' => 'Generic', 'unit_cost_price' => 85.00, 'unit_sale_price' => 170.00, 'in_stock' => 18],
            ['sku' => 'SPK-APL-01', 'name' => 'Speaker (Earpiece) - Apple', 'category' => 'Audio', 'manufacturer' => 'Apple', 'model' => 'Generic', 'unit_cost_price' => 95.00, 'unit_sale_price' => 190.00, 'in_stock' => 12],
            ['sku' => 'MIC-XIA-01', 'name' => 'Microphone (Primary) - Xiaomi', 'category' => 'Audio', 'manufacturer' => 'Xiaomi', 'model' => 'Generic', 'unit_cost_price' => 75.00, 'unit_sale_price' => 150.00, 'in_stock' => 20],
            ['sku' => 'CHP-HUA-01', 'name' => 'Charging IC - Huawei', 'category' => 'Motherboard', 'manufacturer' => 'Huawei', 'model' => 'Generic', 'unit_cost_price' => 420.00, 'unit_sale_price' => 840.00, 'in_stock' => 6],
            ['sku' => 'BTN-OPP-01', 'name' => 'Power / Volume Button - Oppo', 'category' => 'Display & Input', 'manufacturer' => 'Oppo', 'model' => 'Generic', 'unit_cost_price' => 65.00, 'unit_sale_price' => 130.00, 'in_stock' => 30],
            ['sku' => 'SIM-TEC-01', 'name' => 'SIM / SD Slot Flex - Tecno', 'category' => 'Motherboard & Internal Components', 'manufacturer' => 'Tecno', 'model' => 'Generic', 'unit_cost_price' => 95.00, 'unit_sale_price' => 190.00, 'in_stock' => 16],
            ['sku' => 'CAM-FR-APL-01', 'name' => 'Front Camera Module - Apple', 'category' => 'Camera', 'manufacturer' => 'Apple', 'model' => 'Generic', 'unit_cost_price' => 230.00, 'unit_sale_price' => 440.00, 'in_stock' => 10],

            // Additional entries to reach ~50
            ['sku' => 'SAM-A12-BAT', 'name' => 'Battery - Samsung Galaxy A12', 'category' => 'Battery', 'manufacturer' => 'Samsung', 'model' => 'Galaxy A12', 'unit_cost_price' => 380.00, 'unit_sale_price' => 720.00, 'in_stock' => 14],
            ['sku' => 'XIA-RED-10-SCR', 'name' => 'Screen Assembly - Redmi 10', 'category' => 'Display', 'manufacturer' => 'Xiaomi', 'model' => 'Redmi 10', 'unit_cost_price' => 760.00, 'unit_sale_price' => 1400.00, 'in_stock' => 9],
            ['sku' => 'OPP-A73-SCR', 'name' => 'Screen Assembly - Oppo A73', 'category' => 'Display', 'manufacturer' => 'Oppo', 'model' => 'A73', 'unit_cost_price' => 720.00, 'unit_sale_price' => 1300.00, 'in_stock' => 8],
            ['sku' => 'VIV-Y51-BAT', 'name' => 'Battery - Vivo Y51', 'category' => 'Battery', 'manufacturer' => 'Vivo', 'model' => 'Y51', 'unit_cost_price' => 240.00, 'unit_sale_price' => 460.00, 'in_stock' => 11],
            ['sku' => 'RLM-C3-SCR', 'name' => 'Screen Assembly - Realme C3', 'category' => 'Display', 'manufacturer' => 'Realme', 'model' => 'C3', 'unit_cost_price' => 560.00, 'unit_sale_price' => 1050.00, 'in_stock' => 10],
            ['sku' => 'INF-TOUCH-01', 'name' => 'Touch Digitizer - Infinix Hot', 'category' => 'Display & Input', 'manufacturer' => 'Infinix', 'model' => 'Hot Series', 'unit_cost_price' => 180.00, 'unit_sale_price' => 350.00, 'in_stock' => 22],
            ['sku' => 'TEC-CHG-02', 'name' => 'Charging Port - Tecno Spark', 'category' => 'Power & Charging', 'manufacturer' => 'Tecno', 'model' => 'Spark Series', 'unit_cost_price' => 110.00, 'unit_sale_price' => 220.00, 'in_stock' => 20],
            ['sku' => 'ONE-8-BAT', 'name' => 'Battery - OnePlus 8', 'category' => 'Battery', 'manufacturer' => 'OnePlus', 'model' => '8', 'unit_cost_price' => 420.00, 'unit_sale_price' => 800.00, 'in_stock' => 6],
            ['sku' => 'HON-20-SCR', 'name' => 'Screen Assembly - Honor 20', 'category' => 'Display', 'manufacturer' => 'Honor', 'model' => '20', 'unit_cost_price' => 640.00, 'unit_sale_price' => 1200.00, 'in_stock' => 7],
            ['sku' => 'CHR-FLARE-SCR', 'name' => 'Screen Assembly - Cherry Mobile Flare', 'category' => 'Display', 'manufacturer' => 'Cherry Mobile', 'model' => 'Flare', 'unit_cost_price' => 220.00, 'unit_sale_price' => 420.00, 'in_stock' => 18],
            ['sku' => 'SPK-ONE-01', 'name' => 'Loudspeaker (Bottom) - OnePlus', 'category' => 'Audio', 'manufacturer' => 'OnePlus', 'model' => 'Generic', 'unit_cost_price' => 105.00, 'unit_sale_price' => 210.00, 'in_stock' => 12],
            ['sku' => 'MIC-HON-01', 'name' => 'Microphone - Honor', 'category' => 'Audio', 'manufacturer' => 'Honor', 'model' => 'Generic', 'unit_cost_price' => 78.00, 'unit_sale_price' => 156.00, 'in_stock' => 14],
        ];

        // Normalize categories to canonical category list used in the UI
        $categoryMap = [
            'Display' => 'Display & Input Components',
            'Display & Input' => 'Display & Input Components',
            'Display & Input' => 'Display & Input Components',
            'Display & Input Components' => 'Display & Input Components',
            'Power & Charging' => 'Power & Charging Components',
            'Battery' => 'Power & Charging Components',
            'Power & Charging' => 'Power & Charging Components',
            'Camera' => 'Camera & Audio Components',
            'Audio' => 'Camera & Audio Components',
            'Motherboard' => 'Motherboard & Core Components',
            'Motherboard & Internal Components' => 'Motherboard & Core Components',
            'Motherboard & Core Components' => 'Motherboard & Core Components',
            'Display & Input Components' => 'Display & Input Components',
            'Camera & Audio Components' => 'Camera & Audio Components',
            'Network' => 'Network & Connectivity Components',
            'Network & Connectivity' => 'Network & Connectivity Components',
            'Network & Connectivity Components' => 'Network & Connectivity Components',
            'Sensors' => 'Sensors & Security Components',
            'Sensors & Security Components' => 'Sensors & Security Components',
            'Structural' => 'Structural & Physical Components',
            'Structural & Physical Components' => 'Structural & Physical Components',
            'Accessories' => 'Accessories & External Parts',
            'Accessories & External Parts' => 'Accessories & External Parts',
        ];

        foreach ($parts as $p) {
            $rawCategory = $p['category'] ?? null;
            $normalizedCategory = $categoryMap[$rawCategory] ?? $rawCategory;

            Part::updateOrCreate([
                'sku' => $p['sku'],
            ], array_merge($p, [
                'description' => $p['name'],
                'category' => $normalizedCategory,
                'reorder_point' => isset($p['reorder_point']) ? $p['reorder_point'] : 5,
                'supplier' => 'Local Supplier',
                'model' => $p['model'] ?? null,
                'is_active' => true,
            ]));
        }
    }
}
