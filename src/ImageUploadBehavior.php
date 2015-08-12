<?php
/**
 * @author Alexey Samoylov <alexey.samoylov@gmail.com>
 * @link http://yiidreamteam.com/yii2/upload-behavior
 */

namespace yiidreamteam\upload;

use PHPThumb\GD;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Class ImageUploadBehavior
 */
class ImageUploadBehavior extends FileUploadBehavior
{
    public $attribute = 'image';

    public $createThumbsOnSave = true;
    public $createThumbsOnRequest = false;

    /** @var array Thumbnail profiles, array of [width, height] */
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
    public function cleanFiles($attribute = null)
    {
        $attribute = $this->getAttributeName($attribute);

        parent::cleanFiles();
        foreach (array_keys($this->thumbs) as $profile) {
            @unlink($this->getThumbFilePath($attribute, $profile));
        }
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
     * @param string $profile
     * @return string
     */
    public function getThumbFilePath($attribute = null, $profile = 'thumb')
    {
        $attribute = $this->getAttributeName($attribute);

        $behavior = static::getInstance($this->owner, $attribute);
        return $behavior->resolveProfilePath($behavior->thumbPath, $profile);
    }

    /**
     *
     * @param string|null $emptyUrl
     * @return string|null
     */
    public function getImageFileUrl($attribute = null, $emptyUrl = null)
    {
        $attribute = $this->getAttributeName($attribute);

        if (!$this->owner->{$attribute})
            return $emptyUrl;

        return $this->getUploadedFileUrl($this->attribute, $emptyUrl);
    }

    /**
     * @param string $profile
     * @param string|null $emptyUrl
     * @return string|null
     */
    public function getThumbFileUrl($attribute = null, $profile = 'thumb', $emptyUrl = null)
    {
        $attribute = $this->getAttributeName($attribute);

        if (!$this->owner->{$attribute})
            return $emptyUrl;

        $behavior = static::getInstance($this->owner, $this->attribute);
        if ($behavior->createThumbsOnRequest)
            $behavior->createThumbs();
        return $behavior->resolveProfilePath($behavior->thumbUrl, $profile);
    }

    /**
     * After file save event handler.
     */
    public function afterFileSave()
    {
        if ($this->createThumbsOnSave == true)
            $this->createThumbs();
    }

    /**
     * Creates image thumbnails
     */
    public function createThumbs($attribute = null)
    {
        $attribute = $this->getAttributeName($attribute);

        $path = $this->getUploadedFilePath($attribute);
        foreach ($this->thumbs as $profile => $config) {
            $thumbPath = static::getThumbFilePath($attribute, $profile);

            /** @var GD $thumb */
            $thumb = new GD($path);
            $thumb->adaptiveResize($config['width'], $config['height']);
            FileHelper::createDirectory(pathinfo($thumbPath, PATHINFO_DIRNAME), 0775, true);
            $thumb->save($thumbPath);
        }
    }
}
