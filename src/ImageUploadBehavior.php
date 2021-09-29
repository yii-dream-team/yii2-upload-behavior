<?php

namespace bajadev\upload;

use Imagine\Image\Box;
use Imagine\Image\Point;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\imagine\Image;

/**
 * Class ImageUploadBehavior
 */
class ImageUploadBehavior extends FileUploadBehavior
{
    /**
     * @var string
     */
    public $attribute = 'image';
    /**
     * @var bool
     */
    public $createThumbsOnSave = true;
    /**
     * @var bool
     */
    public $createThumbsOnRequest = false;
    /**
     * @var bool
     */
    public $deleteOriginalFile = true;
    /**
     * @var bool
     */
    public $rotateImageByExif = true;

    /** @var array Thumbnail profiles, array of [width, height, ... PHPThumb options] */
    public $thumbs = [];

    /** @var string Path template for thumbnails. Please use the [[profile]] placeholder. */
    public $thumbPath = '@webroot/images/[[profile]]_[[pk]].[[extension]]';
    /** @var string Url template for thumbnails. */
    public $thumbUrl = '/images/[[profile]]_[[pk]].[[extension]]';
    /**
     * @var string
     */
    public $filePath = '@webroot/images/[[pk]].[[extension]]';
    /**
     * @var string
     */
    public $fileUrl = '/images/[[pk]].[[extension]]';

    /**
     * @inheritdoc
     */
    public function events()
    {
        return ArrayHelper::merge(parent::events(), [
            static::EVENT_AFTER_FILE_SAVE => 'afterFileSave',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function cleanFiles()
    {
        parent::cleanFiles();
        foreach (array_keys($this->thumbs) as $profile) {
            try {
                FileHelper::unlink($this->getThumbFilePath($this->attribute, $profile));
            } catch (\Exception $e) {
                \Yii::warning('File delete is failed');
            }
        }
    }

    /**
     * @param string $attribute
     * @param string $profile
     * @return string
     */
    public function getThumbFilePath($attribute, $profile = 'thumb')
    {
        $behavior = static::getInstance($this->owner, $attribute);
        return $behavior->resolveProfilePath($behavior->thumbPath, $profile);
    }

    /**
     * Resolves profile path for thumbnail profile.
     *
     * @param string $path
     * @param string $profile
     * @return string
     */
    public function resolveProfilePath($path, $profile)
    {
        $path = $this->resolvePath($path);
        return preg_replace_callback('|\[\[([\w\_/]+)\]\]|', function ($matches) use ($profile) {
            $name = $matches[1];
            switch ($name) {
                case 'profile':
                    return $profile;
            }
            return '[[' . $name . ']]';
        }, $path);
    }

    /**
     *
     * @param string $attribute
     * @param string|null $emptyUrl
     * @return string|null
     */
    public function getImageFileUrl($attribute, $emptyUrl = null)
    {
        if (!$this->owner->{$attribute}) {
            return $emptyUrl;
        }

        return $this->getUploadedFileUrl($attribute);
    }

    /**
     * @param string $attribute
     * @param string $profile
     * @param string|null $emptyUrl
     * @return string|null
     */
    public function getThumbFileUrl($attribute, $profile = 'thumb', $emptyUrl = null)
    {
        if (!$this->owner->{$attribute}) {
            return $emptyUrl;
        }

        $behavior = static::getInstance($this->owner, $attribute);
        if ($behavior->createThumbsOnRequest) {
            $behavior->createThumbs();
        }
        return $behavior->resolveProfilePath($behavior->thumbUrl, $profile);
    }

    /**
     * Creates image thumbnails
     */
    public function createThumbs()
    {
        $path = $this->getUploadedFilePath($this->attribute);
        foreach ($this->thumbs as $profile => $config) {
            $thumbPath = static::getThumbFilePath($this->attribute, $profile);
            if (!is_file($path) || is_file($thumbPath)) {
                continue;
            }
            FileHelper::createDirectory(pathinfo($thumbPath, PATHINFO_DIRNAME));
            $pathInfo = pathInfo($path);

            if ($pathInfo['extension'] !== 'svg') {
                $imagine = Image::getImagine();
                $photo = $imagine->open($path);

                if ($this->rotateImageByExif) {
                    $photo = Image::autorotate($photo);
                }

                $quality = ArrayHelper::getValue($config, 'quality', 100);
                $crop = ArrayHelper::getValue($config, 'crop', true);
                $insetMode = ArrayHelper::getValue($config, 'inset', false);

                if ($crop == true) {
                    $thumbnail = Image::thumbnail($photo, $config['width'], $config['height']);
                    if ($insetMode) {
                        $size = $thumbnail->getSize();
                        if ($size->getWidth() < $config['width'] or $size->getHeight() < $config['height']) {
                            $white = Image::getImagine()->create(new Box($config['width'], $config['height']));
                            $thumbnail = $white->paste($thumbnail,
                                new Point($config['width'] / 2 - $size->getWidth() / 2,
                                    $config['height'] / 2 - $size->getHeight() / 2)
                            );
                        }
                    }
                    $thumbnail->save($thumbPath, ['quality' => $quality]);
                } else {
                    $photo->thumbnail(new Box($config['width'], $config['height']))
                        ->save($thumbPath, ['quality' => $quality]);
                }
            } else {
                copy($path, $thumbPath);
            }
        }

        if ($this->deleteOriginalFile) {
            try {
                FileHelper::unlink($path);
            } catch (\Exception $e) {
                \Yii::warning('File delete is failed');
            }
        }
    }

    /**
     * After file save event handler.
     */
    public function afterFileSave()
    {
        if ($this->createThumbsOnSave == true) {
            $this->createThumbs();
        }
    }
}
