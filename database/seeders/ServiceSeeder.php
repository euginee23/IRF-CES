<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            'Display & Input' => [
                ['name' => 'Screen Repair', 'labor_price' => 500.00, 'estimated_duration' => 60],
                ['name' => 'Touchscreen / Digitizer Repair', 'labor_price' => 450.00, 'estimated_duration' => 45],
                ['name' => 'Button Repair (Power / Volume / Home)', 'labor_price' => 300.00, 'estimated_duration' => 30],
                ['name' => 'Fingerprint / Face ID Repair', 'labor_price' => 600.00, 'estimated_duration' => 90],
                ['name' => 'Vibrator / Haptic Repair', 'labor_price' => 350.00, 'estimated_duration' => 40],
            ],
            'Power & Charging' => [
                ['name' => 'Battery Replacement', 'labor_price' => 400.00, 'estimated_duration' => 30],
                ['name' => 'Charging Port Repair', 'labor_price' => 450.00, 'estimated_duration' => 45],
            ],
            'Motherboard & Internal Components' => [
                ['name' => 'Motherboard / Logic Board Repair', 'labor_price' => 1500.00, 'estimated_duration' => 180],
                ['name' => 'Soldering / Micro-Soldering', 'labor_price' => 800.00, 'estimated_duration' => 120],
                ['name' => 'SIM / SD Slot Repair', 'labor_price' => 350.00, 'estimated_duration' => 40],
                ['name' => 'Camera Repair', 'labor_price' => 500.00, 'estimated_duration' => 60],
                ['name' => 'Speaker / Microphone Repair', 'labor_price' => 400.00, 'estimated_duration' => 45],
            ],
            'Water & Physical Damage' => [
                ['name' => 'Water Damage Repair', 'labor_price' => 1200.00, 'estimated_duration' => 240],
                ['name' => 'Accessories & Peripheral Repair', 'labor_price' => 300.00, 'estimated_duration' => 30],
            ],
            'Software & Firmware' => [
                ['name' => 'OS & Firmware Flashing', 'labor_price' => 500.00, 'estimated_duration' => 60],
                ['name' => 'Software Troubleshooting', 'labor_price' => 350.00, 'estimated_duration' => 45],
                ['name' => 'Unlocking / Rooting / Jailbreaking', 'labor_price' => 600.00, 'estimated_duration' => 90],
            ],
            'Data & Security' => [
                ['name' => 'Data Recovery / Backup', 'labor_price' => 800.00, 'estimated_duration' => 120],
            ],
            'Diagnostics & Testing' => [
                ['name' => 'Network / Signal Diagnostics', 'labor_price' => 250.00, 'estimated_duration' => 30],
                ['name' => 'Diagnostics & Testing', 'labor_price' => 200.00, 'estimated_duration' => 20],
            ],
            'Refurbishing & Resale' => [
                ['name' => 'Refurbishing / Resale Prep', 'labor_price' => 700.00, 'estimated_duration' => 120],
            ],
        ];

        foreach ($services as $category => $categoryServices) {
            foreach ($categoryServices as $service) {
                Service::create([
                    'name' => $service['name'],
                    'category' => $category,
                    'labor_price' => $service['labor_price'],
                    'estimated_duration' => $service['estimated_duration'],
                    'description' => null,
                    'is_active' => true,
                ]);
            }
        }
    }
}
