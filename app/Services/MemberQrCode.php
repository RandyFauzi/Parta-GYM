<?php

namespace App\Services;

use InvalidArgumentException;

class MemberQrCode
{
    /**
     * Encode the numeric member identifier as QR Model 2, version 1-L, mask 0.
     *
     * @return list<list<bool>>
     */
    public function encode(string $code): array
    {
        if (! preg_match('/^[0-9]{12,20}$/D', $code)) {
            throw new InvalidArgumentException('Expected 12 to 20 digits.');
        }
        $bits = '0001'.str_pad(decbin(strlen($code)), 10, '0', STR_PAD_LEFT);
        foreach (str_split($code, 3) as $group) {
            $bits .= str_pad(decbin((int) $group), [1 => 4, 2 => 7, 3 => 10][strlen($group)], '0', STR_PAD_LEFT);
        }
        $bits .= '0000';
        $bits = str_pad($bits, (int) ceil(strlen($bits) / 8) * 8, '0');
        $data = array_map(bindec(...), str_split($bits, 8));
        for ($index = 0; count($data) < 19; $index++) {
            $data[] = $index % 2 === 0 ? 0xEC : 0x11;
        }
        $generator = [1];
        $root = 1;
        for ($degree = 0; $degree < 7; $degree++) {
            $next = array_fill(0, count($generator) + 1, 0);
            foreach ($generator as $index => $coefficient) {
                $next[$index] ^= $coefficient;
                $next[$index + 1] ^= $this->multiply($coefficient, $root);
            }
            $generator = $next;
            $root = $this->multiply($root, 2);
        }
        $remainder = array_merge($data, array_fill(0, 7, 0));
        for ($index = 0; $index < 19; $index++) {
            $factor = $remainder[$index];
            foreach ($generator as $offset => $coefficient) {
                $remainder[$index + $offset] ^= $this->multiply($coefficient, $factor);
            }
        }
        $payload = '';
        foreach (array_merge($data, array_slice($remainder, -7)) as $byte) {
            $payload .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }
        $matrix = array_fill(0, 21, array_fill(0, 21, false));
        $reserved = $matrix;
        $set = function (int $x, int $y, bool $dark) use (&$matrix, &$reserved): void {
            if ($x >= 0 && $x < 21 && $y >= 0 && $y < 21) {
                $matrix[$y][$x] = $dark;
                $reserved[$y][$x] = true;
            }
        };
        for ($index = 0; $index < 21; $index++) {
            $set(6, $index, $index % 2 === 0);
            $set($index, 6, $index % 2 === 0);
        }
        foreach ([[3, 3], [17, 3], [3, 17]] as [$centerX,$centerY]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $distance = max(abs($dx), abs($dy));
                    $set($centerX + $dx, $centerY + $dy, $distance !== 2 && $distance !== 4);
                }
            }
        }
        $format = 0x77C4;
        $bit = fn (int $index): bool => (($format >> $index) & 1) !== 0;
        for ($index = 0; $index <= 5; $index++) {
            $set(8, $index, $bit($index));
        }
        $set(8, 7, $bit(6));
        $set(8, 8, $bit(7));
        $set(7, 8, $bit(8));
        for ($index = 9; $index < 15; $index++) {
            $set(14 - $index, 8, $bit($index));
        }
        for ($index = 0; $index < 8; $index++) {
            $set(20 - $index, 8, $bit($index));
        }
        for ($index = 8; $index < 15; $index++) {
            $set(8, 6 + $index, $bit($index));
        }
        $set(8, 13, true);
        $position = 0;
        for ($right = 20; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vertical = 0; $vertical < 21; $vertical++) {
                $y = (($right + 1) & 2) === 0 ? 20 - $vertical : $vertical;
                for ($offset = 0; $offset < 2; $offset++) {
                    $x = $right - $offset;
                    if (! $reserved[$y][$x]) {
                        $matrix[$y][$x] = ($payload[$position++] === '1') !== (($x + $y) % 2 === 0);
                    }
                }
            }
        }

        return $matrix;
    }

    private function multiply(int $left, int $right): int
    {
        $result = 0;
        while ($right > 0) {
            if (($right & 1) !== 0) {
                $result ^= $left;
            }
            $right >>= 1;
            $left <<= 1;
            if (($left & 0x100) !== 0) {
                $left ^= 0x11D;
            }
        }

        return $result;
    }
}
