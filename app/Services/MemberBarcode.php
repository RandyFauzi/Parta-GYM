<?php

namespace App\Services;

class MemberBarcode
{
    /** @return array{width: int, bars: list<array{x: int, width: int}>} */
    public function encode(string $code): array
    {
        if (! preg_match('/^[0-9]{12,20}$/D', $code)) {
            throw new \InvalidArgumentException('Expected 12 to 20 digits.');
        }
        $patterns = ['0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn', '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw', '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', '*' => 'nwnnwnwnn'];
        $position = 10;
        $bars = [];
        foreach (str_split('*'.$code.'*') as $character) {
            foreach (str_split($patterns[$character]) as $index => $element) {
                $width = $element === 'w' ? 3 : 1;
                if ($index % 2 === 0) {
                    $bars[] = ['x' => $position, 'width' => $width];
                }
                $position += $width;
            }
            $position++;
        }

        return ['width' => $position + 9, 'bars' => $bars];
    }
}
