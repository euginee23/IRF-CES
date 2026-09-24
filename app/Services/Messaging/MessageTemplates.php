<?php

namespace App\Services\Messaging;

use App\Contracts\Contactable;
use App\Models\CustomerMessage;
use InvalidArgumentException;

/**
 * Turns the presets in config/customer-messages.php into ready-to-edit text
 * for the "Contact Customer" composer.
 */
class MessageTemplates
{
    /**
     * Template keys and their labels, for the picker.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(config('customer-messages.templates', []))
            ->map(fn (array $template, string $key) => $template['label'] ?? $key)
            ->all();
    }

    public static function exists(string $key): bool
    {
        return is_array(config("customer-messages.templates.{$key}"));
    }

    /**
     * Render one template for one record.
     *
     * @return array{subject: string, body: string}
     */
    public static function render(string $key, string $channel, Contactable $record): array
    {
        return self::renderFrom("customer-messages.templates.{$key}", $key, $channel, $record);
    }

    /**
     * Render one of the messages the system sends on its own.
     *
     * Separate from render() so these stay out of options(), which feeds the
     * staff picker.
     *
     * @return array{subject: string, body: string}
     */
    public static function renderSystem(string $key, string $channel, Contactable $record): array
    {
        return self::renderFrom("customer-messages.system.{$key}", $key, $channel, $record);
    }

    /**
     * @return array{subject: string, body: string}
     */
    private static function renderFrom(string $path, string $key, string $channel, Contactable $record): array
    {
        $template = config($path);

        if (! is_array($template)) {
            throw new InvalidArgumentException("Unknown message template [{$key}].");
        }

        $replacements = [];

        foreach ($record->messagePlaceholders() as $name => $value) {
            $replacements[':'.$name] = $value;
        }

        // Unknown placeholders are deliberately left untouched by strtr, so a
        // typo in a template shows up in the composer instead of sending a gap.
        return [
            'subject' => $channel === CustomerMessage::CHANNEL_SMS
                ? ''
                : strtr((string) ($template['subject'] ?? ''), $replacements),
            'body' => strtr((string) ($template[$channel] ?? ''), $replacements),
        ];
    }
}
