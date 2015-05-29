<?php

namespace tests\unit\workflow\activebehavior;

use Codeception\Specify;
use fproject\workflow\core\ActiveWorkflowBehavior;
use tests\codeception\unit\models\Item04;
use Yii;
use yii\codeception\DbTestCase;
use tests\codeception\unit\fixtures\ItemFixture04;

/**
 * Class AfterFindTest
 *
 * @method Item04|ActiveWorkflowBehavior items()
 *
 */
class AfterFindTest extends DbTestCase
{
	use Specify;

	public function fixtures()
	{
		return [
			'items' => ItemFixture04::className(),
		];
	}

	protected function setup()
	{
		parent::setUp();
		Yii::$app->set('workflowFactory',[
			'class'=> 'fproject\workflow\core\ArrayWorkflowItemFactory',
			'workflowSourceNamespace' => 'tests\codeception\unit\models'
		]);
	}

    public function testWorkflowStatusOnAfterFind()
    {
		$this->specify('item1 can be read from db', function() {
			$item = $this->items('item1');
			verify('current status is set', $item->getWorkflowStatus()->getId())->equals('Item04Workflow/B');
		});

		$this->specify('item2 cannot be read from db (invalid status)', function() {
			$this->items('item2');
		},['throws' => 'fproject\workflow\core\WorkflowException']);

		$this->specify('item3 can be read from db : short name', function() {
			$this->items('item3');
		});
    }
}
