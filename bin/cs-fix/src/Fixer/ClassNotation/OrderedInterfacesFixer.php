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
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
@implements
@phpstan-type
@phpstan-type











*/
final class OrderedInterfacesFixer extends AbstractFixer implements ConfigurableFixerInterface
{
/**
@use */
use ConfigurableFixerTrait;


public const OPTION_DIRECTION = 'direction';


public const OPTION_ORDER = 'order';


public const DIRECTION_ASCEND = 'ascend';


public const DIRECTION_DESCEND = 'descend';


public const ORDER_ALPHA = 'alpha';


public const ORDER_LENGTH = 'length';






private const SUPPORTED_DIRECTION_OPTIONS = [
self::DIRECTION_ASCEND,
self::DIRECTION_DESCEND,
];






private const SUPPORTED_ORDER_OPTIONS = [
self::ORDER_ALPHA,
self::ORDER_LENGTH,
];

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Orders the interfaces in an `implements` or `interface extends` clause.',
[
new CodeSample(
"<?php\n\nfinal class ExampleA implements Gamma, Alpha, Beta {}\n\ninterface ExampleB extends Gamma, Alpha, Beta {}\n"
),
new CodeSample(
"<?php\n\nfinal class ExampleA implements Gamma, Alpha, Beta {}\n\ninterface ExampleB extends Gamma, Alpha, Beta {}\n",
[self::OPTION_DIRECTION => self::DIRECTION_DESCEND]
),
new CodeSample(
"<?php\n\nfinal class ExampleA implements MuchLonger, Short, Longer {}\n\ninterface ExampleB extends MuchLonger, Short, Longer {}\n",
[self::OPTION_ORDER => self::ORDER_LENGTH]
),
new CodeSample(
"<?php\n\nfinal class ExampleA implements MuchLonger, Short, Longer {}\n\ninterface ExampleB extends MuchLonger, Short, Longer {}\n",
[
self::OPTION_ORDER => self::ORDER_LENGTH,
self::OPTION_DIRECTION => self::DIRECTION_DESCEND,
]
),
new CodeSample(
"<?php\n\nfinal class ExampleA implements IgnorecaseB, IgNoReCaSeA, IgnoreCaseC {}\n\ninterface ExampleB extends IgnorecaseB, IgNoReCaSeA, IgnoreCaseC {}\n",
[
self::OPTION_ORDER => self::ORDER_ALPHA,
]
),
new CodeSample(
"<?php\n\nfinal class ExampleA implements Casesensitivea, CaseSensitiveA, CasesensitiveA {}\n\ninterface ExampleB extends Casesensitivea, CaseSensitiveA, CasesensitiveA {}\n",
[
self::OPTION_ORDER => self::ORDER_ALPHA,
'case_sensitive' => true,
]
),
],
);
}






public function getPriority(): int
{
return 0;
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound(T_IMPLEMENTS)
|| $tokens->isAllTokenKindsFound([T_INTERFACE, T_EXTENDS]);
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
foreach ($tokens as $index => $token) {
if (!$token->isGivenKind(T_IMPLEMENTS)) {
if (!$token->isGivenKind(T_EXTENDS)) {
continue;
}

$nameTokenIndex = $tokens->getPrevMeaningfulToken($index);
$interfaceTokenIndex = $tokens->getPrevMeaningfulToken($nameTokenIndex);
$interfaceToken = $tokens[$interfaceTokenIndex];

if (!$interfaceToken->isGivenKind(T_INTERFACE)) {
continue;
}
}

$implementsStart = $index + 1;
$implementsEnd = $tokens->getPrevMeaningfulToken($tokens->getNextTokenOfKind($implementsStart, ['{']));

$interfaces = $this->getInterfaces($tokens, $implementsStart, $implementsEnd);

if (1 === \count($interfaces)) {
continue;
}

foreach ($interfaces as $interfaceIndex => $interface) {
$interfaceTokens = Tokens::fromArray($interface);
$normalized = '';
$actualInterfaceIndex = $interfaceTokens->getNextMeaningfulToken(-1);

while ($interfaceTokens->offsetExists($actualInterfaceIndex)) {
$token = $interfaceTokens[$actualInterfaceIndex];

if ($token->isComment() || $token->isWhitespace()) {
break;
}

$normalized .= str_replace('\\', ' ', $token->getContent());
++$actualInterfaceIndex;
}

$interfaces[$interfaceIndex] = [
'tokens' => $interface,
'normalized' => $normalized,
'originalIndex' => $interfaceIndex,
];
}

usort($interfaces, function (array $first, array $second): int {
$score = self::ORDER_LENGTH === $this->configuration[self::OPTION_ORDER]
? \strlen($first['normalized']) - \strlen($second['normalized'])
: (
true === $this->configuration['case_sensitive']
? $first['normalized'] <=> $second['normalized']
: strcasecmp($first['normalized'], $second['normalized'])
);

if (self::DIRECTION_DESCEND === $this->configuration[self::OPTION_DIRECTION]) {
$score *= -1;
}

return $score;
});

$changed = false;

foreach ($interfaces as $interfaceIndex => $interface) {
if ($interface['originalIndex'] !== $interfaceIndex) {
$changed = true;

break;
}
}

if (!$changed) {
continue;
}

$newTokens = array_shift($interfaces)['tokens'];

foreach ($interfaces as $interface) {
array_push($newTokens, new Token(','), ...$interface['tokens']);
}

$tokens->overrideRange($implementsStart, $implementsEnd, $newTokens);
}
}

protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
{
return new FixerConfigurationResolver([
(new FixerOptionBuilder(self::OPTION_ORDER, 'How the interfaces should be ordered.'))
->setAllowedValues(self::SUPPORTED_ORDER_OPTIONS)
->setDefault(self::ORDER_ALPHA)
->getOption(),
(new FixerOptionBuilder(self::OPTION_DIRECTION, 'Which direction the interfaces should be ordered.'))
->setAllowedValues(self::SUPPORTED_DIRECTION_OPTIONS)
->setDefault(self::DIRECTION_ASCEND)
->getOption(),
(new FixerOptionBuilder('case_sensitive', 'Whether the sorting should be case sensitive.'))
->setAllowedTypes(['bool'])
->setDefault(false)
->getOption(),
]);
}




private function getInterfaces(Tokens $tokens, int $implementsStart, int $implementsEnd): array
{
$interfaces = [];
$interfaceIndex = 0;

for ($i = $implementsStart; $i <= $implementsEnd; ++$i) {
if ($tokens[$i]->equals(',')) {
++$interfaceIndex;
$interfaces[$interfaceIndex] = [];

continue;
}

$interfaces[$interfaceIndex][] = $tokens[$i];
}

return $interfaces;
}
}