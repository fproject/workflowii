<?php

namespace tests\codeception\unit\models;

use fproject\workflow\core\ActiveWorkflowBehavior;
use Yii;

/**
 * This is the model class for table "item".
 *
 * @property integer $id
 * @property string $name
 * @property string $status
 * @property string $dynamicWorkflowId
 */
class Item09 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'item';
    }

    /**
     * (non-PHPdoc)
     * @see \yii\base\Component::behaviors()
     */
    public function behaviors()
    {
        return [
            'wf' => [
                'class' => ActiveWorkflowBehavior::className(),
                'idAccessor' => 'wfIdAccessor'
            ]
        ];
    }
}
