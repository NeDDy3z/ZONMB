<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\ControlStructure;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\BlocksAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;






final class IncludeFixer extends AbstractFixer
{
public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Include/Require and file path should be divided with a single space. File path should not be placed within parentheses.',
[
new CodeSample(
'<?php
require ("sample1.php");
require_once  "sample2.php";
include       "sample3.php";
include_once("sample4.php");
'
),
]
);
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isAnyTokenKindsFound([T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE]);
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
$this->clearIncludies($tokens, $this->findIncludies($tokens));
}




private function clearIncludies(Tokens $tokens, array $includies): void
{
$blocksAnalyzer = new BlocksAnalyzer();

foreach ($includies as $includy) {
if (!$tokens[$includy['end']]->isGivenKind(T_CLOSE_TAG)) {
$afterEndIndex = $tokens->getNextNonWhitespace($includy['end']);

if (null === $afterEndIndex || !$tokens[$afterEndIndex]->isComment()) {
$tokens->removeLeadingWhitespace($includy['end']);
}
}

$braces = $includy['braces'];

if (null !== $braces) {
$prevIndex = $tokens->getPrevMeaningfulToken($includy['begin']);
$nextIndex = $tokens->getNextMeaningfulToken($braces['close']);


if (!$tokens[$nextIndex]->equalsAny([';', [T_CLOSE_TAG]]) && !$blocksAnalyzer->isBlock($tokens, $prevIndex, $nextIndex)) {
continue;
}

$this->removeWhitespaceAroundIfPossible($tokens, $braces['open']);
$this->removeWhitespaceAroundIfPossible($tokens, $braces['close']);
$tokens->clearTokenAndMergeSurroundingWhitespace($braces['open']);
$tokens->clearTokenAndMergeSurroundingWhitespace($braces['close']);
}

$nextIndex = $tokens->getNonEmptySibling($includy['begin'], 1);

if ($tokens[$nextIndex]->isWhitespace()) {
$tokens[$nextIndex] = new Token([T_WHITESPACE, ' ']);
} elseif (null !== $braces || $tokens[$nextIndex]->isGivenKind([T_VARIABLE, T_CONSTANT_ENCAPSED_STRING, T_COMMENT])) {
$tokens->insertAt($includy['begin'] + 1, new Token([T_WHITESPACE, ' ']));
}
}
}




private function findIncludies(Tokens $tokens): array
{
static $includyTokenKinds = [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE];

$includies = [];

foreach ($tokens->findGivenKind($includyTokenKinds) as $includyTokens) {
foreach ($includyTokens as $index => $token) {
$includy = [
'begin' => $index,
'braces' => null,
'end' => $tokens->getNextTokenOfKind($index, [';', [T_CLOSE_TAG]]),
];

$braceOpenIndex = $tokens->getNextMeaningfulToken($index);

if ($tokens[$braceOpenIndex]->equals('(')) {
$braceCloseIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $braceOpenIndex);

$includy['braces'] = [
'open' => $braceOpenIndex,
'close' => $braceCloseIndex,
];
}

$includies[$index] = $includy;
}
}

krsort($includies);

return $includies;
}

private function removeWhitespaceAroundIfPossible(Tokens $tokens, int $index): void
{
$nextIndex = $tokens->getNextNonWhitespace($index);

if (null === $nextIndex || !$tokens[$nextIndex]->isComment()) {
$tokens->removeLeadingWhitespace($index);
}

$prevIndex = $tokens->getPrevNonWhitespace($index);

if (null === $prevIndex || !$tokens[$prevIndex]->isComment()) {
$tokens->removeTrailingWhitespace($index);
}
}
}
