Upload behavior for Yii 2
===========================

This behavior automatically uploads file and fills the specified attribute with a value of the name of the uploaded file.

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

to the `require` section of your composer.json.

Usage
-----

### Upload file

```php
    function behaviors()
    {
        return [
            [
                'class' => UploadBehavior::className(),
                'attribute' => 'file',
                'scenarios' => ['insert', 'update'],
                'path' => '@webroot/upload/{id}',
                'url' => '@web/upload/{id}',
            ],
        ];
    }
```

### Upload image and create thumbnails

```php
    function behaviors()
    {
        return [
            [
                'class' => UploadBehavior::className(),
                'attribute' => 'file',
                'scenarios' => ['insert', 'update'],
                'placeholder' => '@app/modules/user/assets/images/userpic.jpg',
                'path' => '@webroot/upload/{id}/images',
                'url' => '@web/upload/{id}/images',
            ],
        ];
    }
```