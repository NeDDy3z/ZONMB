<?php declare(strict_types=1);








namespace SebastianBergmann\Diff;

use function array_reverse;
use function count;
use function max;
use SplFixedArray;

final class TimeEfficientLongestCommonSubsequenceCalculator implements LongestCommonSubsequenceCalculator
{



public function calculate(array $from, array $to): array
{
$common = [];
$fromLength = count($from);
$toLength = count($to);
$width = $fromLength + 1;
$matrix = new SplFixedArray($width * ($toLength + 1));

for ($i = 0; $i <= $fromLength; ++$i) {
$matrix[$i] = 0;
}

for ($j = 0; $j <= $toLength; ++$j) {
$matrix[$j * $width] = 0;
}

for ($i = 1; $i <= $fromLength; ++$i) {
for ($j = 1; $j <= $toLength; ++$j) {
$o = ($j * $width) + $i;


$firstOrLast = $from[$i - 1] === $to[$j - 1] ? $matrix[$o - $width - 1] + 1 : 0;

if ($matrix[$o - 1] > $matrix[$o - $width]) {
if ($firstOrLast > $matrix[$o - 1]) {
$matrix[$o] = $firstOrLast;
} else {
$matrix[$o] = $matrix[$o - 1];
}
} else {
if ($firstOrLast > $matrix[$o - $width]) {
$matrix[$o] = $firstOrLast;
} else {
$matrix[$o] = $matrix[$o - $width];
}
}
}
}

$i = $fromLength;
$j = $toLength;

while ($i > 0 && $j > 0) {
if ($from[$i - 1] === $to[$j - 1]) {
$common[] = $from[$i - 1];
--$i;
--$j;
} else {
$o = ($j * $width) + $i;

if ($matrix[$o - $width] > $matrix[$o - 1]) {
--$j;
} else {
--$i;
}
}
}

return array_reverse($common);
}
}
