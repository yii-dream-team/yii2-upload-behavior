<?php
/**
 * @author Alexey Samoylov <alexey.samoylov@gmail.com>
 *
 * Simply attach this behavior to your model, specify attribute and file path.
 * You can use placeholders in path configuration:
 *
 * [[app_root]] - application root
 * [[web_root]] - web root
 * [[model]] - model name
 * [[pk]] - model Pk
 * [[id]] - the same as [[pk]]
 * [[attribute_name]] - model attribute (may be id or other model attribute), for example [[attribute_name]]
 * [[id_path]] - id subdirectories structure
 * [[basename]] - original filename with extension
 * [[filename]] - original filename without extension
 * [[extension]] - original extension
 * [[base_url]] - site base url
 * [[profile]] - thumbnail profile name
 *
 * public
 * function behaviors()
 * {
 *     return [
 *         'image-upload' => [
 *              'class' => '\yiidreamteam\upload\ImageUploadBehavior',
 *              'attribute' => 'imageUpload',
 *              'thumbs' => [
 *                  'thumb' => ['width' => 400, 'height' => 300],
 *              ],
 *              'filePath' => '[[web_root]]/images/[[pk]].[[extension]]',
 *              'fileUrl' => '/images/[[pk]].[[extension]]',
 *              'thumbPath' => '[[web_root]]/images/[[profile]]_[[pk]].[[extension]]',
 *              'thumbUrl' => '/images/[[profile]]_[[pk]].[[extension]]',
 *         ],
 *     ];
 * }
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
    public $thumbPath = '@web/images/[[profile]]_[[pk]].[[extension]]';
    /** @var string Url template for thumbnails. */
    public $thumbUrl = '/images/[[profile]]_[[pk]].[[extension]]';

    public $filePath = '@web/images/[[pk]].[[extension]]';
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
     * Resolves profile path for thumbnail profile.
     *
     * @param string $path
     * @param string $profile
     * @return string
     */
    public function resolveProfilePath($path, $profile)
    {
        $path = $this->resolvePath($path);
        $path = str_replace('[[profile]]', $profile, $path);
        return $path;
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
     *
     * @param string $attribute
     * @param string $emptyUrl
     * @return string
     */
    public function getImageFileUrl($attribute, $emptyUrl = null)
    {
        return $this->getUploadedFileUrl($attribute, $emptyUrl);
    }

    /**
     * @param string $attribute
     * @param string $profile
     * @param string $emptyUrl
     * @return string
     */
    public function getThumbFileUrl($attribute, $profile = 'thumb', $emptyUrl = null)
    {
        if (!$this->owner->{$attribute})
            return $emptyUrl;

        $behavior = static::getInstance($this->owner, $attribute);
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
    public function createThumbs()
    {
        $path = $this->getUploadedFilePath($this->attribute);
        foreach ($this->thumbs as $profile => $config) {
            $thumbPath = static::getThumbFilePath($this->attribute, $profile);
            if (!is_file($thumbPath)) {
                /** @var GD $thumb */
                $thumb = new GD($path);
                $thumb->adaptiveResize($config['width'], $config['height']);
                FileHelper::createDirectory($thumbPath, 0775, true);
                $thumb->save($thumbPath);
            }
        }
    }
}
