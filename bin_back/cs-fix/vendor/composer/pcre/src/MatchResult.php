<?php










namespace Composer\Pcre;

final class MatchResult
{
/**
@readonly



*/
public $matches;

/**
@readonly

*/
public $matched;





public function __construct(int $count, array $matches)
{
$this->matches = $matches;
$this->matched = (bool) $count;
}
}
