<?php

namespace tests\unit\workflow\core;

use Codeception\Specify;
use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use tests\codeception\unit\fixtures\DynamicItemFixture;
use tests\codeception\unit\models\DynamicItem;
use tests\codeception\unit\models\Item04;
use Yii;
use yii\codeception\TestCase;

/**
 *
 * @method DynamicItem|ActiveWorkflowBehavior items()
 *
 */
class ArrayWorkflowItemFactoryTest extends TestCase
{
	use Specify;

    public function fixtures()
    {
        return [
            'items' => DynamicItemFixture::className(),
        ];
    }

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

			$factory = new ArrayWorkflowItemFactory([
				'workflowSourceNamespace' =>'a\b\c',
				'classMap' =>  [
					ArrayWorkflowItemFactory::CLASS_MAP_WORKFLOW   => 'my\namespace\Workflow',
					ArrayWorkflowItemFactory::CLASS_MAP_STATUS     => 'my\namespace\Status',
					ArrayWorkflowItemFactory::CLASS_MAP_TRANSITION => 'my\namespace\Transition'
				]
			]);
			expect($factory->getClassMapByType(ArrayWorkflowItemFactory::CLASS_MAP_WORKFLOW))->equals(	'my\namespace\Workflow'		);
			expect($factory->getClassMapByType(ArrayWorkflowItemFactory::CLASS_MAP_STATUS))->equals(	'my\namespace\Status'		);
			expect($factory->getClassMapByType(ArrayWorkflowItemFactory::CLASS_MAP_TRANSITION))->equals('my\namespace\Transition'	);
		});
	}

    public function testGetStatusFromFixedDefinition()
    {
        $factory = new ArrayWorkflowItemFactory(['workflowSourceNamespace' =>'tests\codeception\unit\models']);
        $status = $factory->getStatus('Item04Workflow/A', null, null);
        $this->assertEquals('Item04Workflow/A',$status->getId());

        $item = new Item04();
        $factory->workflowSourceNamespace = null;
        $status = $factory->getStatus('Item04Workflow/A', null, $item);
        $this->assertEquals('Item04Workflow/A',$status->getId());
    }

    public function testGetStatusFromDynamicDefinitionSuccess()
    {
        $factory = new ArrayWorkflowItemFactory();

        $item = $this->items('item1');

        $status = $factory->getStatus('Item04Workflow/D', null, $item);
        $this->assertEquals('Item04Workflow/D',$status->getId());

        $item = $this->items('item2');

        $status = $factory->getStatus('Item05Workflow/published', null, $item);
        $this->assertEquals('Item05Workflow/published',$status->getId());

        $item = $this->items('item4');

        $status = $factory->getStatus('Item07Workflow/E', null, $item);
        $this->assertEquals('Item07Workflow/E',$status->getId());
    }

    /**
     * @expectedException fproject\workflow\core\WorkflowException
     * @expectedExceptionMessage Failed to load workflow definition : Class tests\codeception\unit\models\SomethingSource does not exist
     */
    public function testGetStatusFromDynamicDefinitionFail1()
    {
        $factory = new ArrayWorkflowItemFactory();

        $item = $this->items('item5');

        $factory->getStatus('Something/Abc', null, $item);
    }

    /**
     * @expectedException fproject\workflow\core\WorkflowException
     * @expectedExceptionMessage Failed to load workflow definition : Class app\models\SomethingSource does not exist
     */
    public function testGetStatusFromDynamicDefinitionFail2()
    {
        $factory = new ArrayWorkflowItemFactory();
        $factory->getStatus('Something/Abc', null, null);
    }
}
