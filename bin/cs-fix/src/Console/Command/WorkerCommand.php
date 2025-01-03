<?php

declare(strict_types=1);











namespace PhpCsFixer\Console\Command;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use PhpCsFixer\Cache\NullCacheManager;
use PhpCsFixer\Config;
use PhpCsFixer\Console\ConfigurationResolver;
use PhpCsFixer\Error\ErrorsManager;
use PhpCsFixer\FixerFileProcessedEvent;
use PhpCsFixer\Runner\Parallel\ParallelAction;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use PhpCsFixer\Runner\Parallel\ParallelisationException;
use PhpCsFixer\Runner\Runner;
use PhpCsFixer\ToolInfoInterface;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use React\Socket\TcpConnector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;






#[AsCommand(name: 'worker', description: 'Internal command for running fixers in parallel', hidden: true)]
final class WorkerCommand extends Command
{

public const ERROR_PREFIX = 'WORKER_ERROR::';


protected static $defaultName = 'worker';


protected static $defaultDescription = 'Internal command for running fixers in parallel';

private ToolInfoInterface $toolInfo;
private ConfigurationResolver $configurationResolver;
private ErrorsManager $errorsManager;
private EventDispatcherInterface $eventDispatcher;


private array $events;

public function __construct(ToolInfoInterface $toolInfo)
{
parent::__construct();

$this->setHidden(true);
$this->toolInfo = $toolInfo;
$this->errorsManager = new ErrorsManager();
$this->eventDispatcher = new EventDispatcher();
}

protected function configure(): void
{
$this->setDefinition(
[
new InputOption('port', null, InputOption::VALUE_REQUIRED, 'Specifies parallelisation server\'s port.'),
new InputOption('identifier', null, InputOption::VALUE_REQUIRED, 'Specifies parallelisation process\' identifier.'),
new InputOption('allow-risky', '', InputOption::VALUE_REQUIRED, 'Are risky fixers allowed (can be `yes` or `no`).'),
new InputOption('config', '', InputOption::VALUE_REQUIRED, 'The path to a config file.'),
new InputOption('dry-run', '', InputOption::VALUE_NONE, 'Only shows which files would have been modified.'),
new InputOption('rules', '', InputOption::VALUE_REQUIRED, 'List of rules that should be run against configured paths.'),
new InputOption('using-cache', '', InputOption::VALUE_REQUIRED, 'Should cache be used (can be `yes` or `no`).'),
new InputOption('cache-file', '', InputOption::VALUE_REQUIRED, 'The path to the cache file.'),
new InputOption('diff', '', InputOption::VALUE_NONE, 'Prints diff for each file.'),
new InputOption('stop-on-violation', '', InputOption::VALUE_NONE, 'Stop execution on first violation.'),
]
);
}

protected function execute(InputInterface $input, OutputInterface $output): int
{
$errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
$identifier = $input->getOption('identifier');
$port = $input->getOption('port');

if (null === $identifier || !is_numeric($port)) {
throw new ParallelisationException('Missing parallelisation options');
}

try {
$runner = $this->createRunner($input);
} catch (\Throwable $e) {
throw new ParallelisationException('Unable to create runner: '.$e->getMessage(), 0, $e);
}

$loop = new StreamSelectLoop();
$tcpConnector = new TcpConnector($loop);
$tcpConnector
->connect(\sprintf('127.0.0.1:%d', $port))
->then(

function (ConnectionInterface $connection) use ($loop, $runner, $identifier): void {
$jsonInvalidUtf8Ignore = \defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
$out = new Encoder($connection, $jsonInvalidUtf8Ignore);
$in = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore);


$out->write(['action' => ParallelAction::WORKER_HELLO, 'identifier' => $identifier]);

$handleError = static function (\Throwable $error) use ($out): void {
$out->write([
'action' => ParallelAction::WORKER_ERROR_REPORT,
'class' => \get_class($error),
'message' => $error->getMessage(),
'file' => $error->getFile(),
'line' => $error->getLine(),
'code' => $error->getCode(),
'trace' => $error->getTraceAsString(),
]);
};
$out->on('error', $handleError);
$in->on('error', $handleError);


$in->on('data', function (array $json) use ($loop, $runner, $out): void {
$action = $json['action'] ?? null;


if (ParallelAction::RUNNER_THANK_YOU === $action) {
$loop->stop();

return;
}

if (ParallelAction::RUNNER_REQUEST_ANALYSIS !== $action) {

throw new \LogicException(\sprintf('Unexpected action ParallelAction::%s.', $action));
}


$files = $json['files'];

foreach ($files as $absolutePath) {

$this->events = [];
$runner->setFileIterator(new \ArrayIterator([new \SplFileInfo($absolutePath)]));
$analysisResult = $runner->fix();

if (1 !== \count($this->events)) {
throw new ParallelisationException('Runner did not report a fixing event or reported too many.');
}

if (1 < \count($analysisResult)) {
throw new ParallelisationException('Runner returned more analysis results than expected.');
}

$out->write([
'action' => ParallelAction::WORKER_RESULT,
'file' => $absolutePath,
'fileHash' => $this->events[0]->getFileHash(),
'status' => $this->events[0]->getStatus(),
'fixInfo' => array_pop($analysisResult),
'errors' => $this->errorsManager->forPath($absolutePath),
]);
}


$out->write(['action' => ParallelAction::WORKER_GET_FILE_CHUNK]);
});
},
static function (\Throwable $error) use ($errorOutput): void {

$errorOutput->writeln($error->getMessage());
}
)
;

$loop->run();

return Command::SUCCESS;
}

private function createRunner(InputInterface $input): Runner
{
$passedConfig = $input->getOption('config');
$passedRules = $input->getOption('rules');

if (null !== $passedConfig && null !== $passedRules) {
throw new \RuntimeException('Passing both `--config` and `--rules` options is not allowed');
}


$this->eventDispatcher->addListener(FixerFileProcessedEvent::NAME, function (FixerFileProcessedEvent $event): void {
$this->events[] = $event;
});

$this->configurationResolver = new ConfigurationResolver(
new Config(),
[
'allow-risky' => $input->getOption('allow-risky'),
'config' => $passedConfig,
'dry-run' => $input->getOption('dry-run'),
'rules' => $passedRules,
'path' => [],
'path-mode' => ConfigurationResolver::PATH_MODE_OVERRIDE, 
'using-cache' => $input->getOption('using-cache'),
'cache-file' => $input->getOption('cache-file'),
'diff' => $input->getOption('diff'),
'stop-on-violation' => $input->getOption('stop-on-violation'),
],
getcwd(), 
$this->toolInfo
);

return new Runner(
null, 
$this->configurationResolver->getFixers(),
$this->configurationResolver->getDiffer(),
$this->eventDispatcher,
$this->errorsManager,
$this->configurationResolver->getLinter(),
$this->configurationResolver->isDryRun(),
new NullCacheManager(), 
$this->configurationResolver->getDirectory(),
$this->configurationResolver->shouldStopOnViolation(),
ParallelConfigFactory::sequential(), 
null,
$this->configurationResolver->getConfigFile()
);
}
}
