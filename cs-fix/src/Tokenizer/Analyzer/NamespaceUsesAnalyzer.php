<?php

declare(strict_types=1);











namespace PhpCsFixer\Tokenizer\Analyzer;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;









final class NamespaceUsesAnalyzer
{



public function getDeclarationsFromTokens(Tokens $tokens, bool $allowMultiUses = false): array
{
$tokenAnalyzer = new TokensAnalyzer($tokens);
$useIndices = $tokenAnalyzer->getImportUseIndexes();

return $this->getDeclarations($tokens, $useIndices, $allowMultiUses);
}




public function getDeclarationsInNamespace(Tokens $tokens, NamespaceAnalysis $namespace, bool $allowMultiUses = false): array
{
$namespaceUses = [];

foreach ($this->getDeclarationsFromTokens($tokens, $allowMultiUses) as $namespaceUse) {
if ($namespaceUse->getStartIndex() >= $namespace->getScopeStartIndex() && $namespaceUse->getStartIndex() <= $namespace->getScopeEndIndex()) {
$namespaceUses[] = $namespaceUse;
}
}

return $namespaceUses;
}






private function getDeclarations(Tokens $tokens, array $useIndices, bool $allowMultiUses = false): array
{
$uses = [];

foreach ($useIndices as $index) {
$endIndex = $tokens->getNextTokenOfKind($index, [';', [T_CLOSE_TAG]]);

$declarations = $this->parseDeclarations($index, $endIndex, $tokens);
if (false === $allowMultiUses) {
$declarations = array_filter($declarations, static fn (NamespaceUseAnalysis $declaration) => !$declaration->isInMulti());
}

if ([] !== $declarations) {
$uses = array_merge($uses, $declarations);
}
}

return $uses;
}




private function parseDeclarations(int $startIndex, int $endIndex, Tokens $tokens): array
{
$type = $this->determineImportType($tokens, $startIndex);
$potentialMulti = $tokens->getNextTokenOfKind($startIndex, [',', [CT::T_GROUP_IMPORT_BRACE_OPEN]]);
$multi = null !== $potentialMulti && $potentialMulti < $endIndex;
$index = $tokens->getNextTokenOfKind($startIndex, [[T_STRING], [T_NS_SEPARATOR]]);
$imports = [];

while (null !== $index && $index <= $endIndex) {
$qualifiedName = $this->getNearestQualifiedName($tokens, $index);
$token = $tokens[$qualifiedName['afterIndex']];

if ($token->isGivenKind(CT::T_GROUP_IMPORT_BRACE_OPEN)) {
$groupStart = $groupIndex = $qualifiedName['afterIndex'];
$groupEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_GROUP_IMPORT_BRACE, $groupStart);

while ($groupIndex < $groupEnd) {
$chunkStart = $tokens->getNextMeaningfulToken($groupIndex);


if ($tokens[$chunkStart]->isGivenKind(CT::T_GROUP_IMPORT_BRACE_CLOSE)) {
break;
}

$groupQualifiedName = $this->getNearestQualifiedName($tokens, $chunkStart);
$imports[] = new NamespaceUseAnalysis(
$type,
$qualifiedName['fullName'].$groupQualifiedName['fullName'], 
$groupQualifiedName['shortName'],
$groupQualifiedName['aliased'],
true,
$startIndex,
$endIndex,
$chunkStart,
$tokens->getPrevMeaningfulToken($groupQualifiedName['afterIndex'])
);

$groupIndex = $groupQualifiedName['afterIndex'];
}

$index = $groupIndex;
} elseif ($token->equalsAny([',', ';', [T_CLOSE_TAG]])) {
$previousToken = $tokens->getPrevMeaningfulToken($qualifiedName['afterIndex']);

if (!$tokens[$previousToken]->isGivenKind(CT::T_GROUP_IMPORT_BRACE_CLOSE)) {
$imports[] = new NamespaceUseAnalysis(
$type,
$qualifiedName['fullName'],
$qualifiedName['shortName'],
$qualifiedName['aliased'],
$multi,
$startIndex,
$endIndex,
$multi ? $index : null,
$multi ? $previousToken : null
);
}

$index = $qualifiedName['afterIndex'];
}

$index = $tokens->getNextMeaningfulToken($index);
}

return $imports;
}




private function determineImportType(Tokens $tokens, int $startIndex): int
{
$potentialType = $tokens[$tokens->getNextMeaningfulToken($startIndex)];

if ($potentialType->isGivenKind(CT::T_FUNCTION_IMPORT)) {
return NamespaceUseAnalysis::TYPE_FUNCTION;
}

if ($potentialType->isGivenKind(CT::T_CONST_IMPORT)) {
return NamespaceUseAnalysis::TYPE_CONSTANT;
}

return NamespaceUseAnalysis::TYPE_CLASS;
}




private function getNearestQualifiedName(Tokens $tokens, int $index): array
{
$fullName = $shortName = '';
$aliased = false;

while (null !== $index) {
$token = $tokens[$index];

if ($token->isGivenKind(T_STRING)) {
$shortName = $token->getContent();
if (!$aliased) {
$fullName .= $shortName;
}
} elseif ($token->isGivenKind(T_NS_SEPARATOR)) {
$fullName .= $token->getContent();
} elseif ($token->isGivenKind(T_AS)) {
$aliased = true;
} elseif ($token->equalsAny([
',',
';',
[CT::T_GROUP_IMPORT_BRACE_OPEN],
[CT::T_GROUP_IMPORT_BRACE_CLOSE],
[T_CLOSE_TAG],
])) {
break;
}

$index = $tokens->getNextMeaningfulToken($index);
}


$fqn = $fullName;

return [
'fullName' => $fqn,
'shortName' => $shortName,
'aliased' => $aliased,
'afterIndex' => $index,
];
}
}
