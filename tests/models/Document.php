<?php

namespace tests\models;

use mongosoft\file\UploadBehavior;
use yii\db\ActiveRecord;

/**
 * Class Document
 */
class Document extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'document';
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
                'class' => UploadBehavior::className(),
                'attribute' => 'file',
                'scenarios' => ['insert', 'update'],
                'path' => '@webroot/upload/docs/{id}',
                'url' => '@web/upload/docs/{id}',
            ],
        ];
    }
}
