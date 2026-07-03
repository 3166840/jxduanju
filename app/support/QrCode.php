<?php

namespace App\Support;

class QrCode
{
    private const LEVEL_L_FORMAT_BITS = 1;

    private const BLOCKS = [
        1 => ['ecc' => 7, 'groups' => [[1, 19]]],
        2 => ['ecc' => 10, 'groups' => [[1, 34]]],
        3 => ['ecc' => 15, 'groups' => [[1, 55]]],
        4 => ['ecc' => 20, 'groups' => [[1, 80]]],
        5 => ['ecc' => 26, 'groups' => [[1, 108]]],
        6 => ['ecc' => 18, 'groups' => [[2, 68]]],
        7 => ['ecc' => 20, 'groups' => [[2, 78]]],
        8 => ['ecc' => 24, 'groups' => [[2, 97]]],
        9 => ['ecc' => 30, 'groups' => [[2, 116]]],
        10 => ['ecc' => 18, 'groups' => [[2, 68], [2, 69]]],
    ];

    private const ALIGNMENT = [
        1 => [],
        2 => [6, 18],
        3 => [6, 22],
        4 => [6, 26],
        5 => [6, 30],
        6 => [6, 34],
        7 => [6, 22, 38],
        8 => [6, 24, 42],
        9 => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    private int $version;
    private int $size;
    private array $modules = [];
    private array $functionModules = [];

    public static function svg(string $text, int $scale = 7, int $margin = 4): string
    {
        $qr = new self($text);
        $matrix = $qr->matrix();
        $size = count($matrix);
        $imageSize = ($size + $margin * 2) * $scale;
        $paths = [];

        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $dark) {
                if ($dark) {
                    $paths[] = 'M' . (($x + $margin) * $scale) . ' ' . (($y + $margin) * $scale) . 'h' . $scale . 'v' . $scale . 'h-' . $scale . 'z';
                }
            }
        }

