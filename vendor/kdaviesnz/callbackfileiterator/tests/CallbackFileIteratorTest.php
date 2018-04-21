<?php

namespace kdaviesnz\callbackfileiterator;


class CallbackFileIteratorTest extends \PHPUnit_Framework_TestCase {

	public function testMethodName() {
		// $this->assertTrue( false, "true didn't end up being false!" );
		require_once("src/CallbackFileIterator.php");
		$callback = function() {
			return function(string $filename) {
				echo $filename . "\n";
			};
		};
		$callbackIterator = new CallbackFileIterator("src", $callback(), true);
	}

}
