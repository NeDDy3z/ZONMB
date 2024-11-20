<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\FunctionNotation;

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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
@implements
@phpstan-type
@phpstan-type







*/
final class ReturnTypeDeclarationFixer extends AbstractFixer implements ConfigurableFixerInterface
{
/**
@use */
use ConfigurableFixerTrait;

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Adjust spacing around colon in return type declarations and backed enum types.',
[
new CodeSample(
"<?php\nfunction foo(int \$a):string {};\n"
),
new CodeSample(
"<?php\nfunction foo(int \$a):string {};\n",
['space_before' => 'none']
),
new CodeSample(
"<?php\nfunction foo(int \$a):string {};\n",
['space_before' => 'one']
),
],
'Rule is applied only in a PHP 7+ environment.'
);
}






public function getPriority(): int
{
return -17;
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound(CT::T_TYPE_COLON);
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
$oneSpaceBefore = 'one' === $this->configuration['space_before'];

for ($index = 0, $limit = $tokens->count(); $index < $limit; ++$index) {
if (!$tokens[$index]->isGivenKind(CT::T_TYPE_COLON)) {
continue;
}

$previousIndex = $index - 1;
$previousToken = $tokens[$previousIndex];

if ($previousToken->isWhitespace()) {
if (!$tokens[$tokens->getPrevNonWhitespace($index - 1)]->isComment()) {
if ($oneSpaceBefore) {
$tokens[$previousIndex] = new Token([T_WHITESPACE, ' ']);
} else {
$tokens->clearAt($previousIndex);
}
}
} elseif ($oneSpaceBefore) {
$tokenWasAdded = $tokens->ensureWhitespaceAtIndex($index, 0, ' ');

if ($tokenWasAdded) {
++$limit;
}

++$index;
}

++$index;

$tokenWasAdded = $tokens->ensureWhitespaceAtIndex($index, 0, ' ');

if ($tokenWasAdded) {
++$limit;
}
}
}

protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
{
return new FixerConfigurationResolver([
(new FixerOptionBuilder('space_before', 'Spacing to apply before colon.'))
->setAllowedValues(['one', 'none'])
->setDefault('none')
->getOption(),
]);
}
}
