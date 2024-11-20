<?php










namespace Symfony\Component\String;

use Symfony\Component\String\Exception\ExceptionInterface;
use Symfony\Component\String\Exception\InvalidArgumentException;
use Symfony\Component\String\Exception\RuntimeException;













abstract class AbstractString implements \Stringable, \JsonSerializable
{
public const PREG_PATTERN_ORDER = \PREG_PATTERN_ORDER;
public const PREG_SET_ORDER = \PREG_SET_ORDER;
public const PREG_OFFSET_CAPTURE = \PREG_OFFSET_CAPTURE;
public const PREG_UNMATCHED_AS_NULL = \PREG_UNMATCHED_AS_NULL;

public const PREG_SPLIT = 0;
public const PREG_SPLIT_NO_EMPTY = \PREG_SPLIT_NO_EMPTY;
public const PREG_SPLIT_DELIM_CAPTURE = \PREG_SPLIT_DELIM_CAPTURE;
public const PREG_SPLIT_OFFSET_CAPTURE = \PREG_SPLIT_OFFSET_CAPTURE;

protected $string = '';
protected $ignoreCase = false;

abstract public function __construct(string $string = '');






public static function unwrap(array $values): array
{
foreach ($values as $k => $v) {
if ($v instanceof self) {
$values[$k] = $v->__toString();
} elseif (\is_array($v) && $values[$k] !== $v = static::unwrap($v)) {
$values[$k] = $v;
}
}

return $values;
}






public static function wrap(array $values): array
{
$i = 0;
$keys = null;

foreach ($values as $k => $v) {
if (\is_string($k) && '' !== $k && $k !== $j = (string) new static($k)) {
$keys = $keys ?? array_keys($values);
$keys[$i] = $j;
}

if (\is_string($v)) {
$values[$k] = new static($v);
} elseif (\is_array($v) && $values[$k] !== $v = static::wrap($v)) {
$values[$k] = $v;
}

++$i;
}

return null !== $keys ? array_combine($keys, $values) : $values;
}






public function after($needle, bool $includeNeedle = false, int $offset = 0): self
{
$str = clone $this;
$i = \PHP_INT_MAX;

foreach ((array) $needle as $n) {
$n = (string) $n;
$j = $this->indexOf($n, $offset);

if (null !== $j && $j < $i) {
$i = $j;
$str->string = $n;
}
}

if (\PHP_INT_MAX === $i) {
return $str;
}

if (!$includeNeedle) {
$i += $str->length();
}

return $this->slice($i);
}






public function afterLast($needle, bool $includeNeedle = false, int $offset = 0): self
{
$str = clone $this;
$i = null;

foreach ((array) $needle as $n) {
$n = (string) $n;
$j = $this->indexOfLast($n, $offset);

if (null !== $j && $j >= $i) {
$i = $offset = $j;
$str->string = $n;
}
}

if (null === $i) {
return $str;
}

if (!$includeNeedle) {
$i += $str->length();
}

return $this->slice($i);
}




abstract public function append(string ...$suffix): self;






public function before($needle, bool $includeNeedle = false, int $offset = 0): self
{
$str = clone $this;
$i = \PHP_INT_MAX;

foreach ((array) $needle as $n) {
$n = (string) $n;
$j = $this->indexOf($n, $offset);

if (null !== $j && $j < $i) {
$i = $j;
$str->string = $n;
}
}

if (\PHP_INT_MAX === $i) {
return $str;
}

if ($includeNeedle) {
$i += $str->length();
}

return $this->slice(0, $i);
}






public function beforeLast($needle, bool $includeNeedle = false, int $offset = 0): self
{
$str = clone $this;
$i = null;

foreach ((array) $needle as $n) {
$n = (string) $n;
$j = $this->indexOfLast($n, $offset);

if (null !== $j && $j >= $i) {
$i = $offset = $j;
$str->string = $n;
}
}

if (null === $i) {
return $str;
}

if ($includeNeedle) {
$i += $str->length();
}

return $this->slice(0, $i);
}




public function bytesAt(int $offset): array
{
$str = $this->slice($offset, 1);

return '' === $str->string ? [] : array_values(unpack('C*', $str->string));
}




abstract public function camel(): self;




abstract public function chunk(int $length = 1): array;




public function collapseWhitespace(): self
{
$str = clone $this;
$str->string = trim(preg_replace("/(?:[ \n\r\t\x0C]{2,}+|[\n\r\t\x0C])/", ' ', $str->string), " \n\r\t\x0C");

return $str;
}




public function containsAny($needle): bool
{
return null !== $this->indexOf($needle);
}




public function endsWith($suffix): bool
{
if (!\is_array($suffix) && !$suffix instanceof \Traversable) {
throw new \TypeError(sprintf('Method "%s()" must be overridden by class "%s" to deal with non-iterable values.', __FUNCTION__, static::class));
}

foreach ($suffix as $s) {
if ($this->endsWith((string) $s)) {
return true;
}
}

return false;
}




public function ensureEnd(string $suffix): self
{
if (!$this->endsWith($suffix)) {
return $this->append($suffix);
}

$suffix = preg_quote($suffix);
$regex = '{('.$suffix.')(?:'.$suffix.')++$}D';

return $this->replaceMatches($regex.($this->ignoreCase ? 'i' : ''), '$1');
}




public function ensureStart(string $prefix): self
{
$prefix = new static($prefix);

if (!$this->startsWith($prefix)) {
return $this->prepend($prefix);
}

$str = clone $this;
$i = $prefixLen = $prefix->length();

while ($this->indexOf($prefix, $i) === $i) {
$str = $str->slice($prefixLen);
$i += $prefixLen;
}

return $str;
}




public function equalsTo($string): bool
{
if (!\is_array($string) && !$string instanceof \Traversable) {
throw new \TypeError(sprintf('Method "%s()" must be overridden by class "%s" to deal with non-iterable values.', __FUNCTION__, static::class));
}

foreach ($string as $s) {
if ($this->equalsTo((string) $s)) {
return true;
}
}

return false;
}




abstract public function folded(): self;




public function ignoreCase(): self
{
$str = clone $this;
$str->ignoreCase = true;

return $str;
}




public function indexOf($needle, int $offset = 0): ?int
{
if (!\is_array($needle) && !$needle instanceof \Traversable) {
throw new \TypeError(sprintf('Method "%s()" must be overridden by class "%s" to deal with non-iterable values.', __FUNCTION__, static::class));
}

$i = \PHP_INT_MAX;

foreach ($needle as $n) {
$j = $this->indexOf((string) $n, $offset);

if (null !== $j && $j < $i) {
$i = $j;
}
}

return \PHP_INT_MAX === $i ? null : $i;
}




public function indexOfLast($needle, int $offset = 0): ?int
{
if (!\is_array($needle) && !$needle instanceof \Traversable) {
throw new \TypeError(sprintf('Method "%s()" must be overridden by class "%s" to deal with non-iterable values.', __FUNCTION__, static::class));
}

$i = null;

foreach ($needle as $n) {
$j = $this->indexOfLast((string) $n, $offset);

if (null !== $j && $j >= $i) {
$i = $offset = $j;
}
}

return $i;
}

public function isEmpty(): bool
{
return '' === $this->string;
}




abstract public function join(array $strings, ?string $lastGlue = null): self;

public function jsonSerialize(): string
{
return $this->string;
}

abstract public function length(): int;




abstract public function lower(): self;








abstract public function match(string $regexp, int $flags = 0, int $offset = 0): array;




abstract public function padBoth(int $length, string $padStr = ' '): self;




abstract public function padEnd(int $length, string $padStr = ' '): self;




abstract public function padStart(int $length, string $padStr = ' '): self;




abstract public function prepend(string ...$prefix): self;




public function repeat(int $multiplier): self
{
if (0 > $multiplier) {
throw new InvalidArgumentException(sprintf('Multiplier must be positive, %d given.', $multiplier));
}

$str = clone $this;
$str->string = str_repeat($str->string, $multiplier);

return $str;
}




abstract public function replace(string $from, string $to): self;






abstract public function replaceMatches(string $fromRegexp, $to): self;




abstract public function reverse(): self;




abstract public function slice(int $start = 0, ?int $length = null): self;




abstract public function snake(): self;




abstract public function splice(string $replacement, int $start = 0, ?int $length = null): self;




public function split(string $delimiter, ?int $limit = null, ?int $flags = null): array
{
if (null === $flags) {
throw new \TypeError('Split behavior when $flags is null must be implemented by child classes.');
}

if ($this->ignoreCase) {
$delimiter .= 'i';
}

set_error_handler(static function ($t, $m) { throw new InvalidArgumentException($m); });

try {
if (false === $chunks = preg_split($delimiter, $this->string, $limit, $flags)) {
$lastError = preg_last_error();

foreach (get_defined_constants(true)['pcre'] as $k => $v) {
if ($lastError === $v && '_ERROR' === substr($k, -6)) {
throw new RuntimeException('Splitting failed with '.$k.'.');
}
}

throw new RuntimeException('Splitting failed with unknown error code.');
}
} finally {
restore_error_handler();
}

$str = clone $this;

if (self::PREG_SPLIT_OFFSET_CAPTURE & $flags) {
foreach ($chunks as &$chunk) {
$str->string = $chunk[0];
$chunk[0] = clone $str;
}
} else {
foreach ($chunks as &$chunk) {
$str->string = $chunk;
$chunk = clone $str;
}
}

return $chunks;
}




public function startsWith($prefix): bool
{
if (!\is_array($prefix) && !$prefix instanceof \Traversable) {
throw new \TypeError(sprintf('Method "%s()" must be overridden by class "%s" to deal with non-iterable values.', __FUNCTION__, static::class));
}

foreach ($prefix as $prefix) {
if ($this->startsWith((string) $prefix)) {
return true;
}
}

return false;
}




abstract public function title(bool $allWords = false): self;

public function toByteString(?string $toEncoding = null): ByteString
{
$b = new ByteString();

$toEncoding = \in_array($toEncoding, ['utf8', 'utf-8', 'UTF8'], true) ? 'UTF-8' : $toEncoding;

if (null === $toEncoding || $toEncoding === $fromEncoding = $this instanceof AbstractUnicodeString || preg_match('//u', $b->string) ? 'UTF-8' : 'Windows-1252') {
$b->string = $this->string;

return $b;
}

set_error_handler(static function ($t, $m) { throw new InvalidArgumentException($m); });

try {
try {
$b->string = mb_convert_encoding($this->string, $toEncoding, 'UTF-8');
} catch (InvalidArgumentException|\ValueError $e) {
if (!\function_exists('iconv')) {
if ($e instanceof \ValueError) {
throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
}
throw $e;
}

$b->string = iconv('UTF-8', $toEncoding, $this->string);
}
} finally {
restore_error_handler();
}

return $b;
}

public function toCodePointString(): CodePointString
{
return new CodePointString($this->string);
}

public function toString(): string
{
return $this->string;
}

public function toUnicodeString(): UnicodeString
{
return new UnicodeString($this->string);
}




abstract public function trim(string $chars = " \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}"): self;




abstract public function trimEnd(string $chars = " \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}"): self;






public function trimPrefix($prefix): self
{
if (\is_array($prefix) || $prefix instanceof \Traversable) {
foreach ($prefix as $s) {
$t = $this->trimPrefix($s);

if ($t->string !== $this->string) {
return $t;
}
}

return clone $this;
}

$str = clone $this;

if ($prefix instanceof self) {
$prefix = $prefix->string;
} else {
$prefix = (string) $prefix;
}

if ('' !== $prefix && \strlen($this->string) >= \strlen($prefix) && 0 === substr_compare($this->string, $prefix, 0, \strlen($prefix), $this->ignoreCase)) {
$str->string = substr($this->string, \strlen($prefix));
}

return $str;
}




abstract public function trimStart(string $chars = " \t\n\r\0\x0B\x0C\u{A0}\u{FEFF}"): self;






public function trimSuffix($suffix): self
{
if (\is_array($suffix) || $suffix instanceof \Traversable) {
foreach ($suffix as $s) {
$t = $this->trimSuffix($s);

if ($t->string !== $this->string) {
return $t;
}
}

return clone $this;
}

$str = clone $this;

if ($suffix instanceof self) {
$suffix = $suffix->string;
} else {
$suffix = (string) $suffix;
}

if ('' !== $suffix && \strlen($this->string) >= \strlen($suffix) && 0 === substr_compare($this->string, $suffix, -\strlen($suffix), null, $this->ignoreCase)) {
$str->string = substr($this->string, 0, -\strlen($suffix));
}

return $str;
}




public function truncate(int $length, string $ellipsis = '', bool $cut = true): self
{
$stringLength = $this->length();

if ($stringLength <= $length) {
return clone $this;
}

$ellipsisLength = '' !== $ellipsis ? (new static($ellipsis))->length() : 0;

if ($length < $ellipsisLength) {
$ellipsisLength = 0;
}

if (!$cut) {
if (null === $length = $this->indexOf([' ', "\r", "\n", "\t"], ($length ?: 1) - 1)) {
return clone $this;
}

$length += $ellipsisLength;
}

$str = $this->slice(0, $length - $ellipsisLength);

return $ellipsisLength ? $str->trimEnd()->append($ellipsis) : $str;
}




abstract public function upper(): self;




abstract public function width(bool $ignoreAnsiDecoration = true): int;




public function wordwrap(int $width = 75, string $break = "\n", bool $cut = false): self
{
$lines = '' !== $break ? $this->split($break) : [clone $this];
$chars = [];
$mask = '';

if (1 === \count($lines) && '' === $lines[0]->string) {
return $lines[0];
}

foreach ($lines as $i => $line) {
if ($i) {
$chars[] = $break;
$mask .= '#';
}

foreach ($line->chunk() as $char) {
$chars[] = $char->string;
$mask .= ' ' === $char->string ? ' ' : '?';
}
}

$string = '';
$j = 0;
$b = $i = -1;
$mask = wordwrap($mask, $width, '#', $cut);

while (false !== $b = strpos($mask, '#', $b + 1)) {
for (++$i; $i < $b; ++$i) {
$string .= $chars[$j];
unset($chars[$j++]);
}

if ($break === $chars[$j] || ' ' === $chars[$j]) {
unset($chars[$j++]);
}

$string .= $break;
}

$str = clone $this;
$str->string = $string.implode('', $chars);

return $str;
}

public function __sleep(): array
{
return ['string'];
}

public function __clone()
{
$this->ignoreCase = false;
}

public function __toString(): string
{
return $this->string;
}
}
