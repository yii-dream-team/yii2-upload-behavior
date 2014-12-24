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
 * [[attribute]] - value of $this->attribute
 * [[attribute_name]] - model attribute (may be id or other model attribute), for example [[attribute_name]]
 * [[id_path]] - id subdirectories structure
 * [[parent_id]] - parent object primary key value
 * [[basename]] - original filename with extension
 * [[filename]] - original filename without extension
 * [[extension]] - original extension
 * [[base_url]] - site base url
 *
 * Usage example:
 *
 * public
 * function behaviors()
 * {
 *     return [
 *         'file-upload' => [
 *             'class' => '\yiidreamteam\upload\FileUploadBehavior',
 *             'attribute' => 'fileUpload',
 *             'filePath' => '[[web_root]]/uploads/[[pk]].[[extension]]',
 *             'fileUrl' => '/uploads/[[pk]].[[extension]]',
 *         ],
 *     ];
 * }
 */
namespace yiidreamteam\upload;

use Yii;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;
use yii\web\UploadedFile;

/**
 * Class FileUploadBehavior
 *
 * @property ActiveRecord $owner
 */
class FileUploadBehavior extends \yii\base\Behavior
{
    const EVENT_AFTER_FILE_SAVE = 'afterFileSave';

    /** @var string Name of attribute which holds the attachment. */
    public $attribute = 'upload';
    /** @var string Path template to use in storing files.5 */
    public $filePath = '@web/uploads/[[pk]].[[extension]]';
    /** @var string Where to store images. */
    public $fileUrl = '/uploads/[[pk]].[[extension]]';
    /** @var string Attribute used to link owner model with it's parent */
    public $parentRelationAttribute;
    /** @var \yii\web\UploadedFile */
    protected $file;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * Before validate event.
     */
    public function beforeValidate()
    {
        if ($this->owner->{$this->attribute} instanceof UploadedFile) {
            $this->file = $this->owner->{$this->attribute};
            return;
        }
        $this->file = UploadedFile::getInstance($this->owner, $this->attribute);
        
        if (empty($this->file)) {
            $this->file = UploadedFile::getInstanceByName($this->attribute);
        }

        if ($this->file instanceof UploadedFile) {
            $this->owner->{$this->attribute} = $this->file;
        }
    }

    /**
     * Before save event.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeSave()
    {
        if ($this->file instanceof UploadedFile) {
            if (!$this->owner->isNewRecord) {
                /** @var static $oldModel */
                $oldModel = $this->owner->findOne($this->owner->primaryKey);
                $oldModel->cleanFiles();
            }
            $this->owner->{$this->attribute} = $this->file->baseName . '.' . $this->file->extension;
        }
    }

    /**
     * Removes files associated with attribute
     */
    public function cleanFiles()
    {
        $path = $this->resolvePath($this->filePath);
        @unlink($path);
    }

    /**
     * Replaces all placeholders in path variable with corresponding values
     *
     * @param string $path
     * @return string
     */
    public function resolvePath($path)
    {
        $path = Yii::getAlias($path);
        $path = str_replace('[[app_root]]', Yii::getAlias('@app'), $path);
        $path = str_replace('[[web_root]]', Yii::getAlias('@webroot'), $path);
        $path = str_replace('[[base_url]]', Yii::getAlias('@web'), $path);

        $r = new \ReflectionClass($this->owner->className());
        $path = str_replace('[[model]]', lcfirst($r->getShortName()), $path);

        $path = str_replace('[[attribute]]', lcfirst($this->attribute), $path);
        $pk = implode('_', $this->owner->getPrimaryKey(true));
        $path = str_replace('[[pk]]', lcfirst($pk), $path);
        $path = str_replace('[[id]]', lcfirst($pk), $path);
        foreach ($this->owner->attributes() as $attribute) {
            $path = str_replace("[[attribute_{$attribute}]]", $this->owner->{$attribute}, $path);
        }
        $path = str_replace('[[id_path]]', static::makeIdPath($this->owner->primaryKey), $path);

        if (isset($this->parentRelationAttribute))
            $path = str_replace('[[parent_id]]', $this->owner->{$this->parentRelationAttribute}, $path);

        $pi = pathinfo($this->owner->{$this->attribute});
        $fileName = ArrayHelper::getValue($pi, 'filename');
        $extension = strtolower(ArrayHelper::getValue($pi, 'extension'));

        $path = str_replace('[[extension]]', $extension, $path);
        $path = str_replace('[[filename]]', $fileName, $path);
        $path = str_replace('[[basename]]', $fileName . '.' . $extension, $path);
        
        return $path;
    }

    /**
     * @param integer $id
     * @return string
     */
    protected static function makeIdPath($id)
    {
        $id = is_array($id) ? implode('', $id) : $id;
        $length = 10;
        $id = str_pad($id, $length, '0', STR_PAD_RIGHT);

        $result = [];
        for ($i = 0; $i < $length; $i++)
            $result[] = substr($id, $i, 1);

        return implode('/', $result);
    }

    /**
     * After save event.
     */
    public function afterSave()
    {
        if ($this->file instanceof UploadedFile) {
            $path = $this->getUploadedFilePath($this->attribute);
            FileHelper::createDirectory(pathinfo($path, PATHINFO_DIRNAME), 0775, true);
            if (!$this->file->saveAs($path)) {
                throw new Exception('File saving error.');
            }
            $this->owner->trigger(static::EVENT_AFTER_FILE_SAVE);
        }
    }

    /**
     * Returns file path for attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function getUploadedFilePath($attribute)
    {
        $behavior = static::getInstance($this->owner, $attribute);
        return $behavior->resolvePath($behavior->filePath);
    }

    /**
     * Returns behavior instance for specified class and attribute
     *
     * @param ActiveRecord $model
     * @param string $attribute
     * @return static
     */
    public static function getInstance(ActiveRecord $model, $attribute)
    {
        foreach ($model->behaviors as $behavior) {
            if ($behavior instanceof static && $behavior->attribute == $attribute)
                return $behavior;
        }

        throw new InvalidCallException('Missing behavior for attribute ' . VarDumper::dumpAsString($attribute));
    }

    /**
     * Before delete event.
     */
    public function beforeDelete()
    {
        $this->cleanFiles();
    }

    /**
     * Returns file url for the attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function getUploadedFileUrl($attribute)
    {
        $behavior = static::getInstance($this->owner, $attribute);
        return $behavior->resolvePath($behavior->fileUrl);
    }
}
