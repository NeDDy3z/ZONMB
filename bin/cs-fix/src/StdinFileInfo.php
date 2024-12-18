<?php

declare(strict_types=1);











namespace PhpCsFixer;






final class StdinFileInfo extends \SplFileInfo
{
public function __construct()
{
parent::__construct(__FILE__);
}

public function __toString(): string
{
return $this->getRealPath();
}

public function getRealPath(): string
{


return 'php://stdin';
}

public function getATime(): int
{
return 0;
}

public function getBasename($suffix = null): string
{
return $this->getFilename();
}

public function getCTime(): int
{
return 0;
}

public function getExtension(): string
{
return '.php';
}

public function getFileInfo($class = null): \SplFileInfo
{
throw new \BadMethodCallException(\sprintf('Method "%s" is not implemented.', __METHOD__));
}

public function getFilename(): string
{







return 'stdin.php';
}

public function getGroup(): int
{
return 0;
}

public function getInode(): int
{
return 0;
}

public function getLinkTarget(): string
{
return '';
}

public function getMTime(): int
{
return 0;
}

public function getOwner(): int
{
return 0;
}

public function getPath(): string
{
return '';
}

public function getPathInfo($class = null): \SplFileInfo
{
throw new \BadMethodCallException(\sprintf('Method "%s" is not implemented.', __METHOD__));
}

public function getPathname(): string
{
return $this->getFilename();
}

public function getPerms(): int
{
return 0;
}

public function getSize(): int
{
return 0;
}

public function getType(): string
{
return 'file';
}

public function isDir(): bool
{
return false;
}

public function isExecutable(): bool
{
return false;
}

public function isFile(): bool
{
return true;
}

public function isLink(): bool
{
return false;
}

public function isReadable(): bool
{
return true;
}

public function isWritable(): bool
{
return false;
}

public function openFile($openMode = 'r', $useIncludePath = false, $context = null): \SplFileObject
{
throw new \BadMethodCallException(\sprintf('Method "%s" is not implemented.', __METHOD__));
}

public function setFileClass($className = null): void {}

public function setInfoClass($className = null): void {}
}
