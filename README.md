Upload behavior for Yii 2
===========================

This behavior automatically uploads file and fills the specified attribute with a value of the name of the uploaded file.
This code is inspired by, but not derived from, https://github.com/yii-dream-team/yii2-upload-behavior.

[![Latest Version](https://img.shields.io/packagist/v/mohorev/yii2-upload-behavior.svg?style=flat-square)](https://packagist.org/packages/mohorev/yii2-upload-behavior)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/mohorev/yii2-upload-behavior/master.svg?style=flat-square)](https://travis-ci.org/mohorev/yii2-upload-behavior)
[![Quality Score](https://img.shields.io/scrutinizer/g/mohorev/yii2-upload-behavior.svg?style=flat-square)](https://scrutinizer-ci.com/g/mohorev/yii2-upload-behavior)

Installation
------------

The preferred way to install this extension via [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mohorev/yii2-upload-behavior "*"
```

or add this code line to the `require` section of your `composer.json` file:

```json
"mohorev/yii2-upload-behavior": "*"
```

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
        return $this->hasOne(Category::class, [ 'id' => 'id_category' ]);
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
                'path' => '@webroot/upload/docs/{category.id}',
                'url' => '@web/upload/docs/{category.id}',
            ],
        ];
    }
}
```

Set model scenario in controller action:

```php
class Controller extends Controller
{
    public function actionCreate($id)
    {
        $model = $this->findModel($id);
        $model->setScenario('insert'); // Note! Set upload behavior scenario.
        
        ...
        ...
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

Thumbnails processing requires [yiisoft/yii2-imagine](https://github.com/yiisoft/yii2-imagine) to be installed.

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
            [
                'class' => \mohorev\file\UploadImageBehavior::class,
                'attribute' => 'image',
                'scenarios' => ['insert', 'update'],
                'placeholder' => '@app/modules/user/assets/images/userpic.jpg',
                'path' => '@webroot/upload/user/{id}',
                'url' => '@web/upload/user/{id}',
                'thumbs' => [
                    'thumb' => ['width' => 400, 'quality' => 90],
                    'preview' => ['width' => 200, 'height' => 200],
                    'news_thumb' => ['width' => 200, 'height' => 200, 'bg_color' => '000'],
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

Behavior Options
-------

* attribute - The attribute which holds the attachment
* scenarios - The scenarios in which the behavior will be triggered
* instanceByName - Getting file instance by name, If you use UploadBehavior in `RESTfull` application and you do not need a prefix of the model name, set the property `instanceByName = false`, default value is `false`
* path - the base path or path alias to the directory in which to save files.
* url - the base URL or path alias for this file
* generateNewName - Set true or anonymous function takes the old filename and returns a new name, default value is `true`
* unlinkOnSave - If `true` current attribute file will be deleted, default value is `true`
* unlinkOnDelete - If `true` current attribute file will be deleted after model deletion.
* deleteOriginalFile - Only for UploadImageBehavior. If `true` original image file will be deleted after thumbs generating, default value is `false`.

### Attention!

It is prefered to use immutable placeholder in `url` and `path` options, other words try don't use related attributes that can be changed. There's bad practice. For example:

```php
class Track extends ActiveRecord
{
    public function getArtist()
    {
        return $this->hasOne(Artist::class, [ 'id' => 'id_artist' ]);
    }

    public function behaviors()
    {
        return [
            [
                'class' => UploadBehavior::class,
                'attribute' => 'image',
                'scenarios' => ['default'],
                'path' => '@webroot/uploads/{artist.slug}',
                'url' => '@web/uploads/{artist.slug}',
            ],
        ];
    }
}
```

If related model attribute `slug` will change, you must change folders' names too, otherwise behavior will works not correctly. 
