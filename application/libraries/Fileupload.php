<?php defined('BASEPATH') or exit('No direct script access allowed');

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class Fileupload {

    private $EndReturnData;

    function __construct() {
        
    }

    public function fileUpload($Type, $Path, $FileOrContent) {

        $this->EndReturnData = new stdClass();

        $bucket = getenv('AWS_BUCKET_NAME');

        if(getenv('FILE_UPLOAD') == 'amazonaws') {

            // AWS Credentials
            $s3 = new S3Client([
                'version'     => 'latest',
                'region'      => 'ap-south-1',
                'credentials' => [
                    'key'    => getenv('AWS_KEY'),
                    'secret' => getenv('AWS_SECRET'),
                ]
            ]);

        } else if(getenv('FILE_UPLOAD') == 'cloudflare') {

            // Cloudflare R2 Storage
            $s3 = new S3Client([
                'version'     => 'latest',
                'region'      => 'auto',
                'endpoint'      => getenv('CFLARE_ENDPOINT'),
                'credentials' => [
                    'key'    => getenv('CFLARE_ACC_KEY'),
                    'secret' => getenv('CFLARE_SECRET'),
                ]
            ]);

        }

        $mime_type = mime_content_type($FileOrContent);

        try {
            
            $DefPath = 'user_uploads/' . $Path;

            if($Type == 'file') {
                $result = $s3->putObject([
                    'Bucket'      => $bucket,
                    'Key'         => $DefPath,
                    'SourceFile'  => $FileOrContent,
                    'ContentType' => $mime_type,
                    'ACL'         => 'public-read',
                ]);
            } else if($Type == 'data') {
                $result = $s3->putObject([
                    'Bucket'      => $bucket,
                    'Key'         => $DefPath,
                    'Body'        => $FileOrContent,
                    'ContentType' => $mime_type,
                    'ACL'         => 'public-read',
                ]);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->FullPath = $result['ObjectURL'];
            $this->EndReturnData->Path = $DefPath;
            $this->EndReturnData->Message = "Success";
            
        } catch (AwsException $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;
            
    }

}