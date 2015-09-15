<?php
namespace lqAPI\Test;
use lqAPI;
use Slim\Environment;

class StupidTest extends \PHPUnit_Framework_TestCase {

	public function testTrueIsTrue() {
		// just a warm up!
		$foo = true;
		$this->assertTrue( $foo );
	}

	public function testAPI( $path ) {
		Environment::mock(array(
			'REQUEST_METHOD' => 'GET',
			'PATH_INFO' => $path,
		));

		$app = new lqAPI();
		$app->response()->finalize();
		return $app->response();
	}

	public function testIndex() {
		$response = $this->testAPI('/status');
		// $this->assertContains('', $response->getBody());
	}

}
