<?php

namespace lqAPI\Test\some;

use lqAPI\some\URL;

class URLTest extends \PHPUnit_Framework_TestCase {

	public function testSluggifyReturnsSluggifiedString() {
		$originalString = 'This string will be sluggified';
		$expectedResult = 'this-string-will-be-sluggified';

		$url = new URL();
	}
}