        return '<svg class="qr-svg" xmlns="http://www.w3.org/2000/svg" width="' . $imageSize . '" height="' . $imageSize . '" viewBox="0 0 ' . $imageSize . ' ' . $imageSize . '" role="img" aria-label="支付二维码">'
            . '<rect width="100%" height="100%" fill="#fff"/>'
            . '<path d="' . implode('', $paths) . '" fill="#111"/>'
            . '</svg>';
    }

    private function __construct(private string $text)
    {
        $this->version = $this->chooseVersion($text);
        $this->size = 21 + ($this->version - 1) * 4;
        $this->modules = array_fill(0, $this->size, array_fill(0, $this->size, false));
        $this->functionModules = array_fill(0, $this->size, array_fill(0, $this->size, false));

        $this->drawFunctionPatterns();
        $this->drawCodewords($this->addErrorCorrection($this->dataCodewords($text)));
        $this->drawFormatBits(0);
    }

    private function matrix(): array
    {
        return $this->modules;
    }

    private function chooseVersion(string $text): int
    {
        $length = strlen($text);
        foreach (array_keys(self::BLOCKS) as $version) {
            $countBits = $version <= 9 ? 8 : 16;
            if (4 + $countBits + $length * 8 <= $this->dataCapacity($version) * 8) {
                return $version;
            }
        }

        throw new \RuntimeException('支付链接太长，暂时无法生成二维码。');
    }

    private function dataCodewords(string $text): array
    {
        $capacityBits = $this->dataCapacity($this->version) * 8;
        $bits = [];
        $this->appendBits($bits, 0b0100, 4);
        $this->appendBits($bits, strlen($text), $this->version <= 9 ? 8 : 16);

        foreach (array_values(unpack('C*', $text)) as $byte) {
            $this->appendBits($bits, $byte, 8);
        }

        $this->appendBits($bits, 0, min(4, $capacityBits - count($bits)));
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $value = 0;
            for ($j = 0; $j < 8; $j++) {
                $value = ($value << 1) | $bits[$i + $j];
            }
            $codewords[] = $value;
        }

        for ($pad = 0xec; count($codewords) < $this->dataCapacity($this->version); $pad ^= 0xfd) {
            $codewords[] = $pad;
        }

        return $codewords;
    }

    private function addErrorCorrection(array $data): array
    {
        $info = self::BLOCKS[$this->version];
        $eccLength = $info['ecc'];
        $blocks = [];
        $offset = 0;

        foreach ($info['groups'] as [$count, $dataLength]) {
            for ($i = 0; $i < $count; $i++) {
                $blockData = array_slice($data, $offset, $dataLength);
                $offset += $dataLength;
                $blocks[] = [
                    'data' => $blockData,
                    'ecc' => $this->reedSolomonRemainder($blockData, $eccLength),
                ];
            }
        }

        $result = [];
        $maxDataLength = max(array_map(static fn (array $block): int => count($block['data']), $blocks));
        for ($i = 0; $i < $maxDataLength; $i++) {
            foreach ($blocks as $block) {
                if (isset($block['data'][$i])) {
                    $result[] = $block['data'][$i];
                }
            }
        }

        for ($i = 0; $i < $eccLength; $i++) {
            foreach ($blocks as $block) {
                $result[] = $block['ecc'][$i];
            }
        }

        return $result;
    }

    private function drawFunctionPatterns(): void
    {
        $last = $this->size - 7;
        $this->drawFinder(0, 0);
        $this->drawFinder($last, 0);
        $this->drawFinder(0, $last);

        for ($i = 0; $i < $this->size; $i++) {
            if (!$this->functionModules[6][$i]) {
                $this->setFunction($i, 6, $i % 2 === 0);
            }
            if (!$this->functionModules[$i][6]) {
                $this->setFunction(6, $i, $i % 2 === 0);
            }
        }

        foreach (self::ALIGNMENT[$this->version] as $y) {
            foreach (self::ALIGNMENT[$this->version] as $x) {
                if (($x === 6 && $y === 6) || ($x === 6 && $y === $this->size - 7) || ($x === $this->size - 7 && $y === 6)) {
                    continue;
                }
                $this->drawAlignment($x, $y);
            }
        }

        $this->drawFormatBits(0);
        $this->setFunction(8, $this->size - 8, true);

        if ($this->version >= 7) {
            $this->drawVersion();
        }
    }

    private function drawFinder(int $left, int $top): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $x = $left + $dx;
                $y = $top + $dy;
                if ($x < 0 || $x >= $this->size || $y < 0 || $y >= $this->size) {
                    continue;
                }

                $dark = $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6
                    && ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4));
                $this->setFunction($x, $y, $dark);
            }
        }
    }

    private function drawAlignment(int $centerX, int $centerY): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $distance = max(abs($dx), abs($dy));
                $this->setFunction($centerX + $dx, $centerY + $dy, $distance !== 1);
            }
        }
    }

    private function drawFormatBits(int $mask): void
    {
        $bits = $this->bchCode((self::LEVEL_L_FORMAT_BITS << 3) | $mask, 0x537, 10) ^ 0x5412;
        for ($i = 0; $i <= 5; $i++) {
            $this->setFunction(8, $i, (($bits >> $i) & 1) !== 0);
        }
        $this->setFunction(8, 7, (($bits >> 6) & 1) !== 0);
        $this->setFunction(8, 8, (($bits >> 7) & 1) !== 0);
        $this->setFunction(7, 8, (($bits >> 8) & 1) !== 0);
        for ($i = 9; $i < 15; $i++) {
            $this->setFunction(14 - $i, 8, (($bits >> $i) & 1) !== 0);
        }

        for ($i = 0; $i < 8; $i++) {
            $this->setFunction($this->size - 1 - $i, 8, (($bits >> $i) & 1) !== 0);
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFunction(8, $this->size - 15 + $i, (($bits >> $i) & 1) !== 0);
        }
        $this->setFunction(8, $this->size - 8, true);
    }

    private function drawVersion(): void
    {
        $bits = $this->bchCode($this->version, 0x1f25, 12);
        for ($i = 0; $i < 18; $i++) {
            $dark = (($bits >> $i) & 1) !== 0;
            $a = $this->size - 11 + ($i % 3);
            $b = intdiv($i, 3);
            $this->setFunction($a, $b, $dark);
            $this->setFunction($b, $a, $dark);
        }
    }

    private function drawCodewords(array $codewords): void
    {
        $bitIndex = 0;
        $direction = -1;
        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right--;
            }

            for ($vert = 0; $vert < $this->size; $vert++) {
                $y = $direction === -1 ? $this->size - 1 - $vert : $vert;
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    if ($this->functionModules[$y][$x]) {
                        continue;
                    }

                    $byteIndex = intdiv($bitIndex, 8);
                    $bit = $byteIndex < count($codewords) ? (($codewords[$byteIndex] >> (7 - ($bitIndex % 8))) & 1) !== 0 : false;
                    if ($this->mask(0, $x, $y)) {
                        $bit = !$bit;
                    }
                    $this->modules[$y][$x] = $bit;
                    $bitIndex++;
                }
            }

            $direction *= -1;
        }
    }

    private function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    private function reedSolomonRemainder(array $data, int $degree): array
    {
        $divisor = $this->reedSolomonDivisor($degree);
        $result = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $result[0];
            array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coefficient) {
                $result[$i] ^= $this->gfMultiply($coefficient, $factor);
            }
        }

        return $result;
    }

    private function reedSolomonDivisor(int $degree): array
    {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;
        for ($root = 0; $root < $degree; $root++) {
            for ($i = 0; $i < $degree; $i++) {
                $result[$i] = $this->gfMultiply($result[$i], $this->gfPow($root));
                if ($i + 1 < $degree) {
                    $result[$i] ^= $result[$i + 1];
                }
            }
        }

        return $result;
    }

    private function gfMultiply(int $x, int $y): int
    {
        $result = 0;
        for (; $y > 0; $y >>= 1) {
            if (($y & 1) !== 0) {
                $result ^= $x;
            }
            $x <<= 1;
            if (($x & 0x100) !== 0) {
                $x ^= 0x11d;
            }
        }

        return $result & 0xff;
    }

    private function gfPow(int $exponent): int
    {
        $result = 1;
        for ($i = 0; $i < $exponent; $i++) {
            $result = $this->gfMultiply($result, 2);
        }

        return $result;
    }

    private function bchCode(int $data, int $poly, int $degree): int
    {
        $value = $data << $degree;
        for ($i = $this->bitLength($value) - 1; $i >= $degree; $i--) {
            if ((($value >> $i) & 1) !== 0) {
                $value ^= $poly << ($i - $degree);
            }
        }

        return ($data << $degree) | $value;
    }

    private function bitLength(int $value): int
    {
        $length = 0;
        while ($value > 0) {
            $length++;
            $value >>= 1;
        }

        return $length;
    }

    private function mask(int $mask, int $x, int $y): bool
    {
        return match ($mask) {
            0 => (($x + $y) % 2) === 0,
            default => false,
        };
    }

    private function setFunction(int $x, int $y, bool $dark): void
    {
        $this->modules[$y][$x] = $dark;
        $this->functionModules[$y][$x] = true;
    }

    private function dataCapacity(int $version): int
    {
        $capacity = 0;
        foreach (self::BLOCKS[$version]['groups'] as [$count, $dataLength]) {
            $capacity += $count * $dataLength;
        }

        return $capacity;
    }
}
