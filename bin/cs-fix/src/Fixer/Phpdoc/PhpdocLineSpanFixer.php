<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\WhitespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

/**
@implements
@phpstan-type
@phpstan-type











*/
final class PhpdocLineSpanFixer extends AbstractFixer implements WhitespacesAwareFixerInterface, ConfigurableFixerInterface
{
/**
@use */
use ConfigurableFixerTrait;

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Changes doc blocks from single to multi line, or reversed. Works for class constants, properties and methods only.',
[
new CodeSample("<?php\n\nclass Foo{\n    /** @var bool */\n    public \$var;\n}\n"),
new CodeSample(
"<?php\n\nclass Foo{\n    /**\n    * @var bool\n    */\n    public \$var;\n}\n",
['property' => 'single']
),
]
);
}







public function getPriority(): int
{
return 7;
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound(T_DOC_COMMENT);
}

protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
{
return new FixerConfigurationResolver([
(new FixerOptionBuilder('const', 'Whether const blocks should be single or multi line.'))
->setAllowedValues(['single', 'multi', null])
->setDefault('multi')
->getOption(),
(new FixerOptionBuilder('property', 'Whether property doc blocks should be single or multi line.'))
->setAllowedValues(['single', 'multi', null])
->setDefault('multi')
->getOption(),
(new FixerOptionBuilder('method', 'Whether method doc blocks should be single or multi line.'))
->setAllowedValues(['single', 'multi', null])
->setDefault('multi')
->getOption(),
]);
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
$analyzer = new TokensAnalyzer($tokens);

foreach ($analyzer->getClassyElements() as $index => $element) {
if (!$this->hasDocBlock($tokens, $index)) {
continue;
}

$type = $element['type'];

if (!isset($this->configuration[$type])) {
continue;
}

$docIndex = $this->getDocBlockIndex($tokens, $index);
$doc = new DocBlock($tokens[$docIndex]->getContent());

if ('multi' === $this->configuration[$type]) {
$doc->makeMultiLine(WhitespacesAnalyzer::detectIndent($tokens, $docIndex), $this->whitespacesConfig->getLineEnding());
} elseif ('single' === $this->configuration[$type]) {
$doc->makeSingleLine();
}

$tokens->offsetSet($docIndex, new Token([T_DOC_COMMENT, $doc->getContent()]));
}
}

private function hasDocBlock(Tokens $tokens, int $index): bool
{
$docBlockIndex = $this->getDocBlockIndex($tokens, $index);

return $tokens[$docBlockIndex]->isGivenKind(T_DOC_COMMENT);
}

private function getDocBlockIndex(Tokens $tokens, int $index): int
{
$propertyPartKinds = [
T_PUBLIC,
T_PROTECTED,
T_PRIVATE,
T_FINAL,
T_ABSTRACT,
T_COMMENT,
T_VAR,
T_STATIC,
T_STRING,
T_NS_SEPARATOR,
CT::T_ARRAY_TYPEHINT,
CT::T_NULLABLE_TYPE,
];

if (\defined('T_ATTRIBUTE')) { 
$propertyPartKinds[] = T_ATTRIBUTE;
}

if (\defined('T_READONLY')) { 
$propertyPartKinds[] = T_READONLY;
}

do {
$index = $tokens->getPrevNonWhitespace($index);

if ($tokens[$index]->isGivenKind(CT::T_ATTRIBUTE_CLOSE)) {
$index = $tokens->getPrevTokenOfKind($index, [[T_ATTRIBUTE]]);
}
} while ($tokens[$index]->isGivenKind($propertyPartKinds));

return $index;
}
}
