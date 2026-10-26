<?php

declare(strict_types=1);

namespace App\Service;

class TextNormalizer
{
    public function normalize(string $value): array
    {
        $updated = str_replace(["\u{2013}", "\u{2014}"], ',', $value);

        return [
            'value' => $updated,
            'replacedDash' => $updated !== $value,
        ];
    }
}
