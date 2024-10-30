<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
@implements
@phpstan-type
@phpstan-type





*/
final class OrderedTraitsFixer extends AbstractFixer implements ConfigurableFixerInterface
{
/**
@use */
use ConfigurableFixerTrait;

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Trait `use` statements must be sorted alphabetically.',
[
new CodeSample("<?php class Foo { \nuse Z; use A; }\n"),
new CodeSample(
"<?php class Foo { \nuse Aaa; use AA; }\n",
[
'case_sensitive' => true,
]
),
],
null,
'Risky when depending on order of the imports.'
);
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound(CT::T_USE_TRAIT);
}

public function isRisky(): bool
{
return true;
}

protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
{
return new FixerConfigurationResolver([
(new FixerOptionBuilder('case_sensitive', 'Whether the sorting should be case sensitive.'))
->setAllowedTypes(['bool'])
->setDefault(false)
->getOption(),
]);
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
foreach ($this->findUseStatementsGroups($tokens) as $uses) {
$this->sortUseStatements($tokens, $uses);
}
}




private function findUseStatementsGroups(Tokens $tokens): iterable
{
$uses = [];

for ($index = 1, $max = \count($tokens); $index < $max; ++$index) {
$token = $tokens[$index];

if ($token->isWhitespace() || $token->isComment()) {
continue;
}

if (!$token->isGivenKind(CT::T_USE_TRAIT)) {
if (\count($uses) > 0) {
yield $uses;

$uses = [];
}

continue;
}

$startIndex = $tokens->getNextNonWhitespace($tokens->getPrevMeaningfulToken($index));
$endIndex = $tokens->getNextTokenOfKind($index, [';', '{']);

if ($tokens[$endIndex]->equals('{')) {
$endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $endIndex);
}

$use = [];

for ($i = $startIndex; $i <= $endIndex; ++$i) {
$use[] = $tokens[$i];
}

$uses[$startIndex] = Tokens::fromArray($use);

$index = $endIndex;
}
}




private function sortUseStatements(Tokens $tokens, array $uses): void
{
foreach ($uses as $use) {
$this->sortMultipleTraitsInStatement($use);
}

$this->sort($tokens, $uses);
}

private function sortMultipleTraitsInStatement(Tokens $use): void
{
$traits = [];
$indexOfName = null;
$name = [];

for ($index = 0, $max = \count($use); $index < $max; ++$index) {
$token = $use[$index];

if ($token->isGivenKind([T_STRING, T_NS_SEPARATOR])) {
$name[] = $token;

if (null === $indexOfName) {
$indexOfName = $index;
}

continue;
}

if ($token->equalsAny([',', ';', '{'])) {
$traits[$indexOfName] = Tokens::fromArray($name);

$name = [];
$indexOfName = null;
}

if ($token->equals('{')) {
$index = $use->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
}
}

$this->sort($use, $traits);
}




private function sort(Tokens $tokens, array $elements): void
{
$toTraitName = static function (Tokens $use): string {
$string = '';

foreach ($use as $token) {
if ($token->equalsAny([';', '{'])) {
break;
}

if ($token->isGivenKind([T_NS_SEPARATOR, T_STRING])) {
$string .= $token->getContent();
}
}

return ltrim($string, '\\');
};

$sortedElements = $elements;
uasort(
$sortedElements,
fn (Tokens $useA, Tokens $useB): int => true === $this->configuration['case_sensitive']
? $toTraitName($useA) <=> $toTraitName($useB)
: strcasecmp($toTraitName($useA), $toTraitName($useB))
);

$sortedElements = array_combine(
array_keys($elements),
array_values($sortedElements)
);

$beforeOverrideCount = $tokens->count();

foreach (array_reverse($sortedElements, true) as $index => $tokensToInsert) {
$tokens->overrideRange(
$index,
$index + \count($elements[$index]) - 1,
$tokensToInsert
);
}

if ($beforeOverrideCount < $tokens->count()) {
$tokens->clearEmptyTokens();
}
}
}