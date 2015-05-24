<?php

namespace tests\codeception\unit\models;

use Yii;

/**
 * This is the model class for table "item".
 *
 * @property integer $id
 * @property string $name
 * @property string $status
 * @property string $dynamicWorkflowId
 */
class DynamicItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'item';
    }
}
