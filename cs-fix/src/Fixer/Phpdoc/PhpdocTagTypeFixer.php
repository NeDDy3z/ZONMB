<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\Phpdoc;

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
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;

/**
@phpstan-type
@phpstan-type
@implements





*/
final class PhpdocTagTypeFixer extends AbstractFixer implements ConfigurableFixerInterface
{
/**
@use */
use ConfigurableFixerTrait;

private const TAG_REGEX = '/^(?:
        (?<tag>
            (?:@(?<tag_name>.+?)(?:\s.+)?)
        )
        |
        {(?<inlined_tag>
            (?:@(?<inlined_tag_name>.+?)(?:\s.+)?)
        )}
    )$/x';

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound(T_DOC_COMMENT);
}

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Forces PHPDoc tags to be either regular annotations or inline.',
[
new CodeSample(
"<?php\n/**\n * {@api}\n */\n"
),
new CodeSample(
"<?php\n/**\n * @inheritdoc\n */\n",
['tags' => ['inheritdoc' => 'inline']]
),
]
);
}







public function getPriority(): int
{
return 0;
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
if (0 === \count($this->configuration['tags'])) {
return;
}

$regularExpression = \sprintf(
'/({?@(?:%s).*?(?:(?=\s\*\/)|(?=\n)}?))/i',
implode('|', array_map(
static fn (string $tag): string => preg_quote($tag, '/'),
array_keys($this->configuration['tags'])
))
);

foreach ($tokens as $index => $token) {
if (!$token->isGivenKind(T_DOC_COMMENT)) {
continue;
}

$parts = Preg::split(
$regularExpression,
$token->getContent(),
-1,
PREG_SPLIT_DELIM_CAPTURE
);

for ($i = 1, $max = \count($parts) - 1; $i < $max; $i += 2) {
if (!Preg::match(self::TAG_REGEX, $parts[$i], $matches)) {
continue;
}

if ('' !== $matches['tag']) {
$tag = $matches['tag'];
$tagName = $matches['tag_name'];
} else {
$tag = $matches['inlined_tag'];
$tagName = $matches['inlined_tag_name'];
}

$tagName = strtolower($tagName);
if (!isset($this->configuration['tags'][$tagName])) {
continue;
}

if ('inline' === $this->configuration['tags'][$tagName]) {
$parts[$i] = '{'.$tag.'}';

continue;
}

if (!$this->tagIsSurroundedByText($parts, $i)) {
$parts[$i] = $tag;
}
}

$tokens[$index] = new Token([T_DOC_COMMENT, implode('', $parts)]);
}
}

protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
{
return new FixerConfigurationResolver([
(new FixerOptionBuilder('tags', 'The list of tags to fix.'))
->setAllowedTypes(["array<string, 'annotation'|'inline'>"])
->setAllowedValues([static function (array $value): bool {
foreach ($value as $type) {
if (!\in_array($type, ['annotation', 'inline'], true)) {
throw new InvalidOptionsException("Unknown tag type \"{$type}\".");
}
}

return true;
}])
->setDefault([
'api' => 'annotation',
'author' => 'annotation',
'copyright' => 'annotation',
'deprecated' => 'annotation',
'example' => 'annotation',
'global' => 'annotation',
'inheritDoc' => 'annotation',
'internal' => 'annotation',
'license' => 'annotation',
'method' => 'annotation',
'package' => 'annotation',
'param' => 'annotation',
'property' => 'annotation',
'return' => 'annotation',
'see' => 'annotation',
'since' => 'annotation',
'throws' => 'annotation',
'todo' => 'annotation',
'uses' => 'annotation',
'var' => 'annotation',
'version' => 'annotation',
])
->setNormalizer(static function (Options $options, array $value): array {
$normalized = [];

foreach ($value as $tag => $type) {
$normalized[strtolower($tag)] = $type;
}

return $normalized;
})
->getOption(),
]);
}




private function tagIsSurroundedByText(array $parts, int $index): bool
{
return
Preg::match('/(^|\R)\h*[^@\s]\N*/', $this->cleanComment($parts[$index - 1]))
|| Preg::match('/^.*?\R\s*[^@\s]/', $this->cleanComment($parts[$index + 1]));
}

private function cleanComment(string $comment): string
{
$comment = Preg::replace('/^\/\*\*|\*\/$/', '', $comment);

return Preg::replace('/(\R)(\h*\*)?\h*/', '$1', $comment);
}
}