<?php
namespace App;



class Photo
{
    /**
     * @param array $data
     * @return bool
     * @throws PhotoException
     */
public function imagick(array $data): bool
{
    if (empty($data['username'])) {
        throw new PhotoException('The Username should not be empty');
    }
    if (empty($data['filename'])) {
        throw new PhotoException('You did not select a photo');
    }
    return true;
    }

    /**
     * @param $filename
     * @param $text
     * @return PhotoImagick
     */
    public function editSave($filename, $text) {
    $image = new PhotoImagick();
    $image->thumbnail($_SERVER['DOCUMENT_ROOT'] . '/uploads/'.$filename, $filename, $text);
return $image;
    }

}
