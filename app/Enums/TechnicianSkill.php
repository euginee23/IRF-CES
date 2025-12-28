<?php

namespace App\Enums;

enum TechnicianSkill: string
{
    // 1. Display & Input
    case SCREEN_REPAIR = 'Screen Repair';
    case TOUCHSCREEN_DIGITIZER_REPAIR = 'Touchscreen / Digitizer Repair';
    case BUTTON_REPAIR = 'Button Repair (Power / Volume / Home)';
    case FINGERPRINT_FACE_ID_REPAIR = 'Fingerprint / Face ID Repair';
    case VIBRATOR_HAPTIC_REPAIR = 'Vibrator / Haptic Repair';

    // 2. Power & Charging
    case BATTERY_REPLACEMENT = 'Battery Replacement';
    case CHARGING_PORT_REPAIR = 'Charging Port Repair';

    // 3. Motherboard & Internal Components
    case MOTHERBOARD_LOGIC_BOARD_REPAIR = 'Motherboard / Logic Board Repair';
    case SOLDERING_MICRO_SOLDERING = 'Soldering / Micro-Soldering';
    case SIM_SD_SLOT_REPAIR = 'SIM / SD Slot Repair';
    case CAMERA_REPAIR = 'Camera Repair';
    case SPEAKER_MICROPHONE_REPAIR = 'Speaker / Microphone Repair';

    // 4. Water & Physical Damage
    case WATER_DAMAGE_REPAIR = 'Water Damage Repair';
    case ACCESSORIES_PERIPHERAL_REPAIR = 'Accessories & Peripheral Repair';

    // 5. Software & Firmware
    case OS_FIRMWARE_FLASHING = 'OS & Firmware Flashing';
    case SOFTWARE_TROUBLESHOOTING = 'Software Troubleshooting';
    case UNLOCKING_ROOTING_JAILBREAKING = 'Unlocking / Rooting / Jailbreaking';

    // 6. Data & Security
    case DATA_RECOVERY_BACKUP = 'Data Recovery / Backup';

    // 7. Diagnostics & Testing
    case NETWORK_SIGNAL_DIAGNOSTICS = 'Network / Signal Diagnostics';
    case DIAGNOSTICS_TESTING = 'Diagnostics & Testing';

    // 8. Refurbishing & Resale
    case REFURBISHING_RESALE_PREP = 'Refurbishing / Resale Prep';

    public static function getGrouped(): array
    {
        return [
            'Display & Input' => [
                self::SCREEN_REPAIR,
                self::TOUCHSCREEN_DIGITIZER_REPAIR,
                self::BUTTON_REPAIR,
                self::FINGERPRINT_FACE_ID_REPAIR,
                self::VIBRATOR_HAPTIC_REPAIR,
            ],
            'Power & Charging' => [
                self::BATTERY_REPLACEMENT,
                self::CHARGING_PORT_REPAIR,
            ],
            'Motherboard & Internal Components' => [
                self::MOTHERBOARD_LOGIC_BOARD_REPAIR,
                self::SOLDERING_MICRO_SOLDERING,
                self::SIM_SD_SLOT_REPAIR,
                self::CAMERA_REPAIR,
                self::SPEAKER_MICROPHONE_REPAIR,
            ],
            'Water & Physical Damage' => [
                self::WATER_DAMAGE_REPAIR,
                self::ACCESSORIES_PERIPHERAL_REPAIR,
            ],
            'Software & Firmware' => [
                self::OS_FIRMWARE_FLASHING,
                self::SOFTWARE_TROUBLESHOOTING,
                self::UNLOCKING_ROOTING_JAILBREAKING,
            ],
            'Data & Security' => [
                self::DATA_RECOVERY_BACKUP,
            ],
            'Diagnostics & Testing' => [
                self::NETWORK_SIGNAL_DIAGNOSTICS,
                self::DIAGNOSTICS_TESTING,
            ],
            'Refurbishing & Resale' => [
                self::REFURBISHING_RESALE_PREP,
            ],
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
