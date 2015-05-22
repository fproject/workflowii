<?php

namespace tests\unit\workflow\source\php;

use Yii;
use yii\codeception\TestCase;
use tests\codeception\unit\models\Item04;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use fproject\workflow\factory\php\WorkflowPhpSource;
use fproject\workflow\core\Status;
use fproject\workflow\core\Transition;
use fproject\workflow\core\Workflow;


class ClassMapTest extends TestCase
{
	use \Codeception\Specify;

	public function testClassMapStatus()
	{
		$this->specify('Replace default status class with custom one',function (){

			$src = new WorkflowPhpSource([
				'namespace' =>'tests\codeception\unit\models',
				'classMap' =>  [
					WorkflowPhpSource::TYPE_STATUS     => 'tests\codeception\unit\models\MyStatus',
				]
			]);

			verify($src->getClassMapByType(WorkflowPhpSource::TYPE_WORKFLOW))->equals(	'fproject\workflow\core\Workflow'  );
			verify($src->getClassMapByType(WorkflowPhpSource::TYPE_STATUS))->equals(	'tests\codeception\unit\models\MyStatus'  );
			verify($src->getClassMapByType(WorkflowPhpSource::TYPE_TRANSITION))->equals('fproject\workflow\core\Transition');

			$status = $src->getStatus('Item04Workflow/A');

			expect(get_class($status))->equals('tests\codeception\unit\models\MyStatus');
		});
	}
}
