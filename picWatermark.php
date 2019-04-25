<?php
/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 2019-04-18
 * Time: 15:08
 */


class scanDir {
    static private $directories, $files, $ext_filter, $recursive;

// ----------------------------------------------------------------------------------------------
    // scan(dirpath::string|array, extensions::string|array, recursive::true|false)
    static public function scan(){
        // Initialize defaults
        self::$recursive = false;
        self::$directories = array();
        self::$files = array();
        self::$ext_filter = false;

        // Check we have minimum parameters
        if(!$args = func_get_args()){
            die("Must provide a path string or array of path strings");
        }
        if(gettype($args[0]) != "string" && gettype($args[0]) != "array"){
            die("Must provide a path string or array of path strings");
        }

        // Check if recursive scan | default action: no sub-directories
        if(isset($args[2]) && $args[2] == true){self::$recursive = true;}

        // Was a filter on file extensions included? | default action: return all file types
        if(isset($args[1])){
            if(gettype($args[1]) == "array"){self::$ext_filter = array_map('strtolower', $args[1]);}
            else
                if(gettype($args[1]) == "string"){self::$ext_filter[] = strtolower($args[1]);}
        }

        // Grab path(s)
        self::verifyPaths($args[0]);
        return self::$files;
    }

    static private function verifyPaths($paths){
        $path_errors = array();
        if(gettype($paths) == "string"){$paths = array($paths);}

        foreach($paths as $path){
            if(is_dir($path)){
                self::$directories[] = $path;
                $dirContents = self::find_contents($path);
            } else {
                $path_errors[] = $path;
            }
        }

        if($path_errors){echo "The following directories do not exists<br />";die(var_dump($path_errors));}
    }

    // This is how we scan directories
    static private function find_contents($dir){
        $result = array();
        $root = scandir($dir);
        foreach($root as $value){
            if($value === '.' || $value === '..') {continue;}
            if(is_file($dir.DIRECTORY_SEPARATOR.$value)){
                if(!self::$ext_filter || in_array(strtolower(pathinfo($dir.DIRECTORY_SEPARATOR.$value, PATHINFO_EXTENSION)), self::$ext_filter)){
                    self::$files[] = $result[] = $dir.DIRECTORY_SEPARATOR.$value;
                }
                continue;
            }
            if(self::$recursive){
                foreach(self::find_contents($dir.DIRECTORY_SEPARATOR.$value) as $value) {
                    self::$files[] = $result[] = $value;
                }
            }
        }
        // Return required for recursive search
        return $result;
    }
}


function getImageSizeKeepAspectRatio( $imageUrl, $maxWidth, $maxHeight)
{
    $imageDimensions = getimagesize($imageUrl);
    $imageWidth = $imageDimensions[0];
    $imageHeight = $imageDimensions[1];
    $imageSize['width'] = $imageWidth;
    $imageSize['height'] = $imageHeight;
    if($imageWidth > $maxWidth || $imageHeight > $maxHeight)
    {
        if ( $imageWidth > $imageHeight ) {
            $imageSize['height'] = floor(($imageHeight/$imageWidth)*$maxWidth);
            $imageSize['width']  = $maxWidth;
        } else {
            $imageSize['width']  = floor(($imageWidth/$imageHeight)*$maxHeight);
            $imageSize['height'] = $maxHeight;
        }
    }
    return $imageSize;
}


function loadPic($source_url) {
    $source_url_parts = pathinfo($source_url);
    $extension = $source_url_parts['extension'];

    $img = null;
    if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'JPG' || $extension == 'JPEG') {
        //then return the image as a jpeg image for the next step
        $img = imagecreatefromjpeg($source_url);
    } elseif ($extension == 'png' || $extension == 'PNG') {
        //then return the image as a png image for the next step
        $img = imagecreatefrompng($source_url);
    } else {
        //show an error message if the file extension is not available
        echo 'image extension is not supporting';
    }
    return $img;
}

function workPic($source_url, $after_width, $after_height, $moveTo, $watermarkURL) {
    $source_url_parts = pathinfo($source_url);
    $filename = uniqid();
    $extension = $source_url_parts['extension'];

    $img = loadPic($source_url);
    $watermark = loadPic($watermarkURL);

    if ($img != null && $watermark != null) {

        $imgResized = imagescale($img, $after_width, $after_height);
        imagepng($imgResized, $moveTo.'/'.$filename . '.'.$extension);

        $marge_right = 10;
        $marge_bottom = 10;
        $sx = imagesx($watermark);
        $sy = imagesy($watermark);

        imagecopy($imgResized, $watermark, imagesx($imgResized) - $sx - $marge_right, imagesy($imgResized) - $sy - $marge_bottom, 0, 0, imagesx($watermark), imagesy($watermark));
        imagepng($imgResized, $moveTo.'/'.$filename . '.'.$extension);

        imagedestroy($img);
        imagedestroy($imgResized);
        unlink($source_url);
    }
}

while(1) {

    $file_ext = array(
        "jpg",
        "bmp",
        "png"
    );

    $dir           = '/Users/easyproger/Desktop';
    $waterMarksDir = '/box/picProject/watermarks';
    $moveTo        = '/Library/WebServer/Documents/picsendserver/resultPics';
    $picSizeMaxW   = 1920;
    $picSizeMaxH   = 1080;

    $files         = scanDir::scan($dir, $file_ext);
    if (count($files)) {

        $watermarks     = scanDir::scan($waterMarksDir, $file_ext);
        $indexWaterMark = rand ( 0 , count($watermarks) - 1);

        for ($i = 0; $i < count($files); $i++) {
            $image = $files[$i];
            $resizedImage = getImageSizeKeepAspectRatio($image, $picSizeMaxW, $picSizeMaxH);
            workPic($image, $resizedImage['width'], $resizedImage['height'], $moveTo, $watermarks[$indexWaterMark]);
        }
    }

    sleep(1);
}
