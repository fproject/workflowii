<?php

namespace tests\unit\workflow\source\php;

use Yii;
use yii\codeception\TestCase;
use tests\codeception\unit\models\Item01;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use fproject\workflow\factory\php\WorkflowPhpSource;
use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;
use fproject\workflow\core\Workflow;


class WorkflowSourceTest extends TestCase
{
	use \Codeception\Specify;


	public function testConstructFails1()
	{
		$this->specify('Workflow source construct fails if classMap is not an array',function (){

			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'Invalid property type : \'classMap\' must be a non-empty array'
			);

			new WorkflowPhpSource([
				'namespace' =>'a\b\c',
				'classMap' => null
			]);
		});
	}
	public function testConstructFails2()
	{
		$this->specify('Workflow source construct fails if classMap is an empty array',function (){

			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'Invalid property type : \'classMap\' must be a non-empty array'
			);

			new WorkflowPhpSource([
				'namespace' =>'a\b\c',
				'classMap' => null
			]);
		});
	}
	public function testConstructFails3()
	{
		$this->specify('Workflow source construct fails if a class entry is missing',function (){

			$this->setExpectedException(
				'yii\base\InvalidConfigException',
				'Invalid class map value : missing class for type workflow'
			);

			 new WorkflowPhpSource([
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
		$this->specify('Workflow source construct fails if classMap is not an array',function (){

			$src = new WorkflowPhpSource([
				'namespace' =>'a\b\c',
				'classMap' =>  [
					WorkflowPhpSource::TYPE_WORKFLOW   => 'my\namespace\Workflow',
					WorkflowPhpSource::TYPE_STATUS     => 'my\namespace\Status',
					WorkflowPhpSource::TYPE_TRANSITION => 'my\namespace\Transition'
				]
			]);
			expect($src->getClassMapByType(WorkflowPhpSource::TYPE_WORKFLOW))->equals(	'my\namespace\Workflow'		);
			expect($src->getClassMapByType(WorkflowPhpSource::TYPE_STATUS))->equals(	'my\namespace\Status'		);
			expect($src->getClassMapByType(WorkflowPhpSource::TYPE_TRANSITION))->equals('my\namespace\Transition'	);
		});
	}
}
