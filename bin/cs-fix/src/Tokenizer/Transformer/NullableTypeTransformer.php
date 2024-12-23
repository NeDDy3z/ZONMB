<?php

declare(strict_types=1);











namespace PhpCsFixer\Tokenizer\Transformer;

use PhpCsFixer\Tokenizer\AbstractTransformer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;








final class NullableTypeTransformer extends AbstractTransformer
{
public function getPriority(): int
{

return -20;
}

public function getRequiredPhpVersionId(): int
{
return 7_01_00;
}

public function process(Tokens $tokens, Token $token, int $index): void
{
if (!$token->equals('?')) {
return;
}

static $types;

if (null === $types) {
$types = [
'(',
',',
[CT::T_TYPE_COLON],
[CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PUBLIC],
[CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PROTECTED],
[CT::T_CONSTRUCTOR_PROPERTY_PROMOTION_PRIVATE],
[CT::T_ATTRIBUTE_CLOSE],
[T_PRIVATE],
[T_PROTECTED],
[T_PUBLIC],
[T_VAR],
[T_STATIC],
[T_CONST],
];

if (\defined('T_READONLY')) { 
$types[] = [T_READONLY];
}
}

$prevIndex = $tokens->getPrevMeaningfulToken($index);

if ($tokens[$prevIndex]->equalsAny($types)) {
$tokens[$index] = new Token([CT::T_NULLABLE_TYPE, '?']);
}
}

public function getCustomTokens(): array
{
return [CT::T_NULLABLE_TYPE];
}
}
