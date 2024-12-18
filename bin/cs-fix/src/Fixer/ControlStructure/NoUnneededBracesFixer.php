<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\ControlStructure;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
@implements
@phpstan-type
@phpstan-type





*/
final class NoUnneededBracesFixer extends AbstractFixer implements ConfigurableFixerInterface
{
/**
@use */
use ConfigurableFixerTrait;

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Removes unneeded braces that are superfluous and aren\'t part of a control structure\'s body.',
[
new CodeSample(
'<?php {
    echo 1;
}

switch ($b) {
    case 1: {
        break;
    }
}
'
),
new CodeSample(
'<?php
namespace Foo {
    function Bar(){}
}
',
['namespaces' => true]
),
]
);
}






public function getPriority(): int
{
return 40;
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound('}');
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
foreach ($this->findBraceOpen($tokens) as $index) {
if ($this->isOverComplete($tokens, $index)) {
$this->clearOverCompleteBraces($tokens, $index, $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index));
}
}

if (true === $this->configuration['namespaces']) {
$this->clearIfIsOverCompleteNamespaceBlock($tokens);
}
}

protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
{
return new FixerConfigurationResolver([
(new FixerOptionBuilder('namespaces', 'Remove unneeded braces from bracketed namespaces.'))
->setAllowedTypes(['bool'])
->setDefault(false)
->getOption(),
]);
}





private function clearOverCompleteBraces(Tokens $tokens, int $openIndex, int $closeIndex): void
{
$tokens->clearTokenAndMergeSurroundingWhitespace($closeIndex);
$tokens->clearTokenAndMergeSurroundingWhitespace($openIndex);
}




private function findBraceOpen(Tokens $tokens): iterable
{
for ($i = \count($tokens) - 1; $i > 0; --$i) {
if ($tokens[$i]->equals('{')) {
yield $i;
}
}
}




private function isOverComplete(Tokens $tokens, int $index): bool
{
static $include = ['{', '}', [T_OPEN_TAG], ':', ';'];

return $tokens[$tokens->getPrevMeaningfulToken($index)]->equalsAny($include);
}

private function clearIfIsOverCompleteNamespaceBlock(Tokens $tokens): void
{
if (1 !== $tokens->countTokenKind(T_NAMESPACE)) {
return; 
}

$index = $tokens->getNextTokenOfKind(0, [[T_NAMESPACE]]);

$namespaceIsNamed = false;

$index = $tokens->getNextMeaningfulToken($index);
while ($tokens[$index]->isGivenKind([T_STRING, T_NS_SEPARATOR])) {
$index = $tokens->getNextMeaningfulToken($index);
$namespaceIsNamed = true;
}

if (!$namespaceIsNamed) {
return;
}

if (!$tokens[$index]->equals('{')) {
return; 
}

$closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);
$afterCloseIndex = $tokens->getNextMeaningfulToken($closeIndex);

if (null !== $afterCloseIndex && (!$tokens[$afterCloseIndex]->isGivenKind(T_CLOSE_TAG) || null !== $tokens->getNextMeaningfulToken($afterCloseIndex))) {
return;
}


$tokens->clearTokenAndMergeSurroundingWhitespace($closeIndex);
$tokens[$index] = new Token(';');

if ($tokens[$index - 1]->isWhitespace(" \t") && !$tokens[$index - 2]->isComment()) {
$tokens->clearTokenAndMergeSurroundingWhitespace($index - 1);
}
}
}
