<?php



class ComposerAutoloaderInit43f33e2f7f39ed6f3341269681ebd52e
{
private static $loader;

public static function loadClassLoader($class)
{
if ('Composer\Autoload\ClassLoader' === $class) {
require __DIR__ . '/ClassLoader.php';
}
}




public static function getLoader()
{
if (null !== self::$loader) {
return self::$loader;
}

require __DIR__ . '/platform_check.php';

spl_autoload_register(array('ComposerAutoloaderInit43f33e2f7f39ed6f3341269681ebd52e', 'loadClassLoader'), true, true);
self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
spl_autoload_unregister(array('ComposerAutoloaderInit43f33e2f7f39ed6f3341269681ebd52e', 'loadClassLoader'));

require __DIR__ . '/autoload_static.php';
call_user_func(\Composer\Autoload\ComposerStaticInit43f33e2f7f39ed6f3341269681ebd52e::getInitializer($loader));

$loader->register(true);

$filesToLoad = \Composer\Autoload\ComposerStaticInit43f33e2f7f39ed6f3341269681ebd52e::$files;
$requireFile = \Closure::bind(static function ($fileIdentifier, $file) {
if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
$GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;

require $file;
}
}, null, null);
foreach ($filesToLoad as $fileIdentifier => $file) {
$requireFile($fileIdentifier, $file);
}

return $loader;
}
}
