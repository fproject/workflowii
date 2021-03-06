<?php

namespace tests\unit\workflow\activebehavior;

use Codeception\Specify;
use Yii;
use yii\codeception\TestCase;

use tests\codeception\unit\models\Item01;
use fproject\workflow\core\ActiveWorkflowBehavior;

class DiscoverWorkflowTest extends TestCase
{
	use Specify;

    public function testDefaultWorkflowIdCreation()
    {
    	$this->specify('a workflow Id is created if not provided', function () {
            /** @var ActiveWorkflowBehavior|Item01 $model */
    		$model = new Item01();
    		expect('model should have workflow id set to "Item01"', $model->getDefaultWorkflowId() == 'Item01Workflow' )->true();
    	});
    }
    public function testConfiguredWorkflowId()
    {
    	$this->specify('use the configured workflow Id', function () {
            /** @var ActiveWorkflowBehavior|Item01 $model */
    		$model = new Item01();
    		$model->attachBehavior('workflow', [
    			'class' => ActiveWorkflowBehavior::className(),
    			'defaultWorkflowId' => 'myWorkflow'
    		]);
    		expect('model should have workflow id set to "myWorkflow"', $model->getDefaultWorkflowId() == 'myWorkflow' )->true();
    	});
    }
// NOT (YET?) SUPPORTED
//     public function testWorkflowProvidedByModel()
//     {
//     	$this->specify('the provided workflow is accessible', function () {
//     		$model = new Item03();
//     		expect('model should have workflow is set to "Item03Workflow"', $model->getDefaultWorkflowId() == 'Item03Workflow' )->true();
//     		$source = $model->getWorkflowFactory();
//     		$w =  $source->getWorkflow('Item03Workflow');
//     		expect('provided workflow definition has been injected in the source component', $w != null)->true();
//     		expect('a status can be retrieved for the provided workflow', $source->getStatus('Item03Workflow/C') != null)->true();
//     	});
//     }
}
