<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\Operator;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
@phpstan-type
@implements
@phpstan-type
@phpstan-type










*/
final class NoUselessConcatOperatorFixer extends AbstractFixer implements ConfigurableFixerInterface
{
/**
@use */
use ConfigurableFixerTrait;

private const STR_DOUBLE_QUOTE = 0;
private const STR_DOUBLE_QUOTE_VAR = 1;
private const STR_SINGLE_QUOTE = 2;

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'There should not be useless concat operations.',
[
new CodeSample("<?php\n\$a = 'a'.'b';\n"),
new CodeSample("<?php\n\$a = 'a'.\"b\";\n", ['juggle_simple_strings' => true]),
],
);
}







public function getPriority(): int
{
return 5;
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound('.') && $tokens->isAnyTokenKindsFound([T_CONSTANT_ENCAPSED_STRING, '"']);
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
for ($index = $tokens->count() - 1; $index > 0; --$index) {
if (!$tokens[$index]->equals('.')) {
continue;
}

$nextMeaningfulTokenIndex = $tokens->getNextMeaningfulToken($index);

if ($this->containsLinebreak($tokens, $index, $nextMeaningfulTokenIndex)) {
continue;
}

$secondOperand = $this->getConcatOperandType($tokens, $nextMeaningfulTokenIndex, 1);

if (null === $secondOperand) {
continue;
}

$prevMeaningfulTokenIndex = $tokens->getPrevMeaningfulToken($index);

if ($this->containsLinebreak($tokens, $prevMeaningfulTokenIndex, $index)) {
continue;
}

$firstOperand = $this->getConcatOperandType($tokens, $prevMeaningfulTokenIndex, -1);

if (null === $firstOperand) {
continue;
}

$this->fixConcatOperation($tokens, $firstOperand, $index, $secondOperand);
}
}

protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
{
return new FixerConfigurationResolver([
(new FixerOptionBuilder('juggle_simple_strings', 'Allow for simple string quote juggling if it results in more concat-operations merges.'))
->setAllowedTypes(['bool'])
->setDefault(false)
->getOption(),
]);
}





private function fixConcatOperation(Tokens $tokens, array $firstOperand, int $concatIndex, array $secondOperand): void
{


if (
(self::STR_DOUBLE_QUOTE === $firstOperand['type'] && self::STR_DOUBLE_QUOTE === $secondOperand['type'])
|| (self::STR_SINGLE_QUOTE === $firstOperand['type'] && self::STR_SINGLE_QUOTE === $secondOperand['type'])
) {
$this->mergeConstantEscapedStringOperands($tokens, $firstOperand, $concatIndex, $secondOperand);

return;
}

if (self::STR_DOUBLE_QUOTE_VAR === $firstOperand['type'] && self::STR_DOUBLE_QUOTE_VAR === $secondOperand['type']) {
if ($this->operandsCanNotBeMerged($tokens, $firstOperand, $secondOperand)) {
return;
}

$this->mergeConstantEscapedStringVarOperands($tokens, $firstOperand, $concatIndex, $secondOperand);

return;
}



$operands = [
[$firstOperand, $secondOperand],
[$secondOperand, $firstOperand],
];

foreach ($operands as $operandPair) {
[$operand1, $operand2] = $operandPair;

if (self::STR_DOUBLE_QUOTE_VAR === $operand1['type'] && self::STR_DOUBLE_QUOTE === $operand2['type']) {
if ($this->operandsCanNotBeMerged($tokens, $operand1, $operand2)) {
return;
}

$this->mergeConstantEscapedStringVarOperands($tokens, $firstOperand, $concatIndex, $secondOperand);

return;
}

if (false === $this->configuration['juggle_simple_strings']) {
continue;
}

if (self::STR_DOUBLE_QUOTE === $operand1['type'] && self::STR_SINGLE_QUOTE === $operand2['type']) {
$operantContent = $tokens[$operand2['start']]->getContent();

if ($this->isSimpleQuotedStringContent($operantContent)) {
$this->mergeConstantEscapedStringOperands($tokens, $firstOperand, $concatIndex, $secondOperand);
}

return;
}

if (self::STR_DOUBLE_QUOTE_VAR === $operand1['type'] && self::STR_SINGLE_QUOTE === $operand2['type']) {
$operantContent = $tokens[$operand2['start']]->getContent();

if ($this->isSimpleQuotedStringContent($operantContent)) {
if ($this->operandsCanNotBeMerged($tokens, $operand1, $operand2)) {
return;
}

$this->mergeConstantEscapedStringVarOperands($tokens, $firstOperand, $concatIndex, $secondOperand);
}

return;
}
}
}






