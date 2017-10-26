<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 */

namespace yiidreamteam\upload\exceptions;

use Exception;
use yii\helpers\ArrayHelper;

class FileUploadException extends \Exception
{
    public $errorCode;

    public $errors = [
        UPLOAD_ERR_OK => 'There is no error, the file uploaded with success.',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
    ];

    public function __construct(
        $errorCode,
        $defaultMessage = 'Unknown error occurred.',
        $code = 0,
        Exception $previous = null
    ) {
        $this->errorCode = $errorCode;

        parent::__construct($this->prepareMessage($errorCode, $defaultMessage), $code, $previous);
    }


    protected function prepareMessage($code, $defaultMessage) {
        return ArrayHelper::getValue($this->errors, $code, $defaultMessage) . ' Error code is ' . $code;
    }
}