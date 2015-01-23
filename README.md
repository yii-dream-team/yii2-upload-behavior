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
            [
                'class' => '\yiidreamteam\upload\FileUploadBehavior',
                'attribute' => 'fileUpload',
                'filePath' => '@webroot/uploads/[[pk]].[[extension]]',
                'fileUrl' => '/uploads/[[pk]].[[extension]]',
            ],
        ];
    }
   
You can use Yii path [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html) 
like `@app`, `@webroot`, `@web` in your path template configuration.

You can also use additional placeholders in path templates.
Placeholder `[[foobar]]` will be replaced with appropriate placeholder value. 

Possible path/url placeholders:

 * `[[model]]` - model class name
 * `[[pk]]` - value of the primary key 
 * `[[id]]` - the same as `[[pk]]`
 * `[[attribute_name]]` - attribute value, for example `[[attribute_ownerId]]`
 * `[[id_path]]` - id subdirectories structure (if model primary key is `12345`, placeholder value will be `1/2/3/4/5/0/0/0/0/0`
 * `[[basename]]` - original filename with extension
 * `[[filename]]` - original filename without extension
 * `[[extension]]` - original extension
    
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
            [
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

You can use Yii path [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html) 
like `@app`, `@webroot`, `@web` in your path template configuration.

You can also use additional placeholders in path templates.
Placeholder `[[foobar]]` will be replaced with appropriate placeholder value. 

Possible path/url placeholders:

 * `[[model]]` - model class name
 * `[[pk]]` - value of the primary key 
 * `[[id]]` - the same as `[[pk]]`
 * `[[attribute_name]]` - attribute value, for example `[[attribute_ownerId]]`
 * `[[id_path]]` - id subdirectories structure (if model primary key is `12345`, placeholder value will be `1/2/3/4/5/0/0/0/0/0`
 * `[[basename]]` - original filename with extension
 * `[[filename]]` - original filename without extension
 * `[[extension]]` - original extension
 * `[[profile]]` - thumbnail profile name, use it in thumbnail path/url
    
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
