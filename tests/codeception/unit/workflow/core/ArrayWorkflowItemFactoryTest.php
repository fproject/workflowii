<?php

namespace tests\unit\workflow\core;

use Codeception\Specify;
use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use tests\codeception\unit\fixtures\DynamicItemFixture;
use tests\codeception\unit\models\DynamicItem;
use tests\codeception\unit\models\Item00;
use tests\codeception\unit\models\Item04;
use tests\codeception\unit\models\Item04WorkflowSource;
use tests\codeception\unit\models\Item05WorkflowSource;
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

    /** @var  ArrayWorkflowItemFactory $factory*/
    public $factory;

    protected function setUp()
    {
        parent::setUp();
        $this->factory = new ArrayWorkflowItemFactory();
    }

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

    public function testGetStatusFromDynamicDefinitionSuccess1()
    {
        $factory = new ArrayWorkflowItemFactory();

        $item = $this->items('item1');

        $status = $factory->getStatus('Item04Workflow/D', null, $item);
        $this->assertEquals('Item04Workflow/D',$status->getId());
    }

    public function testGetStatusFromDynamicDefinitionSuccess2()
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

    public function testGetStatusFromDynamicDefinitionSuccess3()
    {
        $factory = new ArrayWorkflowItemFactory();

        $item = $this->items('item3');

        $status = $factory->getStatus('Item06Workflow/published', 'Item06Workflow', $item);
        $this->assertEquals('Item06Workflow/published',$status->getId());
    }

    public function testGetStatusFromDynamicDefinitionSuccess4()
    {
        $factory = new ArrayWorkflowItemFactory();

        $item = $this->items('item1');

        $status = $factory->getStatus('Item04Workflow/D', null, $item);
        $this->assertEquals('Item04Workflow/D',$status->getId());

        $item = $this->items('item2');

        $status = $factory->getStatus('Item05Workflow/published', null, $item);
        $this->assertEquals('Item05Workflow/published',$status->getId());

        $item = $this->items('item3');

        $status = $factory->getStatus('Item06Workflow/published', 'Item06Workflow', $item);
        $this->assertEquals('Item06Workflow/published',$status->getId());

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

    /**
     * @expectedException fproject\workflow\core\WorkflowException
     * @expectedExceptionMessage No status found with id Item07Workflow/X
     */
    public function testGetStatusFromDynamicDefinitionFail3()
    {
        $factory = new ArrayWorkflowItemFactory();

        $item = $this->items('item4');

        $factory->getStatus('Item07Workflow/X', null, $item);
    }

    /**
     * @expectedException fproject\workflow\core\WorkflowException
     * @expectedExceptionMessage No status found with id Item07Workflow/X
     */
    public function testGetStatusFromDynamicDefinitionFail4()
    {
        $factory = new ArrayWorkflowItemFactory();

        $item = $this->items('item2');

        $factory->getStatus('Item04Workflow/D', null, $item);
    }

    public function testParseWorkflowAndStatusId()
    {
        list($wId, $lid) = $this->factory->parseWorkflowAndStatusId('Wid/Id', null, null);
        $this->assertEquals('Wid', $wId);
        $this->assertEquals('Id', $lid);
        $this->assertTrue(count($this->factory->parseWorkflowAndStatusId('Wid/Id', null, null)) == 2);
    }

    public function testParseWorkflowAndStatusIdWithModel()
    {
        $item = $this->items('item2');

        list($wId, $lid) = $this->factory->parseWorkflowAndStatusId('Item04Workflow/D', null, $item);
        $this->assertEquals('Item04Workflow', $wId);
        $this->assertEquals('D', $lid);
        $this->assertTrue(count($this->factory->parseWorkflowAndStatusId('Wid/Id', null, null)) == 2);
    }

    public function testGetWorkflowDefinition()
    {
        $wfDef = $this->factory->getWorkflowDefinition('Item04Workflow', null);
        $item04WfSrc = new Item04WorkflowSource();
        $expected = $item04WfSrc->getDefinition(null);
        $this->assertTrue($expected == $wfDef);
    }

    public function testGetWorkflowDefinitionWithModel()
    {
        $item = $this->items('item2');
        $wfDef = $this->factory->getWorkflowDefinition('Item05Workflow', $item);
        $item05WfSrc = new Item05WorkflowSource();
        $expected = $item05WfSrc->getDefinition(null);
        $this->assertTrue($expected == $wfDef);
    }

    public function testIsValidWorkflowId()
    {
        $this->assertFalse($this->factory->isValidWorkflowId('workflow id'));
        $this->assertFalse($this->factory->isValidWorkflowId('-workflowId'));
        $this->assertFalse($this->factory->isValidWorkflowId(' workflowId'));
        $this->assertFalse($this->factory->isValidWorkflowId('workflowId/'));
        $this->assertFalse($this->factory->isValidWorkflowId('1'));
        $this->assertFalse($this->factory->isValidWorkflowId('WORKFLOW_id'));

        $this->assertTrue($this->factory->isValidWorkflowId('workflowId'));
        $this->assertTrue($this->factory->isValidWorkflowId('workflow-Id'));
        $this->assertTrue($this->factory->isValidWorkflowId('workflow01-Id02'));
        $this->assertTrue($this->factory->isValidWorkflowId('w01-2'));
    }

    public function testIsValidStatusId()
    {
        $this->assertFalse($this->factory->isValidStatusId('id'));
        $this->assertFalse($this->factory->isValidStatusId('/id'));
        $this->assertFalse($this->factory->isValidStatusId('id/'));
        $this->assertFalse($this->factory->isValidStatusId('/'));
        $this->assertFalse($this->factory->isValidStatusId('workflow_id/status_id'));
        $this->assertFalse($this->factory->isValidStatusId('workflow id/status id'));

        $this->assertTrue($this->factory->isValidStatusId('ID/ID'));
        $this->assertTrue($this->factory->isValidStatusId('workflow-id/status-id'));
    }

    /**
     * @expectedException fproject\workflow\core\WorkflowValidationException
     * @expectedExceptionMessageRegExp #No status definition found#
     */
    public function testAddInvalidWorkflowDefinition()
    {
        $this->factory->addWorkflowDefinition('wid', ['initialStatusId' => 'A']);
    }

    public function testGetWorkflowSourceClassNameFail()
    {
        $this->specify('exception thrown on invalid workflow id', function() {
            $this->factory->getWorkflowSourceClassName('', null);
        },['throws'=> 'fproject\workflow\core\WorkflowException']);
    }

    public function testGetWorkflowSourceClassNameSuccess1()
    {
        $this->factory->workflowSourceNamespace = null;
        $this->assertEquals('app\models\PostWorkflowSource', $this->factory->getWorkflowSourceClassName('PostWorkflow', null));
        $this->factory->workflowSourceNamespace = 'a\b\c';
        $this->assertEquals('a\b\c\PostWorkflowSource', $this->factory->getWorkflowSourceClassName('PostWorkflow', null));
        $this->factory->workflowSourceNamespace = '';
        $this->assertEquals('\PostWorkflowSource', $this->factory->getWorkflowSourceClassName('PostWorkflow', null));
    }

    public function testGetWorkflowSourceClassNameSuccess2()
    {
        $item = new Item00();
        $this->factory->workflowSourceNamespace = null;
        $this->assertEquals('tests\codeception\unit\models\Item00WorkflowSource', $this->factory->getWorkflowSourceClassName('Item00Workflow', $item));
    }

    public function testFailToLoadWorkflowSourceClass()
    {
        $this->specify('incorrect status id format', function () {
            $this->factory->getStatus('id', null, null);
        },['throws' => 'fproject\workflow\core\WorkflowException']);

        $this->specify('empty provider fails to load workflow from non-existant workflow class', function () {
            $this->factory->getWorkflow('id', null);
        },['throws' => 'fproject\workflow\core\WorkflowException']);

        $this->specify('empty provider fails to load status from non-existant workflow class', function () {
            $this->factory->getStatus('w/s', null, null);
        },['throws' => 'fproject\workflow\core\WorkflowException']);

        $this->specify('empty provider fails to load transition from non-existant workflow class', function ()  {
            $this->factory->getTransitions('w/s', null, null);
        },['throws' => 'fproject\workflow\core\WorkflowException']);
    }
}
