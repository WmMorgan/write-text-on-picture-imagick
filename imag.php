<?php

use App\Models\Sign;

require_once __DIR__.'/vendor/autoload.php';
$db = new Sign();
$user = $db->db->queryRow('SELECT * FROM tokens WHERE id=:id', array(':id' => 1));


print $user;

/*$src1 = new Imagick(__DIR__."/it.jpg");
$src2 = new Imagick(__DIR__."/transparent.png");

$src1->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
//$src1->setImageArtifact('compose:args', "1,0,-0.5,0.5");
$src1->compositeImage($src2, Imagick::COMPOSITE_MATHEMATICS, 0, 0);
$src1->writeImage(__DIR__."/outputt.jpg");*/

/*use App\PhotoImagick;

require_once 'src/PhotoImagick.php';
$img = new PhotoImagick();
$text = "qwerty ssdcsdc";

$ok=$img->thumbnail(__DIR__.'/uploads/565954173f6f6e35.jpg', 'mamuxs.jpg', $text);
echo json_encode($ok);*/