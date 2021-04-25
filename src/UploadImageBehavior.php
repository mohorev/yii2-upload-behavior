<?php

namespace mohorev\file;

use Imagine\Image\ManipulatorInterface;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\imagine\Image;

/**
 * UploadImageBehavior automatically uploads image, creates thumbnails and fills
 * the specified attribute with a value of the name of the uploaded image.
 *
 * To use UploadImageBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * use mohorev\file\UploadImageBehavior;
 *
 * function behaviors()
 * {
 *     return [
 *         [
 *             'class' => UploadImageBehavior::class,
 *             'attribute' => 'file',
 *             'scenarios' => ['insert', 'update'],
 *             'placeholder' => '@app/modules/user/assets/images/userpic.jpg',
 *             'path' => '@webroot/upload/{id}/images',
 *             'url' => '@web/upload/{id}/images',
 *             'thumbPath' => '@webroot/upload/{id}/images/thumb',
 *             'thumbUrl' => '@web/upload/{id}/images/thumb',
 *             'thumbs' => [
 *                   'thumb' => ['width' => 400, 'quality' => 90],
 *                   'preview' => ['width' => 200, 'height' => 200],
 *              ],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Alexander Mohorev <dev.mohorev@gmail.com>
 * @author Alexey Samoylov <alexey.samoylov@gmail.com>
 */
class UploadImageBehavior extends UploadBehavior
{
    /**
     * @var string
     */
    public $placeholder;
    /**
     * @var bool
     */
    public $createThumbsOnSave = true;
    /**
     * @var bool
     */
    public $createThumbsOnRequest = false;
    /**
     * Whether delete original uploaded image after thumbs generating.
     * Defaults to FALSE
     * @var bool
     */
    public $deleteOriginalFile = false;
    /**
     * @var array[] the thumbnail profiles
     * - `width`
     * - `height`
     * - `quality`
     */
    public $thumbs = [
        'thumb' => ['width' => 200, 'height' => 200, 'quality' => 90],
    ];
    /**
     * @var string|null
     */
    public $thumbPath;
    /**
     * @var string|null
     */
    public $thumbUrl;


