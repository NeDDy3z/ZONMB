<?php










namespace Symfony\Component\Console\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;








class ConsoleLogger extends AbstractLogger
{
public const INFO = 'info';
public const ERROR = 'error';

private $output;
private $verbosityLevelMap = [
LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
];
private $formatLevelMap = [
LogLevel::EMERGENCY => self::ERROR,
LogLevel::ALERT => self::ERROR,
LogLevel::CRITICAL => self::ERROR,
LogLevel::ERROR => self::ERROR,
LogLevel::WARNING => self::INFO,
LogLevel::NOTICE => self::INFO,
LogLevel::INFO => self::INFO,
LogLevel::DEBUG => self::INFO,
];
private $errored = false;

public function __construct(OutputInterface $output, array $verbosityLevelMap = [], array $formatLevelMap = [])
{
$this->output = $output;
$this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
$this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;
}






public function log($level, $message, array $context = [])
{
if (!isset($this->verbosityLevelMap[$level])) {
throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
}

$output = $this->output;


if (self::ERROR === $this->formatLevelMap[$level]) {
if ($this->output instanceof ConsoleOutputInterface) {
$output = $output->getErrorOutput();
}
$this->errored = true;
}



if ($output->getVerbosity() >= $this->verbosityLevelMap[$level]) {
$output->writeln(sprintf('<%1$s>[%2$s] %3$s</%1$s>', $this->formatLevelMap[$level], $level, $this->interpolate($message, $context)), $this->verbosityLevelMap[$level]);
}
}






public function hasErrored()
{
return $this->errored;
}






private function interpolate(string $message, array $context): string
{
if (!str_contains($message, '{')) {
return $message;
}

$replacements = [];
foreach ($context as $key => $val) {
if (null === $val || \is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
$replacements["{{$key}}"] = $val;
} elseif ($val instanceof \DateTimeInterface) {
$replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
} elseif (\is_object($val)) {
$replacements["{{$key}}"] = '[object '.\get_class($val).']';
} else {
$replacements["{{$key}}"] = '['.\gettype($val).']';
}
}

return strtr($message, $replacements);
}
}
