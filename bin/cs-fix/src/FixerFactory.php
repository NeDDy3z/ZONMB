<?php

declare(strict_types=1);











namespace PhpCsFixer;

use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\RuleSet\RuleSetInterface;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use Symfony\Component\Finder\SplFileInfo;














final class FixerFactory
{
private FixerNameValidator $nameValidator;




private array $fixers = [];




private array $fixersByName = [];

public function __construct()
{
$this->nameValidator = new FixerNameValidator();
}

public function setWhitespacesConfig(WhitespacesFixerConfig $config): self
{
foreach ($this->fixers as $fixer) {
if ($fixer instanceof WhitespacesAwareFixerInterface) {
$fixer->setWhitespacesConfig($config);
}
}

return $this;
}




public function getFixers(): array
{
$this->fixers = Utils::sortFixers($this->fixers);

return $this->fixers;
}




public function registerBuiltInFixers(): self
{
static $builtInFixers = null;

if (null === $builtInFixers) {

$builtInFixers = [];

$finder = SymfonyFinder::create()->files()
->in(__DIR__.'/Fixer')
->exclude(['Internal'])
->name('*Fixer.php')
->depth(1)
;


foreach ($finder as $file) {
$relativeNamespace = $file->getRelativePath();
$fixerClass = 'PhpCsFixer\Fixer\\'.('' !== $relativeNamespace ? $relativeNamespace.'\\' : '').$file->getBasename('.php');
$builtInFixers[] = $fixerClass;
}
}

foreach ($builtInFixers as $class) {

$fixer = new $class();
$this->registerFixer($fixer, false);
}

return $this;
}






public function registerCustomFixers(iterable $fixers): self
{
foreach ($fixers as $fixer) {
$this->registerFixer($fixer, true);
}

return $this;
}




public function registerFixer(FixerInterface $fixer, bool $isCustom): self
{
$name = $fixer->getName();

if (isset($this->fixersByName[$name])) {
throw new \UnexpectedValueException(\sprintf('Fixer named "%s" is already registered.', $name));
}

if (!$this->nameValidator->isValid($name, $isCustom)) {
throw new \UnexpectedValueException(\sprintf('Fixer named "%s" has invalid name.', $name));
}

$this->fixers[] = $fixer;
$this->fixersByName[$name] = $fixer;

return $this;
}






public function useRuleSet(RuleSetInterface $ruleSet): self
{
$fixers = [];
$fixersByName = [];
$fixerConflicts = [];

$fixerNames = array_keys($ruleSet->getRules());
foreach ($fixerNames as $name) {
if (!\array_key_exists($name, $this->fixersByName)) {
throw new \UnexpectedValueException(\sprintf('Rule "%s" does not exist.', $name));
}

$fixer = $this->fixersByName[$name];
$config = $ruleSet->getRuleConfiguration($name);

if (null !== $config) {
if ($fixer instanceof ConfigurableFixerInterface) {
if (\count($config) < 1) {
throw new InvalidFixerConfigurationException($fixer->getName(), 'Configuration must be an array and may not be empty.');
}

$fixer->configure($config);
} else {
throw new InvalidFixerConfigurationException($fixer->getName(), 'Is not configurable.');
}
}

$fixers[] = $fixer;
$fixersByName[$name] = $fixer;
$conflicts = array_intersect($this->getFixersConflicts($fixer), $fixerNames);

if (\count($conflicts) > 0) {
$fixerConflicts[$name] = $conflicts;
}
}

if (\count($fixerConflicts) > 0) {
throw new \UnexpectedValueException($this->generateConflictMessage($fixerConflicts));
}

$this->fixers = $fixers;
$this->fixersByName = $fixersByName;

return $this;
}




public function hasRule(string $name): bool
{
return isset($this->fixersByName[$name]);
}




private function getFixersConflicts(FixerInterface $fixer): array
{
static $conflictMap = [
'blank_lines_before_namespace' => [
'no_blank_lines_before_namespace',
'single_blank_line_before_namespace',
],
'no_blank_lines_before_namespace' => ['single_blank_line_before_namespace'],
'single_import_per_statement' => ['group_import'],
];

$fixerName = $fixer->getName();

return \array_key_exists($fixerName, $conflictMap) ? $conflictMap[$fixerName] : [];
}




private function generateConflictMessage(array $fixerConflicts): string
{
$message = 'Rule contains conflicting fixers:';
$report = [];

foreach ($fixerConflicts as $fixer => $fixers) {

$report[$fixer] = array_filter(
$fixers,
static fn (string $candidate): bool => !\array_key_exists($candidate, $report) || !\in_array($fixer, $report[$candidate], true)
);

if (\count($report[$fixer]) > 0) {
$message .= \sprintf("\n- \"%s\" with %s", $fixer, Utils::naturalLanguageJoin($report[$fixer]));
}
}

return $message;
}
}
