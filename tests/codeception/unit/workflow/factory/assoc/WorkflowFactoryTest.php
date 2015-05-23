<?php

namespace tests\unit\workflow\factory\assoc;

use Yii;
use yii\codeception\TestCase;
use fproject\workflow\factories\assoc\WorkflowArrayFactory;

class WorkflowFactoryTest extends TestCase
{
	use \Codeception\Specify;


	public function testConstructFails1()
	{
		$this->specify('Workflow factory construct fails if classMap is not an array',function (){

			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'Invalid property type : \'classMap\' must be a non-empty array'
			);

			new WorkflowArrayFactory([
				'namespace' =>'a\b\c',
				'classMap' => null
			]);
		});
	}
	public function testConstructFails2()
	{
		$this->specify('Workflow factory construct fails if classMap is an empty array',function (){

			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'Invalid property type : \'classMap\' must be a non-empty array'
			);

			new WorkflowArrayFactory([
				'namespace' =>'a\b\c',
				'classMap' => null
			]);
		});
	}
	public function testConstructFails3()
	{
		$this->specify('Workflow factory construct fails if a class entry is missing',function (){

			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'Invalid class map value : missing class for type workflow'
			);

			 new WorkflowArrayFactory([
				'namespace' =>'a\b\c',
				'classMap' =>  [
					'workflow'   => null,
					'status'     => 'fproject\workflow\core\Status',
					'transition' => 'fproject\workflow\core\Transition'
				]
			]);


		});


	}
	public function testConstructSuccess()
	{
		$this->specify('Workflow factory construct fails if classMap is not an array',function (){

			$src = new WorkflowArrayFactory([
				'namespace' =>'a\b\c',
				'classMap' =>  [
					WorkflowArrayFactory::TYPE_WORKFLOW   => 'my\namespace\Workflow',
					WorkflowArrayFactory::TYPE_STATUS     => 'my\namespace\Status',
					WorkflowArrayFactory::TYPE_TRANSITION => 'my\namespace\Transition'
				]
			]);
			expect($src->getClassMapByType(WorkflowArrayFactory::TYPE_WORKFLOW))->equals(	'my\namespace\Workflow'		);
			expect($src->getClassMapByType(WorkflowArrayFactory::TYPE_STATUS))->equals(	'my\namespace\Status'		);
			expect($src->getClassMapByType(WorkflowArrayFactory::TYPE_TRANSITION))->equals('my\namespace\Transition'	);
		});
	}
}
