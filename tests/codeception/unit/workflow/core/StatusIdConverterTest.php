<?php
namespace tests\unit\workflow\core;

use Codeception\Specify;
use Yii;
use yii\codeception\TestCase;
use fproject\workflow\core\StatusIdConverter;

class StatusIdConverterTest extends TestCase
{
	use Specify;

	public function testCreateFails()
	{
		$this->specify('a map parameter must be provided', function(){
			Yii::createObject(['class'=> 'fproject\workflow\core\StatusIdConverter']);
		},['throws' => 'yii\base\InvalidConfigException']);

		$this->specify(' the map parameter must be an array', function() {
			Yii::createObject(['class'=> 'fproject\workflow\core\StatusIdConverter', 'map' => 'string']);
		},['throws' => 'yii\base\InvalidConfigException']);
	}

	public function testCreateSuccess()
	{
		$this->specify('a status converter is created successfully', function(){
			Yii::createObject([
				'class'=> 'fproject\workflow\core\StatusIdConverter',
				'map' => [
					'Post/ready' => '1',
					'Post/draft' => '2',
					'Post/deleted' => '3',
					StatusIdConverter::VALUE_NULL => '0'
				]
			]);
		});
	}

	public function testConversionSuccess()
	{
		$c = Yii::createObject([
			'class'=> 'fproject\workflow\core\StatusIdConverter',
			'map' => [
				'Post/ready' => '1',
				'Post/draft' => '2',
				'Post/deleted' => '3',
				StatusIdConverter::VALUE_NULL => '0',
				'Post/new' => StatusIdConverter::VALUE_NULL
			]
		]);

		$this->assertEquals('1', $c->toModelAttribute('Post/ready'));
		$this->assertEquals('2', $c->toModelAttribute('Post/draft'));
		$this->assertEquals('3', $c->toModelAttribute('Post/deleted'));
		$this->assertEquals(null, $c->toModelAttribute('Post/new'));
		$this->assertEquals('0', $c->toModelAttribute(null));

		$this->assertEquals('Post/ready', $c->toWorkflow(1));
		$this->assertEquals('Post/draft', $c->toWorkflow(2));
		$this->assertEquals('Post/deleted', $c->toWorkflow(3));
		$this->assertEquals(null, $c->toWorkflow(0));
		$this->assertEquals('Post/new', $c->toWorkflow(null));
	}

	public function testConversionFails()
	{
		$c = Yii::createObject([
			'class'=> 'fproject\workflow\core\StatusIdConverter',
			'map' => [
				'Post/ready' => '1',
			]
		]);

		$this->specify(' an exception is thrown if value is not found', function() use ($c) {
			$c->toWorkflow('not found');
		},['throws' => 'yii\base\Exception']);

		$this->specify(' an exception is thrown if value is not found', function() use ($c) {
			$c->toModelAttribute('not found');
		},['throws' => 'yii\base\Exception']);
	}
}
