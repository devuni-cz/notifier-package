<?php

declare(strict_types=1);

namespace Devuni\Notifier\Enums;

enum Theme: string
{
    case Blue = 'blue';
    case Cyan = 'cyan';
    case Green = 'green';
    case Purple = 'purple';
    case Orange = 'orange';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    /**
     * @return array<int, int>
     */
    public function gradient(): array
    {
        return match ($this) {
            self::Blue => [33, 39, 45, 51, 87, 123],
            self::Cyan => [37, 44, 51, 50, 49, 48],
            self::Green => [22, 28, 34, 40, 46, 82],
            self::Purple => [55, 56, 57, 93, 129, 165],
            self::Orange => [208, 214, 220, 221, 215, 209],
        };
    }

    public function primary(): int
    {
        return match ($this) {
            self::Blue => 39,
            self::Cyan => 44,
            self::Green => 34,
            self::Purple => 93,
            self::Orange => 214,
        };
    }
}
