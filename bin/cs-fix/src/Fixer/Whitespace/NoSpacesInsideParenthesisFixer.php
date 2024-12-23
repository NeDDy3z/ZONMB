<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\Whitespace;

use PhpCsFixer\AbstractProxyFixer;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;









final class NoSpacesInsideParenthesisFixer extends AbstractProxyFixer implements DeprecatedFixerInterface
{
public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'There MUST NOT be a space after the opening parenthesis. There MUST NOT be a space before the closing parenthesis.',
[
new CodeSample("<?php\nif ( \$a ) {\n    foo( );\n}\n"),
new CodeSample(
"<?php
function foo( \$bar, \$baz )
{
}\n"
),
]
);
}







public function getPriority(): int
{
return 3;
}

public function getSuccessorsNames(): array
{
return array_keys($this->proxyFixers);
}

public function isCandidate(Tokens $tokens): bool
{
return $tokens->isTokenKindFound('(');
}

protected function createProxyFixers(): array
{
return [new SpacesInsideParenthesesFixer()];
}
}
