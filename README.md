Upload behavior for Yii 2
===========================

This behavior automatically uploads file and fills the specified attribute with a value of the name of the uploaded file.

[![Latest Stable Version](https://poser.pugx.org/mongosoft/yii2-upload-behavior/v/stable.png)](https://packagist.org/packages/mongosoft/yii2-upload-behavior)
[![Total Downloads](https://poser.pugx.org/mongosoft/yii2-upload-behavior/downloads.png)](https://packagist.org/packages/mongosoft/yii2-upload-behavior)

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mongosoft/yii2-upload-behavior "*"
```

or add

```json
"mongosoft/yii2-upload-behavior": "*"
```

to the `require` section of your `composer.json` file.

Usage
-----

### Upload file

Attach the behavior in your model:

```php
class Document extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['file', 'file', 'extensions' => 'doc, docx, pdf', 'on' => ['insert', 'update']],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), [ 'id' => 'id_category' ]);
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
                'path' => '@webroot/upload/docs/{category.id}/',
                'url' => '@web/upload/docs/{category.id}/',
            ],
        ];
    }
}
```

Example view file:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'image')->fileInput() ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```

### Upload image and create thumbnails

Attach the behavior in your model:

```php
class User extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['image', 'image', 'extensions' => 'jpg, jpeg, gif, png', 'on' => ['insert', 'update']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'image' => [
                'class' => UploadImageBehavior::className(),
                'attribute' => 'image',
                'scenarios' => ['insert', 'update'],
                'placeholder' => '@app/modules/user/assets/images/userpic.jpg',
                'path' => '@webroot/upload/user/{id}',
                'url' => '@web/upload/user/{id}',
                'thumbs' => [
                    'thumb' => ['width' => 400, 'quality' => 90],
                    'preview' => ['width' => 200, 'height' => 200],
                ],
            ],
        ];
    }
}
```

Example view file:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <div class="form-group">
        <div class="row">
            <div class="col-lg-6">
                <!-- Original image -->
                <?= Html::img($model->getUploadUrl('image'), ['class' => 'img-thumbnail']) ?>
            </div>
            <div class="col-lg-4">
                <!-- Thumb 1 (thumb profile) -->
                <?= Html::img($model->getThumbUploadUrl('image'), ['class' => 'img-thumbnail']) ?>
            </div>
            <div class="col-lg-2">
                <!-- Thumb 2 (preview profile) -->
                <?= Html::img($model->getThumbUploadUrl('image', 'preview'), ['class' => 'img-thumbnail']) ?>
            </div>
        </div>
    </div>
    <?= $form->field($model, 'image')->fileInput(['accept' => 'image/*']) ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```

### RESTfull

If you use UploadBehavior in RESTfull application and you do not need a prefix of the model name, set the property `instanceByName = false`:

```php
/**
 * @inheritdoc
 */
function behaviors()
{
    return [
        [
            'class' => UploadBehavior::className(),
            'attribute' => 'file',
            'instanceByName' => true,
            'scenarios' => ['insert', 'update'],
            'path' => '@webroot/upload/docs',
            'url' => '@web/upload/docs',
        ],
    ];
}
```
