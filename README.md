# Yii2 file/image upload behavior for ActiveRecord #
 
This package is the set of two similar behaviors. The first one allows you to keep the uploaded file as-is.
 And the second one allows you to generate set of thumbnails for the uploaded image. Behaviors could be attached
 multiple times for different attributes.
 
## Installation ##

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

    php composer.phar require --prefer-dist yii-dream-team/yii2-upload-behavior "*"

or add

    "yii-dream-team/yii2-upload-behavior": "*"

to the `require` section of your composer.json.
 
## FileUploadBehavior ##

This behavior allow you to add file uploading logic with ActiveRecord behavior.

### Usage ###
Attach the behavior to your model class:

    public function behaviors()
    {
        return [
            'file-upload' => [
                'class' => '\yiidreamteam\upload\FileUploadBehavior',
                'attribute' => 'fileUpload',
                'filePath' => '@webroot/uploads/[[pk]].[[extension]]',
                'fileUrl' => '/uploads/[[pk]].[[extension]]',
            ],
        ];
    }

Possible path/url placeholders:

 * [[app_root]] - application root
 * [[web_root]] - web root
 * [[model]] - model name
 * [[pk]] - model Pk
 * [[id]] - the same as [[pk]]
 * [[attribute_name]] - model attribute (may be id or other model attribute), for example [[attribute_systemName]]
 * [[id_path]] - id subdirectories structure
 * [[parent_id]] - parent object primary key value
 * [[basename]] - original filename with extension
 * [[filename]] - original filename without extension
 * [[extension]] - original extension
 * [[base_url]] - site base url
 
 You can also use Yii path [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html) like `@app`, `@webroot`, `@web` in your path template configuration.
    
Add validation rule:

    public function rules()
    {
        return [
            ['fileUpload', 'file'],   
        ];
    }

Setup proper form enctype:

    $form = \yii\bootstrap\ActiveForm::begin([
        'enableClientValidation' => false,
        'options' => [
            'enctype' => 'multipart/form-data',
        ],
    ]);

File should be uploading fine.

You can get uploaded file url using model call:

    echo $model->getUploadedFileUrl('fileUpload');

## ImageUploadBehavior ##

Image upload behavior extends file upload behavior with image thumbnails generation.
You can configure set of different thumbnail profiles to generate.

### Usage ###
Attach the behavior to your model class:

    public function behaviors()
    {
        return [
            'image-upload' => [
                 'class' => '\yiidreamteam\upload\ImageUploadBehavior',
                 'attribute' => 'imageUpload',
                 'thumbs' => [
                     'thumb' => ['width' => 400, 'height' => 300],
                 ],
                 'filePath' => '@webroot/images/[[pk]].[[extension]]',
                 'fileUrl' => '/images/[[pk]].[[extension]]',
                 'thumbPath' => '@webroot/images/[[profile]]_[[pk]].[[extension]]',
                 'thumbUrl' => '/images/[[profile]]_[[pk]].[[extension]]',
            ],
        ];
    }

Possible path/url placeholders:

 * [[app_root]] - application root
 * [[web_root]] - web root
 * [[model]] - model name
 * [[pk]] - model Pk
 * [[id]] - the same as [[pk]]
 * [[attribute_name]] - model attribute (may be id or other model attribute), for example [[attribute_systemName]]
 * [[id_path]] - id subdirectories structure
 * [[basename]] - original filename with extension
 * [[filename]] - original filename without extension
 * [[extension]] - original extension
 * [[base_url]] - site base url
 * [[profile]] - thumbnail profile name
 
 You can also use Yii path [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html) like `@app`, `@webroot`, `@web` in your path template configuration.
    
Add validation rule:

    public function rules()
    {
        return [
            ['imageUpload', 'file', 'extensions' => 'jpeg, gif, png'],   
        ];
    }

Setup proper form enctype:

    $form = \yii\bootstrap\ActiveForm::begin([
        'enableClientValidation' => false,
        'options' => [
            'enctype' => 'multipart/form-data',
        ],
    ]);

File should be uploading fine.

You can get uploaded image url using model call:

    echo $model->getImageFileUrl('imageUpload');

You can specify default image for models without uploaded image:

    echo $model->getImageFileUrl('imageUpload', '/images/empty.jpg');

You can also get generated thumbnail image url:

    echo $model->getThumbFileUrl('imageUpload', 'thumb');

You can specify default thumbnail image for models without uploaded image:
  
    echo $model->getThumbFileUrl('imageUpload', 'thumb', '/images/thumb_empty.jpg');

## Licence ##

MIT
    
## Links ##

* [Official site](http://yiidreamteam.com/yii2/upload-behavior)
* [Source code on GitHub](https://github.com/yii-dream-team/yii2-upload-behavior)
* [Composer package on Packagist](https://packagist.org/packages/yii-dream-team/yii2-upload-behavior)
