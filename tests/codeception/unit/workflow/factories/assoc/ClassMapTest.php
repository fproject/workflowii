<?php

namespace tests\unit\workflow\factories\assoc;

use Yii;
use yii\codeception\TestCase;
use fproject\workflow\factories\assoc\WorkflowArrayFactory;

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

			$status = $src->getStatus('Item04Workflow/A', null, null);

			expect(get_class($status))->equals('tests\codeception\unit\models\MyStatus');
		});
	}
}
