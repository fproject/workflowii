<?php

namespace tests\unit\workflow\serialize;

use Codeception\Specify;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\codeception\TestCase;

class ClassMapTest extends TestCase
{
	use Specify;

	public function testClassMapStatus()
	{
		$this->specify('Replace default status class with custom one',function (){

			$factory = new ArrayWorkflowItemFactory([
				'workflowSourceNamespace' =>'tests\codeception\unit\models',
				'classMap' =>  [
					ArrayWorkflowItemFactory::CLASS_MAP_STATUS     => 'tests\codeception\unit\models\MyStatus',
				]
			]);

			verify($factory->getClassMapByType(ArrayWorkflowItemFactory::CLASS_MAP_WORKFLOW))->equals(	'fproject\workflow\core\Workflow'  );
			verify($factory->getClassMapByType(ArrayWorkflowItemFactory::CLASS_MAP_STATUS))->equals(	'tests\codeception\unit\models\MyStatus'  );
			verify($factory->getClassMapByType(ArrayWorkflowItemFactory::CLASS_MAP_TRANSITION))->equals('fproject\workflow\core\Transition');

			$status = $factory->getStatus('Item04Workflow/A', null, null);

			expect(get_class($status))->equals('tests\codeception\unit\models\MyStatus');
		});
	}
}
