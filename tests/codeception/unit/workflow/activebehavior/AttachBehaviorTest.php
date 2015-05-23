<?php

namespace tests\unit\workflow\activebehavior;

use Yii;
use yii\codeception\TestCase;
use tests\codeception\unit\models\Item01;
use fproject\workflow\core\ActiveWorkflowBehavior;

class AttachBehaviorTest extends TestCase
{
	use \Codeception\Specify;


    public function testAttachCorrect()
    {
    	$model = new Item01();

    	$this->specify('behavior can be attached to ActiveRecord', function () use ($model) {
    		$behaviors = $model->behaviors();
    		expect('model should have the "workflow" behavior attached', isset($behaviors['workflow']) )->true();
    		expect('model has a ActiveWorkflowBehavior attached', ActiveWorkflowBehavior::isAttachedTo($model) )->true();
    	});
    }

    public function testAttachFails1()
    {
    	$this->specify('behavior cannot be attached to a non-ActiveRecord object', function () {
    		$model = Yii::createObject("yii\base\Component",[]);
    		$model->attachBehavior('workflow', ActiveWorkflowBehavior::className());
    	},['throws' => 'yii\base\InvalidConfigException']);
    }

    public function testAttachFails2()
    {
    	$this->specify('the status attribute cannot be empty', function () {
    		$model = new Item01();
    		expect('model has a ActiveWorkflowBehavior attached', ActiveWorkflowBehavior::isAttachedTo($model) )->true();
    		$model->detachBehavior('workflow');
    		expect('model has a NO ActiveWorkflowBehavior attached', ActiveWorkflowBehavior::isAttachedTo($model) )->false();
    		$model->attachBehavior('workflow', [ 'class' =>  ActiveWorkflowBehavior::className(), 'statusAttribute' => '' ]);
    	},['throws' => 'yii\base\InvalidConfigException']);
    }

    public function testAttachFails3()
    {
    	$this->specify('the status attribute must exist in the owner model', function () {
    		$model = new Item01();
    		$model->detachBehavior('workflow');
    		$model->attachBehavior('workflow', [ 'class' =>  ActiveWorkflowBehavior::className(), 'statusAttribute' => 'not_found' ]);
    	},['throws' => 'yii\base\InvalidConfigException']);
    }
}
