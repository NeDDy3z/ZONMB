<?php

declare(strict_types=1);











namespace PhpCsFixer\Fixer\PhpUnit;

use PhpCsFixer\Fixer\AbstractPhpUnitFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;




final class PhpUnitAssertNewNamesFixer extends AbstractPhpUnitFixer
{
public function isRisky(): bool
{
return true;
}

public function getDefinition(): FixerDefinitionInterface
{
return new FixerDefinition(
'Rename deprecated PHPUnit assertions like `assertFileNotExists` to new methods like `assertFileDoesNotExist`.',
[
new CodeSample(
'<?php
final class MyTest extends \PHPUnit_Framework_TestCase
{
    public function testSomeTest()
    {
        $this->assertFileNotExists("test.php");
        $this->assertNotIsWritable("path.php");
    }
}
'
),
],
null,
'Fixer could be risky if one is overriding PHPUnit\'s native methods.'
);
}






public function getPriority(): int
{
return -10;
}

protected function applyPhpUnitClassFix(Tokens $tokens, int $startIndex, int $endIndex): void
{
foreach ($this->getPreviousAssertCall($tokens, $startIndex, $endIndex) as $assertCall) {
$this->fixAssertNewNames($tokens, $assertCall);
}
}









private function fixAssertNewNames(Tokens $tokens, array $assertCall): void
{
$replacements = [
'assertnotisreadable' => 'assertIsNotReadable',
'assertnotiswritable' => 'assertIsNotWritable',
'assertdirectorynotexists' => 'assertDirectoryDoesNotExist',
'assertfilenotexists' => 'assertFileDoesNotExist',
'assertdirectorynotisreadable' => 'assertDirectoryIsNotReadable',
'assertdirectorynotiswritable' => 'assertDirectoryIsNotWriteable',
'assertfilenotisreadable' => 'assertFileIsNotReadable',
'assertfilenotiswritable' => 'assertFileIsNotWriteable',
'assertregexp' => 'assertMatchesRegularExpression',
'assertnotregexp' => 'assertDoesNotMatchRegularExpression',
];
$replacement = $replacements[$assertCall['loweredName']] ?? null;

if (null === $replacement) {
return;
}

$tokens[$assertCall['index']] = new Token([
T_STRING,
$replacement,
]);
}
}
