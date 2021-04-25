<?php

namespace mohorev\file;

use Closure;
use Yii;
use yii\base\Behavior;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * UploadBehavior automatically uploads file and fills the specified attribute
 * with a value of the name of the uploaded file.
 *
 * To use UploadBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * use mohorev\file\UploadBehavior;
 *
 * function behaviors()
 * {
 *     return [
 *         'upload' => [
 *             'class' => UploadBehavior::class,
 *             'attribute' => 'file',
 *             // 'scenarios' => ['insert', 'update'],
 *             'path' => '@webroot/upload/{id}',
 *             'url' => '@web/upload/{id}',
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Alexander Mohorev <dev.mohorev@gmail.com>
 * @author Alexey Samoylov <alexey.samoylov@gmail.com>
 */
class UploadBehavior extends Behavior
{
    /**
     * @event ModelEvent an event that is triggered after a file is uploaded.
     * @var string
     */
    const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * @var string the attribute which holds the attachment.
     */
    public $attribute;
    /**
     * @var string[] the scenarios in which the behavior will be triggered
     */
    public $scenarios = [BaseActiveRecord::SCENARIO_DEFAULT];
    /**
     * @var string the base path or path alias to the directory in which to save files.
     */
    public $path;
    /**
     * @var string the base URL or path alias for this file
     */
    public $url;
    /**
     * @var bool Getting file instance by name
     */
    public $instanceByName = false;
    /**
     * @var bool|callable generate a new unique name for the file
     * set true or anonymous function takes the old filename and returns a new name.
     * @see self::generateFileName()
     */
    public $generateNewName = true;
    /**
     * @var bool If `true` current attribute file will be deleted
     */
    public $unlinkOnSave = true;
    /**
     * @var bool If `true` current attribute file will be deleted after model deletion.
     */
    public $unlinkOnDelete = true;
    /**
     * @var bool $deleteTempFile whether to delete the temporary file after saving.
     */
    public $deleteTempFile = true;
    /**
     * @var UploadedFile the uploaded file instance.
     */
    protected $file;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->owner instanceof BaseActiveRecord) {
            throw new NotSupportedException('Owner must inherit \yii\db\BaseActiveRecord.');
        }
        if ($this->attribute === null) {
            throw new InvalidConfigException('The "attribute" property must be set.');
        }
        if (!$this->owner->hasAttribute($this->attribute)) {
            throw new InvalidConfigException('The attribute not defined in owner.');
        }
        if ($this->path === null) {
            throw new InvalidConfigException('The "path" property must be set.');
        }
        if ($this->url === null) {
            throw new InvalidConfigException('The "url" property must be set.');
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * This method is invoked before validation starts.
     */
    public function beforeValidate()
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        if (\in_array($model->getScenario(), $this->scenarios, true)) {
            $file = $model->getAttribute($this->attribute);
            if ($file instanceof UploadedFile) {
                $this->file = $file;
            } else {
                if ($this->instanceByName === true) {
                    $this->file = UploadedFile::getInstanceByName($this->attribute);
                } else {
                    $this->file = UploadedFile::getInstance($model, $this->attribute);
                }
            }
            if ($this->file instanceof UploadedFile) {
                $this->file->name = $this->getFileName($this->file);
                $model->setAttribute($this->attribute, $this->file);
            }
        }
    }

    /**
     * This method is called at the beginning of inserting or updating a record.
     */
    public function beforeSave()
    {
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        if (\in_array($model->getScenario(), $this->scenarios, true)) {
            if ($this->file instanceof UploadedFile) {
                if (!$model->getIsNewRecord() && $model->isAttributeChanged($this->attribute)) {
                    if ($this->unlinkOnSave === true) {
                        $this->delete($this->attribute, true);
                    }
                }
                $model->setAttribute($this->attribute, $this->file->name);
            } else {
                // Protect attribute
                unset($model->{$this->attribute});
            }
        } else {
            if (!$model->getIsNewRecord() && $model->isAttributeChanged($this->attribute)) {
                if ($this->unlinkOnSave === true) {
                    $this->delete($this->attribute, true);
                }
            }
        }
    }

    /**
     * This method is called at the end of inserting or updating a record.
     * @throws \yii\base\InvalidArgumentException
     */
    public function afterSave()
    {
        if ($this->file instanceof UploadedFile) {
            $path = $this->getUploadPath($this->attribute);
            if (\is_string($path) && FileHelper::createDirectory(\dirname($path))) {
                $this->save($this->file, $path);
                $this->afterUpload();
            } else {
                throw new InvalidArgumentException(
                    "Directory specified in 'path' attribute doesn't exist or cannot be created."
                );
            }
        }
    }

    /**
     * This method is invoked after deleting a record.
     */
    public function afterDelete()
    {
        if ($this->unlinkOnDelete && $this->attribute) {
            $this->delete($this->attribute);
        }
    }

    /**
     * Returns file path for the attribute.
     * @param string $attribute
     * @param bool $old
     * @return string|null the file path.
     */
    public function getUploadPath($attribute, $old = false)
    {
        $path = $this->resolvePath($this->path);
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        $fileName = $old === true ? $model->getOldAttribute($attribute) : $model->getAttribute($attribute);

        return $fileName ? Yii::getAlias($path . DIRECTORY_SEPARATOR . $fileName) : null;
    }

    /**
     * Returns file url for the attribute.
     * @param string $attribute
     * @return string|null
     */
    public function getUploadUrl($attribute)
    {
        $url = $this->resolvePath($this->url);
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        $fileName = $model->getOldAttribute($attribute);

        return $fileName ? Yii::getAlias($url . '/' . $fileName) : null;
    }

    /**
     * Returns the UploadedFile instance.
     * @return UploadedFile
     */
    protected function getUploadedFile()
    {
        return $this->file;
    }

    /**
     * Replaces all placeholders in path variable with corresponding values.
     * @param string $path
     * @return string
     */
    protected function resolvePath($path)
    {
        return \preg_replace_callback(
            '/\{([^}]+)\}/',
            function ($matches) {
                /** @var BaseActiveRecord $model */
                $model = $this->owner;
                $attribute = $model->getAttribute($matches[1]);
                if (\is_string($attribute) || \is_numeric($attribute)) {
                    return $attribute;
                }
                return $matches[0];
            }, 
            $path
        );
    }

    /**
     * Saves the uploaded file.
     * @param UploadedFile $file the uploaded file instance
     * @param string $path the file path used to save the uploaded file
     * @return bool true whether the file is saved successfully
     */
    protected function save($file, $path)
    {
        return $file->saveAs($path, $this->deleteTempFile);
    }

    /**
     * Deletes old file.
     * @param string $attribute
     * @param bool $old
     */
    protected function delete($attribute, $old = false)
    {
        $path = $this->getUploadPath($attribute, $old);
        if (\is_file($path)) {
            \unlink($path);
        }
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function getFileName(UploadedFile $file)
    {
        if ($this->generateNewName) {
            return $this->generateNewName instanceof Closure
                ? \call_user_func($this->generateNewName, $file)
                : $this->generateFileName($file);
        }
        return $this->sanitizeFileName($file->name);
    }

    /**
     * Replaces characters in strings that are illegal/unsafe for filename.
     * @param string $filename the source filename to be "sanitized"
     * @return string string the sanitized filename
     */
    protected static function sanitizeFileName($filename)
    {
        return \preg_replace('/[^-.\w]+/i', '', $filename);
    }

    /**
     * Generates random filename.
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFileName(UploadedFile $file)
    {
        return \str_replace('.', '', \uniqid('', true)) . '.' . $file->extension;
    }

    /**
     * This method is invoked after uploading a file.
     * The default implementation raises the [[EVENT_AFTER_UPLOAD]] event.
     * You may override this method to do postprocessing after the file is uploaded.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    protected function afterUpload()
    {
        $this->owner->trigger(self::EVENT_AFTER_UPLOAD, new ModelEvent());
    }
}