    /**
     * @inheritdoc
     */
    public function init()
    {

        parent::init();

        if ($this->createThumbsOnSave || $this->createThumbsOnRequest) {
           if (!class_exists(Image::class)) {
                throw new NotSupportedException("Composer package 'yiisoft/yii2-imagine' is required");
            }
            if ($this->thumbPath === null) {
                $this->thumbPath = $this->path;
            }
            if ($this->thumbUrl === null) {
                $this->thumbUrl = $this->url;
            }

            foreach ($this->thumbs as $id => $config) {
                $width = ArrayHelper::getValue($config, 'width', 0);
                $height = ArrayHelper::getValue($config, 'height', 0);
                if ($height < 1 && $width < 1) {
                    $error = Yii::t(
                        'app',
                        'Length of either side of thumb cannot be 0 or negative ({id} {width}x{$height})',
                        ['id' => $id, 'width' => $width, 'height' => $height]
                    );
                    throw new InvalidConfigException($error);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function afterUpload()
    {
        parent::afterUpload();

        if ($this->createThumbsOnSave) {
            $this->createThumbs();
        }
    }

    /**
     * @return void
     * @throws \yii\base\InvalidArgumentException
     */
    protected function createThumbs()
    {
        $path = $this->getUploadPath($this->attribute);
        if (!\is_file($path)) {
            return;
        }
        
        foreach ($this->thumbs as $profile => $config) {
            $thumbPath = $this->getThumbUploadPath($this->attribute, $profile);
            if ($thumbPath !== null) {
                if (!FileHelper::createDirectory(\dirname($thumbPath))) {
                    throw new InvalidArgumentException(
                        "Directory specified in 'thumbPath' attribute doesn't exist or cannot be created."
                    );
                }
                if (!\is_file($thumbPath)) {
                    $this->generateImageThumb($config, $path, $thumbPath);
                }
            }
        }
        
        if ($this->deleteOriginalFile) {
            parent::delete($this->attribute);
        }
    }

    /**
     * @param string $attribute
     * @param string $profile
     * @param bool $old
     * @return string|null
     */
    public function getThumbUploadPath($attribute, $profile = 'thumb', $old = false)
    {
        $path = $this->resolvePath($this->thumbPath);
        /** @var BaseActiveRecord $model */
        $model = $this->owner;
        $attribute = $old === true ? $model->getOldAttribute($attribute) : $model->getAttribute($attribute);
        $filename = $this->getThumbFileName($attribute, $profile);
        
        return $filename ? Yii::getAlias($path . DIRECTORY_SEPARATOR . $filename) : null;
    }

    /**
     * @param string $attribute
     * @param string $profile
     * @return string|null
     */
    public function getThumbUploadUrl($attribute, $profile = 'thumb')
    {
        if ($this->createThumbsOnRequest) {
            $this->createThumbs();
        }
        
        if (\is_file($this->getThumbUploadPath($attribute, $profile))) {
            $url = $this->resolvePath($this->thumbUrl);
            /** @var BaseActiveRecord $model */
            $model = $this->owner;
            $fileName = $model->getOldAttribute($attribute);
            $thumbName = $this->getThumbFileName($fileName, $profile);

            return Yii::getAlias($url . '/' . $thumbName);
        } elseif ($this->placeholder) {
            return $this->getPlaceholderUrl($profile);
        }
 
        return null;
    }

    /**
     * @param string $profile
     * @return string
     */
    protected function getPlaceholderUrl($profile)
    {
        list($path, $url) = Yii::$app->assetManager->publish($this->placeholder);
        $filename = \basename($path);
        $thumb = $this->getThumbFileName($filename, $profile);
        $thumbPath = \dirname($path) . DIRECTORY_SEPARATOR . $thumb;
        $thumbUrl = \dirname($url) . '/' . $thumb;

        if (!\is_file($thumbPath)) {
            $this->generateImageThumb($this->thumbs[$profile], $path, $thumbPath);
        }

        return $thumbUrl;
    }

    /**
     * @inheritdoc
     */
    protected function delete($attribute, $old = false)
    {
        parent::delete($attribute, $old);

        $profiles = array_keys($this->thumbs);
        foreach ($profiles as $profile) {
            $path = $this->getThumbUploadPath($attribute, $profile, $old);
            if (\is_file($path)) {
                \unlink($path);
            }
        }
    }

    /**
     * @param string $filename
     * @param string $profile
     * @return string
     */
    protected function getThumbFileName($filename, $profile = 'thumb')
    {
        return $profile . '-' . $filename;
    }

    /**
     * @param array $config
     * @param string $path
     * @param string $thumbPath
     * @return void
     */
    protected function generateImageThumb($config, $path, $thumbPath)
    {
        $width = ArrayHelper::getValue($config, 'width', 0);
        $height = ArrayHelper::getValue($config, 'height', 0);

        if (!$width || !$height) {
            $image = Image::getImagine()->open($path);
            $ratio = $image->getSize()->getWidth() / $image->getSize()->getHeight();
            if ($width) {
                $height = \ceil($width / $ratio);
            } else {
                $width = \ceil($height * $ratio);
            }
        }

        $bgColor = ArrayHelper::getValue($config, 'bg_color', false);
        if ($bgColor !== false) {
            Image::$thumbnailBackgroundColor = $bgColor;
        }

        $mode = ArrayHelper::getValue($config, 'mode', ManipulatorInterface::THUMBNAIL_INSET);
        $quality = ArrayHelper::getValue($config, 'quality', 100);

        // Fix error "PHP GD Allowed memory size exhausted".
        $oldLimit = \ini_get('memory_limit');
        \ini_set('memory_limit', '128M');
        Image::thumbnail($path, $width, $height, $mode)->save($thumbPath, ['quality' => $quality]);
        \ini_set('memory_limit', $oldLimit);
    }
}
