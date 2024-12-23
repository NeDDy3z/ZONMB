<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;




final class NoTrailingWhitespaceInCommentFixer extends AbstractFixer
{
public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'There MUST be no trailing spaces inside comment or PHPDoc.',
[new CodeSample('<?php
// This is '.'
// a comment. '.'
')]
);
}






public function getPriority(): int
{
return 0;
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
}

protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
{
foreach ($tokens as $index => $token) {
if ($token->isGivenKind(T_DOC_COMMENT)) {
$tokens[$index] = new Token([T_DOC_COMMENT, Preg::replace('/(*ANY)[\h]+$/m', '', $token->getContent())]);

continue;
}

if ($token->isGivenKind(T_COMMENT)) {
if (str_starts_with($token->getContent(), '/*')) {
$tokens[$index] = new Token([T_COMMENT, Preg::replace('/(*ANY)[\h]+$/m', '', $token->getContent())]);
} elseif (isset($tokens[$index + 1]) && $tokens[$index + 1]->isWhitespace()) {
$trimmedContent = ltrim($tokens[$index + 1]->getContent(), " \t");
$tokens->ensureWhitespaceAtIndex($index + 1, 0, $trimmedContent);
}
}
}
}
}
