<?php

namespace tests\models;

use mohorev\file\UploadBehavior;
use yii\db\ActiveRecord;

/**
 * Class Document
 */
class File extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'file';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['file', 'file', 'extensions' => 'txt', 'on' => ['insert', 'update']],
        ];
    }

    /**
     * @inheritdoc
     */
    function behaviors()
    {
        return [
            [
                'class' => UploadBehavior::class,
                'attribute' => 'file',
                'scenarios' => ['insert', 'update'],
                'path' => '@webroot/upload/files/{year}',
                'url' => '@web/upload/files/{year}',
                'deleteEmptyDir' => true,
            ],
        ];
    }
}
