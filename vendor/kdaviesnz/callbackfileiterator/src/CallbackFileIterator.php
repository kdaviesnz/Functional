<?php
declare(strict_types = 1);

namespace kdaviesnz\callbackfileiterator;

/**
 * Class CallbackFileIterator
 * @package kdaviesnz\callbackfileiterator
 */
class CallbackFileIterator
{

	/**
	 * CallbackFileIterator constructor.
	 *
	 * @param string $rootDirectory
	 * @param callable $callback
	 * @param bool $recursive
	 */
	public function __construct(string $rootDirectory, Callable $callback, bool $recursive)
    {
    	 $this->parseFiles($rootDirectory, $callback, $recursive);
    }

	/**
	 * @param string $currentDirectory
	 * @param callable $callback
	 * @param bool $recursive
	 */
	private function parseFiles(string $currentDirectory, Callable $callback, bool $recursive)
    {
	    foreach (new \DirectoryIterator($currentDirectory) as $fileobject) {

		    if($fileobject->isDot()) continue;

		    if ($fileobject->isDir() && $recursive) {
			    $this->parseFiles($fileobject->getPathname(), $callback, $recursive);
		    }

		    if ($fileobject->isFile()) {
		    	$callback($fileobject->getPathname());
		    }

	    }
    }
}
