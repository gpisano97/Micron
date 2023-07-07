<?php

namespace core\Media;

use core\Response;
use Exception;

/**
 * Provide usefull methods for file upload management.
 * The uploaded file are saved in a folder tree structure, e.g. ID : 10 will be => {MEDIA_BASE_PATH}/1/0/10.extension
 * You can also decide to save the file with his original name, e.g. ID : 10, filename : "Test.txt" will be -> {MEDIA_BASE_PATH}/1/0/Test.txt
 * You can upload how much file do you want if you specific the filname, if you want to use the id you will store 1 file at once.
 * 
 * N.B. if the paths do not exist they will be created
 */
class FilesManager
{

    private function splitIdInPathLike(string $id)
    {
        $path = "";
        for ($i = 0; $i < strlen($id); $i++) {
            $path .= $id[$i] . "/";
        }

        return $path;
    }

    /**
     * Finalize the file uploaded in the media folder.
     *
     * @param int $fileId the file identificator, this will decide also the folder structure where the file will be placed
     * @param array $uploadedFile the file to upload, you can find it in the Request class object if you are managing via Micron (preferred and simplier method) or in $_FILES array.
     * @param string $targetFolder = "" an additional path to MEDIA_BASE_PATH
     * @param array $notAllowedFileExtension = ["js" ,"exe", "msi"] files with those extensions will be ignored.
     * @param bool $replaceIfPresent = false if true will replace the existent file.
     * @param bool $useFilename = false  if true, will be used the original filename of the uploaded file.
     * 
     * @throws Exception Throw an exception if exist a file with the given ID or filename.
     * 
     * @return bool true if success, false otherwise
     * 
     */
    public function Upload(int $fileId, array $uploadedFile, string $targetFolder = "", array $notAllowedFileExtension = ["php", "js", "exe", "msi"], bool $replaceIfPresent = false, bool $useFilename = false): bool
    {

        if ($uploadedFile["error"] !== UPLOAD_ERR_OK) {
            return false;
        }

        $extension = pathinfo($uploadedFile["name"], PATHINFO_EXTENSION);

        if (in_array($extension, $notAllowedFileExtension)) {
            return false;
        }

        $tempName = $uploadedFile["tmp_name"];
        $path = MEDIA_BASE_PATH . $targetFolder;

        $fileId = (string) $fileId;

        $path .= "/" . $this->splitIdInPathLike($fileId);

        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }

        $fileIsPresent = $this->IsPresent((int) $fileId, $targetFolder, ($useFilename ? $uploadedFile["name"] : ""));

        if($fileIsPresent && !$replaceIfPresent){
            throw new Exception("File with id: $fileId found! Use Replace function instead Upload to replace it or use another file id.", 400);
        }
        else if($fileIsPresent && $replaceIfPresent){
            return $this->Replace((int) $fileId, $uploadedFile, $targetFolder, false, ($useFilename ? $uploadedFile["name"] : ""));
        }

        if ($useFilename) {
            $path .= $uploadedFile["name"];
        } else {
            $path .= "{$fileId}" . ".{$extension}";
        }


