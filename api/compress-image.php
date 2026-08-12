<?php
function compressImage($source, $destination, $quality = 70) {
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagepng($image, $destination, 7);
    } elseif ($info['mime'] == 'image/webp') {
        $image = imagecreatefromwebp($source);
        imagewebp($image, $destination, $quality);
    }
    imagedestroy($image);
    return true;
}

// Compress all images in uploads folder
$uploadDir = '../uploads/';
$files = scandir($uploadDir);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $filePath = $uploadDir . $file;
        if (is_file($filePath) && filesize($filePath) > 200 * 1024) { // > 200KB
            $tempPath = $uploadDir . 'temp_' . $file;
            compressImage($filePath, $tempPath, 65);
            rename($tempPath, $filePath);
            echo "Compressed: $file<br>";
        }
    }
}
echo "Done!";
?>