private function getConcatOperandType(Tokens $tokens, int $index, int $direction): ?array
{
if ($tokens[$index]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
$firstChar = $tokens[$index]->getContent();

if ('b' === $firstChar[0] || 'B' === $firstChar[0]) {
return null; 
}

return [
'start' => $index,
'end' => $index,
'type' => '"' === $firstChar[0] ? self::STR_DOUBLE_QUOTE : self::STR_SINGLE_QUOTE,
];
}

if ($tokens[$index]->equals('"')) {
$end = $tokens->getTokenOfKindSibling($index, $direction, ['"']);

return [
'start' => 1 === $direction ? $index : $end,
'end' => 1 === $direction ? $end : $index,
'type' => self::STR_DOUBLE_QUOTE_VAR,
];
}

return null;
}





private function mergeConstantEscapedStringOperands(
Tokens $tokens,
array $firstOperand,
int $concatOperatorIndex,
array $secondOperand
): void {
$quote = self::STR_DOUBLE_QUOTE === $firstOperand['type'] || self::STR_DOUBLE_QUOTE === $secondOperand['type'] ? '"' : "'";
$firstOperandTokenContent = $tokens[$firstOperand['start']]->getContent();
$secondOperandTokenContent = $tokens[$secondOperand['start']]->getContent();

$tokens[$firstOperand['start']] = new Token(
[
T_CONSTANT_ENCAPSED_STRING,
$quote.substr($firstOperandTokenContent, 1, -1).substr($secondOperandTokenContent, 1, -1).$quote,
],
);

$this->clearConcatAndAround($tokens, $concatOperatorIndex);
$tokens->clearTokenAndMergeSurroundingWhitespace($secondOperand['start']);
}





private function mergeConstantEscapedStringVarOperands(
Tokens $tokens,
array $firstOperand,
int $concatOperatorIndex,
array $secondOperand
): void {

$newContent = '';

foreach ([$firstOperand, $secondOperand] as $operant) {
$operandContent = '';

for ($i = $operant['start']; $i <= $operant['end'];) {
$operandContent .= $tokens[$i]->getContent();
$i = $tokens->getNextMeaningfulToken($i);
}

$newContent .= substr($operandContent, 1, -1);
}



for ($i = $secondOperand['end']; $i >= $secondOperand['start'];) {
$tokens->clearTokenAndMergeSurroundingWhitespace($i);
$i = $tokens->getPrevMeaningfulToken($i);
}

$this->clearConcatAndAround($tokens, $concatOperatorIndex);

for ($i = $firstOperand['end']; $i > $firstOperand['start'];) {
$tokens->clearTokenAndMergeSurroundingWhitespace($i);
$i = $tokens->getPrevMeaningfulToken($i);
}



$newTokens = Tokens::fromCode('<?php "'.$newContent.'";');
$newTokensCount = \count($newTokens);

$insertTokens = [];

for ($i = 1; $i < $newTokensCount - 1; ++$i) {
$insertTokens[] = $newTokens[$i];
}

$tokens->overrideRange($firstOperand['start'], $firstOperand['start'], $insertTokens);
}

private function clearConcatAndAround(Tokens $tokens, int $concatOperatorIndex): void
{
if ($tokens[$concatOperatorIndex + 1]->isWhitespace()) {
$tokens->clearTokenAndMergeSurroundingWhitespace($concatOperatorIndex + 1);
}

$tokens->clearTokenAndMergeSurroundingWhitespace($concatOperatorIndex);

if ($tokens[$concatOperatorIndex - 1]->isWhitespace()) {
$tokens->clearTokenAndMergeSurroundingWhitespace($concatOperatorIndex - 1);
}
}

private function isSimpleQuotedStringContent(string $candidate): bool
{
return !Preg::match('#[\$"\'\\\]#', substr($candidate, 1, -1));
}

private function containsLinebreak(Tokens $tokens, int $startIndex, int $endIndex): bool
{
for ($i = $endIndex; $i > $startIndex; --$i) {
if (Preg::match('/\R/', $tokens[$i]->getContent())) {
return true;
}
}

return false;
}





private function operandsCanNotBeMerged(Tokens $tokens, array $firstOperand, array $secondOperand): bool
{

if (self::STR_DOUBLE_QUOTE_VAR !== $firstOperand['type']) {
return false;
}
if (!$tokens[$firstOperand['end'] - 1]->isGivenKind(T_VARIABLE)) {
return false;
}

$allowedPatternsForSecondOperand = [
'/^ .*/', 
'/^-(?!\>)/', 
];



foreach ($allowedPatternsForSecondOperand as $allowedPattern) {
$secondOperandInnerContent = substr($tokens->generatePartialCode($secondOperand['start'], $secondOperand['end']), 1, -1);

if (Preg::match($allowedPattern, $secondOperandInnerContent)) {
return false;
}
}

return true;
}
}