        if (move_uploaded_file($tempName, $path)) {
            return true;
        }
        return false;
    }

    /**
     * Delete a file
     *
     * @param int $fileId file identificator
     * @param string $targetFolder = "" an additional path to MEDIA_BASE_PATH
     * @param string $fileName = "" if different by "", will be searched for a file with this filename. The fileName must containe the extension
     * 
     * @throws Exception throw an exception if the file is not found.
     * 
     * @return bool
     * 
     */
    public function Delete(int $fileId, string $targetFolder = "", string $fileName = ""): bool
    {
        $fileId = (string) $fileId;

        $path = MEDIA_BASE_PATH . $targetFolder;

        $path .= "/" . $this->splitIdInPathLike($fileId);

        $scan = array_values(array_filter(scandir($path), function ($item) use ($path) {
            return !is_dir($path . $item);
        }));

        if (count($scan) === 0) {
            throw new Exception("File not found.", 404);
        }

        $filename = $path;

        if ($fileName !== "") {
            $fileFound = false;
            foreach ($scan as $file) {
                if ($file === $fileName) {
                    $fileFound = true;
                }
            }
            if (!$fileFound) {
                throw new Exception("File not found.", 404);
            }
            $filename .= $fileName;
        } else {
            $filename .= $scan[0];
        }

        if (unlink($filename)) {
            return true;
        }

        return false;
    }

    /**
     * Replace a file
     *
     * @param int $fileId file identificator
     * @param array $newFile the file to upload, you can find it in the Request class object if you are managing via Micron (preferred and simplier method) or in $_FILES array.
     * @param string $targetFolder = "" an additional path to MEDIA_BASE_PATH
     * @param bool $useFilename = false  if true, will be used the original filename of the uploaded file.
     * @param string $fileNameToReplace = "" if different by "", will be searched for a file with this filename. The fileName must containe the extension
     * 
     * @return bool
     * 
     */
    public function Replace(int $fileId, array $newFile, string $targetFolder = "", bool $useFilename = false, string $fileNameToReplace = ""): bool
    {
        $fileId = (string) $fileId;

        $path = MEDIA_BASE_PATH . $targetFolder;

        $path .= "/" . $this->splitIdInPathLike($fileId);

        $extensionOfFileToReplace = "";
        $isPresentFileToReplace = $this->IsPresent($fileId, $targetFolder, $fileNameToReplace, $extensionOfFileToReplace);

        if (!$isPresentFileToReplace) {
            throw new Exception("File not found.", 404);
        }

        $filename = $path;

        if($fileNameToReplace !== ""){
            $filename .= $fileNameToReplace;
        }
        else{
            $filename .= $fileId.".{$extensionOfFileToReplace}";
        }
         
        if (!unlink($filename)) {
            return false;
        }

        $extension = pathinfo($newFile["name"], PATHINFO_EXTENSION);

        $tempName = $newFile["tmp_name"];

        if ($useFilename) {
            $path .= $newFile["name"];
        } else {
            $path .= "{$fileId}" . ".{$extension}";
        }

        $path .= "{$fileId}" . ".{$extension}";

        if (move_uploaded_file($tempName, $path)) {
            return true;
        }
        return false;
    }

    /**
     * Check if a file is present in the media folder.
     *
     * @param int $fileId file identificator
     * @param string $targetFolder = "" an additional path to MEDIA_BASE_PATH
     * @param string $fileName = "" if different by "", will be searched for a file with this filename. The fileName must containe the extension
     * @param string|null $extensionOfFoundFile = null this argument is passed by reference, the variable will be filled with file extension.
     * 
     * @return bool
     * 
     */
    public function IsPresent(int $fileId, string $targetFolder = "", $fileName = "", string &$extensionOfFoundFile = null) : bool {
        $path = MEDIA_BASE_PATH . $targetFolder;

        $fileId = (string) $fileId;

        $path .= "/" . $this->splitIdInPathLike($fileId);

        $scan = array_values(array_filter(scandir($path), function ($item) use ($path) {
            return !is_dir($path . $item);
        }));

        $fileFound = false;
        
        if (count($scan) > 0) {
            foreach ($scan as $file) {
                $f = pathinfo($file, PATHINFO_FILENAME);
                if(($fileName !== "" && $file === $fileName) || ($fileName === "" && pathinfo($file, PATHINFO_FILENAME) === $fileId)){
                    $fileFound = true;

                    if($extensionOfFoundFile !== null){
                        $extensionOfFoundFile = pathinfo($file, PATHINFO_EXTENSION);
                    }
                }
            }
        }

        return $fileFound;
    }

    /**
     * Download a file from the server. This function set the correct HTTP Header to force the client to start the download.
     *
     * @param int $fileId file identificator
     * @param string $targetFolder = "" an additional path to MEDIA_BASE_PATH
     * @param string $fileName = "" if different by "", will be searched for a file with this filename. The fileName must containe the extension
     * @param string $downloadName = "" if different by "" it will be used as filename for the download.
     * 
     * @return [type]
     * 
     */
    public function Download(int $fileId, string $targetFolder = "", string $fileName = "", string $downloadName = ""){

        $path = MEDIA_BASE_PATH . $targetFolder;
        $fileId = (string) $fileId;
        $path .= "/" . $this->splitIdInPathLike($fileId);

        $extensionOfFoundFile = "";
        $fileIsPresent = $this->IsPresent((int) $fileId, $targetFolder, $fileName, $extensionOfFoundFile);

        if(!$fileIsPresent){
            throw new Exception("File not found.", 404);
        }

        if($fileName !== ""){
            $path .= $fileName;
        }
        else{
            $path .= $fileId.".{$extensionOfFoundFile}";
        }

        Response::instance()->provideFile($path, true, $downloadName);
    }
}