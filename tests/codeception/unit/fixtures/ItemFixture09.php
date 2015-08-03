<?php
namespace tests\codeception\unit\fixtures;

use yii\test\ActiveFixture;

class ItemFixture09 extends ActiveFixture
{
    public $modelClass = 'tests\codeception\unit\models\Item09';
    public $dataFile = '@tests/codeception/unit/fixtures/data/items.php';
}