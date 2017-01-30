<?php
namespace bajadev\upload;

use Imagine\Image\Box;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\imagine\Image;

/**
 * Class ImageUploadBehavior
 */
class ImageUploadBehavior extends FileUploadBehavior
{
    public $attribute = 'image';

    public $createThumbsOnSave = true;
    public $createThumbsOnRequest = false;
    public $deleteOriginalFile = true;
    public $rotateImageByExif = true;

    /** @var array Thumbnail profiles, array of [width, height, ... PHPThumb options] */
    public $thumbs = [];

    /** @var string Path template for thumbnails. Please use the [[profile]] placeholder. */
    public $thumbPath = '@webroot/images/[[profile]]_[[pk]].[[extension]]';
    /** @var string Url template for thumbnails. */
    public $thumbUrl = '/images/[[profile]]_[[pk]].[[extension]]';

    public $filePath = '@webroot/images/[[pk]].[[extension]]';
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
            @unlink($this->getThumbFilePath($this->attribute, $profile));
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
            if (is_file($path) && !is_file($thumbPath)) {
                FileHelper::createDirectory(pathinfo($thumbPath, PATHINFO_DIRNAME), 0775, true);

                $imagine = Image::getImagine();
                $photo = $imagine->open($path);

                if ($this->rotateImageByExif) {
                    $photo = Image::autorotate($photo);
                }

                $quality = isset($config['quality']) ? $config['quality'] : 100;

                if (isset($config['crop']) && $config['crop'] == true) {
                    Image::thumbnail($photo, $config['width'], $config['height'])
                        ->save($thumbPath, ['quality' => $quality]);
                } else {
                    $photo->thumbnail(new Box($config['width'], $config['height']))->save($thumbPath,
                        ['quality' => $quality]);
                }
            }
        }

        if ($this->deleteOriginalFile) {
            @unlink($path);
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
