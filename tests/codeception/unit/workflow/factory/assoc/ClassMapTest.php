<?php

namespace tests\unit\workflow\factory\assoc;

use Yii;
use yii\codeception\TestCase;
use tests\codeception\unit\models\Item04;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use fproject\workflow\factory\assoc\WorkflowArrayFactory;
use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;
use fproject\workflow\core\Workflow;


class ClassMapTest extends TestCase
{
	use \Codeception\Specify;

	public function testClassMapStatus()
	{
		$this->specify('Replace default status class with custom one',function (){

			$src = new WorkflowArrayFactory([
				'namespace' =>'tests\codeception\unit\models',
				'classMap' =>  [
					WorkflowArrayFactory::TYPE_STATUS     => 'tests\codeception\unit\models\MyStatus',
				]
			]);

			verify($src->getClassMapByType(WorkflowArrayFactory::TYPE_WORKFLOW))->equals(	'fproject\workflow\core\Workflow'  );
			verify($src->getClassMapByType(WorkflowArrayFactory::TYPE_STATUS))->equals(	'tests\codeception\unit\models\MyStatus'  );
			verify($src->getClassMapByType(WorkflowArrayFactory::TYPE_TRANSITION))->equals('fproject\workflow\core\Transition');

			$status = $src->getStatus('Item04Workflow/A');

			expect(get_class($status))->equals('tests\codeception\unit\models\MyStatus');
		});
	}
}