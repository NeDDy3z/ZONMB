<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;




final class PhpdocAnnotationWithoutDotFixer extends AbstractFixer
{



private array $tags = ['throws', 'return', 'param', 'internal', 'deprecated', 'var', 'type'];

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'PHPDoc annotation descriptions should not be a sentence.',
[new CodeSample('<?php
/**
 * @param string $bar Some string.
 */
function foo ($bar) {}
')]
);
}







public function getPriority(): int
{
return 17;
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound(T_DOC_COMMENT);
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
foreach ($tokens as $index => $token) {
if (!$token->isGivenKind(T_DOC_COMMENT)) {
continue;
}

$doc = new DocBlock($token->getContent());
$annotations = $doc->getAnnotations();

if (0 === \count($annotations)) {
continue;
}

foreach ($annotations as $annotation) {
if (
!$annotation->getTag()->valid() || !\in_array($annotation->getTag()->getName(), $this->tags, true)
) {
continue;
}

$lineAfterAnnotation = $doc->getLine($annotation->getEnd() + 1);
if (null !== $lineAfterAnnotation) {
$lineAfterAnnotationTrimmed = ltrim($lineAfterAnnotation->getContent());
if ('' === $lineAfterAnnotationTrimmed || !str_starts_with($lineAfterAnnotationTrimmed, '*')) {

continue;
}
}

$content = $annotation->getContent();

if (
!Preg::match('/[.。]\h*$/u', $content)
|| Preg::match('/[.。](?!\h*$)/u', $content, $matches)
) {
continue;
}

$endLine = $doc->getLine($annotation->getEnd());
$endLine->setContent(Preg::replace('/(?<![.。])[.。]\h*(\H+)$/u', '\1', $endLine->getContent()));

$startLine = $doc->getLine($annotation->getStart());
$optionalTypeRegEx = $annotation->supportTypes()
? \sprintf('(?:%s\s+(?:\$\w+\s+)?)?', preg_quote(implode('|', $annotation->getTypes()), '/'))
: '';
$content = Preg::replaceCallback(
'/^(\s*\*\s*@\w+\s+'.$optionalTypeRegEx.')(\p{Lu}?(?=\p{Ll}|\p{Zs}))(.*)$/',
static fn (array $matches): string => $matches[1].mb_strtolower($matches[2]).$matches[3],
$startLine->getContent(),
1
);
$startLine->setContent($content);
}

$tokens[$index] = new Token([T_DOC_COMMENT, $doc->getContent()]);
}
}
}
