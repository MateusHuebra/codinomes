<?php

namespace App\Services\Game\Aux;

use CURLFile;

class Images {

    public $masterImage;
    public $agentsImage;
    public $masterCURLImage;
    public $agentsCURLImage;
    public $masterTempImageFileName;
    public $agentsTempImageFileName;

    public function makeCURLFileFromImage($image, string $fileName) {
        $this->{$fileName.'TempImageFileName'} = tempnam(sys_get_temp_dir(), $fileName.'_image_');

        imagepng($image, $this->{$fileName.'TempImageFileName'});
        imagedestroy($image);
        $this->{$fileName.'CURLImage'} = new CURLFile($this->{$fileName.'TempImageFileName'},'image/png', $fileName);
    }

    public function makeCURLFilesFromImages() {
        if(isset($this->masterImage)) {
            $this->makeCURLFileFromImage($this->masterImage, 'master');
        }
        if(isset($this->agentsImage)) {
            $this->makeCURLFileFromImage($this->agentsImage, 'agents');
        }
    }

}