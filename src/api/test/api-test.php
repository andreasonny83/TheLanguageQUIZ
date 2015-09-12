<?php
class testApi extends PHPUnit_Framework_TestCase {
	var $abc;

	public function setUp() {
		$this->abc = "abc";
	}

	public function testApi() {
		$this->abc = sprintf('contains %s', 'abc');
		$expected = 'contains abc';
		$this->assertTrue($this->abc == $expected);
	}
}

?>
