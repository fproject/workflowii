<?php

namespace tests\unit\workflow\core;

use Codeception\Specify;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\codeception\TestCase;

class ArrayWorkflowItemFactoryTest extends TestCase
{
	use Specify;


	public function testConstructFails1()
	{
		$this->specify('Workflow factory construct fails if classMap is not an array',function (){

			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'Invalid property type : \'classMap\' must be a non-empty array'
			);

			new ArrayWorkflowItemFactory([
				'workflowSourceNamespace' =>'a\b\c',
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

			new ArrayWorkflowItemFactory([
				'workflowSourceNamespace' =>'a\b\c',
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

			 new ArrayWorkflowItemFactory([
				'workflowSourceNamespace' =>'a\b\c',
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

			$src = new ArrayWorkflowItemFactory([
				'workflowSourceNamespace' =>'a\b\c',
				'classMap' =>  [
					ArrayWorkflowItemFactory::TYPE_WORKFLOW   => 'my\namespace\Workflow',
					ArrayWorkflowItemFactory::TYPE_STATUS     => 'my\namespace\Status',
					ArrayWorkflowItemFactory::TYPE_TRANSITION => 'my\namespace\Transition'
				]
			]);
			expect($src->getClassMapByType(ArrayWorkflowItemFactory::TYPE_WORKFLOW))->equals(	'my\namespace\Workflow'		);
			expect($src->getClassMapByType(ArrayWorkflowItemFactory::TYPE_STATUS))->equals(	'my\namespace\Status'		);
			expect($src->getClassMapByType(ArrayWorkflowItemFactory::TYPE_TRANSITION))->equals('my\namespace\Transition'	);
		});
	}
}
