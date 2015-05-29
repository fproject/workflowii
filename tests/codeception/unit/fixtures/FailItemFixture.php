<?php
namespace tests\codeception\unit\fixtures;

use yii\test\ActiveFixture;

class FailItemFixture extends ActiveFixture
{
    public $modelClass = 'tests\codeception\unit\models\DynamicItem';
    public $dataFile = '@tests/codeception/unit/fixtures/data/failitems.php';
}