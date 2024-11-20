<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\FunctionNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;




final class UseArrowFunctionsFixer extends AbstractFixer
{
public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Anonymous functions with one-liner return statement must use arrow functions.',
[
new CodeSample(
<<<'SAMPLE'
                        <?php
                        foo(function ($a) use ($b) {
                            return $a + $b;
                        });

                        SAMPLE
,
),
],
null,
'Risky when using `isset()` on outside variables that are not imported with `use ()`.'
);
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isAllTokenKindsFound([T_FUNCTION, T_RETURN]);
}

public function isRisky(): bool
{
return true;
}






public function getPriority(): int
{
return 32;
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
$analyzer = new TokensAnalyzer($tokens);

for ($index = $tokens->count() - 1; $index > 0; --$index) {
if (!$tokens[$index]->isGivenKind(T_FUNCTION) || !$analyzer->isLambda($index)) {
continue;
}




$parametersStart = $tokens->getNextMeaningfulToken($index);

if ($tokens[$parametersStart]->isGivenKind(CT::T_RETURN_REF)) {
$parametersStart = $tokens->getNextMeaningfulToken($parametersStart);
}

$parametersEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parametersStart);

if ($this->isMultilined($tokens, $parametersStart, $parametersEnd)) {
continue;
}




$next = $tokens->getNextMeaningfulToken($parametersEnd);

$useStart = null;
$useEnd = null;

if ($tokens[$next]->isGivenKind(CT::T_USE_LAMBDA)) {
$useStart = $next;

if ($tokens[$useStart - 1]->isGivenKind(T_WHITESPACE)) {
--$useStart;
}

$next = $tokens->getNextMeaningfulToken($next);

while (!$tokens[$next]->equals(')')) {
if ($tokens[$next]->equals('&')) {

continue 2;
}

$next = $tokens->getNextMeaningfulToken($next);
}

$useEnd = $next;
$next = $tokens->getNextMeaningfulToken($next);
}




$braceOpen = $tokens[$next]->equals('{') ? $next : $tokens->getNextTokenOfKind($next, ['{']);
$return = $braceOpen + 1;

if ($tokens[$return]->isGivenKind(T_WHITESPACE)) {
++$return;
}

if (!$tokens[$return]->isGivenKind(T_RETURN)) {
continue;
}



$semicolon = $tokens->getNextTokenOfKind($return, ['{', ';']);

if (!$tokens[$semicolon]->equals(';')) {
continue;
}




$braceClose = $semicolon + 1;

if ($tokens[$braceClose]->isGivenKind(T_WHITESPACE)) {
++$braceClose;
}

if (!$tokens[$braceClose]->equals('}')) {
continue;
}



if ($this->isMultilined($tokens, $return, $semicolon)) {
continue;
}



$this->transform($tokens, $index, $useStart, $useEnd, $braceOpen, $return, $semicolon, $braceClose);
}
}

private function isMultilined(Tokens $tokens, int $start, int $end): bool
{
for ($i = $start; $i < $end; ++$i) {
if (str_contains($tokens[$i]->getContent(), "\n")) {
return true;
}
}

return false;
}

private function transform(Tokens $tokens, int $index, ?int $useStart, ?int $useEnd, int $braceOpen, int $return, int $semicolon, int $braceClose): void
{
$tokensToInsert = [new Token([T_DOUBLE_ARROW, '=>'])];

if ($tokens->getNextMeaningfulToken($return) === $semicolon) {
$tokensToInsert[] = new Token([T_WHITESPACE, ' ']);
$tokensToInsert[] = new Token([T_STRING, 'null']);
}

$tokens->clearRange($semicolon, $braceClose);
$tokens->clearRange($braceOpen + 1, $return);
$tokens->overrideRange($braceOpen, $braceOpen, $tokensToInsert);

if (null !== $useStart) {
$tokens->clearRange($useStart, $useEnd);
}

$tokens[$index] = new Token([T_FN, 'fn']);
}
}
