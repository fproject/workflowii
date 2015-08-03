<?php

namespace tests\unit\workflow\activebehavior;

use Codeception\Specify;
use tests\codeception\unit\fixtures\ItemFixture09;
use tests\codeception\unit\models\Item09;
use Yii;
use yii\codeception\TestCase;
use tests\codeception\unit\models\Item01;
use fproject\workflow\core\ActiveWorkflowBehavior;

/**
 * Class ChangeStatusTest
 *
 * @method Item09[] items()
 *
 * @package tests\unit\workflow\activebehavior
 */
class AttachBehaviorTest extends TestCase
{
	use Specify;

    public function fixtures()
    {
        return [
            'items' => ItemFixture09::className(),
        ];
    }

    protected function setup()
    {
        parent::setUp();
        Yii::$app->set('wfIdAccessor',[
            'class'=> 'tests\codeception\unit\models\IdAccessor09',
        ]);
    }

    public function testAttachBehaviorSuccess1()
    {
    	$model = new Item01();

    	$this->specify('behavior can be attached to ActiveRecord', function () use ($model) {
    		$behaviors = $model->behaviors();
    		expect('model should have the "workflow" behavior attached', isset($behaviors['workflow']) )->true();
    		expect('model has a ActiveWorkflowBehavior attached', ActiveWorkflowBehavior::isAttachedTo($model) )->true();
    	});
    }

    public function testAttachBehaviorSuccess2()
    {
        /** @var Item09|ActiveWorkflowBehavior $model */
        $model = $this->items('item6');

        $this->specify('behavior with idAccessor specified can be attached to ActiveRecord', function () use ($model) {
            expect('idAccessor is set',isset($model->idAccessor))->true();
            expect('idAccessor equals to \'wfIdAccessor\'',$model->idAccessor)->equals('wfIdAccessor');
            expect('workflow is defined by idAccessor and come from \'dynamicWorkflowId\' field', $model->getWorkflow()->getId())->equals($model->dynamicWorkflowId);
        });
    }


    public function testAttachBehaviorFails1()
    {
    	$this->specify('behavior cannot be attached to a non-ActiveRecord object', function () {
    		$model = Yii::createObject("yii\base\Component",[]);
    		$model->attachBehavior('workflow', ActiveWorkflowBehavior::className());
    	},['throws' => 'yii\base\InvalidConfigException']);
    }

    public function testAttachBehaviorFails2()
    {
    	$this->specify('the status attribute cannot be empty', function () {
    		$model = new Item01();
    		expect('model has a ActiveWorkflowBehavior attached', ActiveWorkflowBehavior::isAttachedTo($model) )->true();
    		$model->detachBehavior('workflow');
    		expect('model has a NO ActiveWorkflowBehavior attached', ActiveWorkflowBehavior::isAttachedTo($model) )->false();
    		$model->attachBehavior('workflow', [ 'class' =>  ActiveWorkflowBehavior::className(), 'statusAttribute' => '' ]);
    	},['throws' => 'yii\base\InvalidConfigException']);
    }

    public function testAttachBehaviorFails3()
    {
    	$this->specify('the status attribute must exist in the owner model', function () {
    		$model = new Item01();
    		$model->detachBehavior('workflow');
    		$model->attachBehavior('workflow', [ 'class' =>  ActiveWorkflowBehavior::className(), 'statusAttribute' => 'not_found' ]);
    	},['throws' => 'yii\base\InvalidConfigException']);
    }
}
