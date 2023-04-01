<?php

namespace core\Media;
class Files
{
    private string $mimeType = "";
    private string $fileName = "";
    private string $extension = "";
    private string $allowedExtensions = "";

    public function __construct(string $mimeType = "", string $extension = "",string $allowedExtensions = ""){
        $this->mimeType = $mimeType;
        $this->extension = $extension;
        $this->allowedExtensions = $allowedExtensions;
    }


}
