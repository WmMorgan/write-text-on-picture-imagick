<?php


namespace App;
use Imagick;
use ImagickDraw;

class PhotoImagick
{

   public function thumbnail($image, $fileName, $textAn, $new_w = 1000, $new_h = 1000, $focus = 'center')
    {
        $image = new Imagick($image);
        $transparent = new Imagick($_SERVER['DOCUMENT_ROOT'] . "/transparent.png");

        $w = $image->getImageWidth();
        $h = $image->getImageHeight();

        if ($w > $h) {
            $resize_w = $w * $new_h / $h;
            $resize_h = $new_h;
        } else {
            $resize_w = $new_w;
            $resize_h = $h * $new_w / $w;
        }
        $image->resizeImage($resize_w, $resize_h, Imagick::FILTER_LANCZOS, 0.9);

        switch ($focus) {
            case 'northwest':
                $image->cropImage($new_w, $new_h, 0, 0);
                break;

            case 'center':
                $image->cropImage($new_w, $new_h, ($resize_w - $new_w) / 2, ($resize_h - $new_h) / 2);
                break;

            case 'northeast':
                $image->cropImage($new_w, $new_h, $resize_w - $new_w, 0);
                break;

            case 'southwest':
                $image->cropImage($new_w, $new_h, 0, $resize_h - $new_h);
                break;

            case 'southeast':
                $image->cropImage($new_w, $new_h, $resize_w - $new_w, $resize_h - $new_h);
                break;
        }
        $image->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
        $image->compositeImage($transparent, Imagick::COMPOSITE_MATHEMATICS, 0, 0);

        $draw = new ImagickDraw();
        $draw->setFillColor('white');
        /* Настройки шрифта */
        $draw->setFont('text.otf');
        $draw->setFontSize(70);
        $draw->setGravity(Imagick::GRAVITY_CENTER);
        $text = $textAn;
        /* Создаём текст */
        list($lines, $lineHeight) = $this->wordWrapAnnotation($image, $draw, $text, $w - 20);
        $image->annotateImage($draw, 10, $lineHeight, 0, $lines);
        return $image->writeImage($_SERVER['DOCUMENT_ROOT'] . '/uploads/'.$fileName);
    }

//this is unicode split method for out of english latin characters
  public function str_split_unicode($str, $l = 0)
    {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

//this is my function detects long words and split them

   public function check_long_words($image, $draw, $text, $maxWidth)
    {
        $metrics = $image->queryFontMetrics($draw, $text);
        if ($metrics['textWidth'] <= $maxWidth)
            return array($text);

        $words = str_split_unicode($text);


        $i = 0;

        while ($i < count($words)) {
            $currentLine = $words[$i];
            if ($i + 1 >= count($words)) {

                $lines[] = $currentLine;
                //$lines = $lines + $checked;
                break;
            }
            //Check to see if we can add another word to this line
            $metrics = $image->queryFontMetrics($draw, $currentLine . $words[$i + 1]);

            while ($metrics['textWidth'] <= $maxWidth) {
                //If so, do it and keep doing it!
                $currentLine .= $words[++$i];
                if ($i + 1 >= count($words))
                    break;
                $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i + 1]);
                $t++;
            }
            //We can't add the next word to this line, so loop to the next line


            $lines[] = $currentLine;

            $i++;

        }
        return $lines;

    }

//this is BMiner code some fixes for manule breaks
    public function wordWrapAnnotation(&$image, &$draw, $text, $maxWidth)
    {
        $brler = explode("<br>", $text);
        $lines = array();


        foreach ($brler as $br) {
            $i = 0;


            $words = explode(" ", $br);


            while ($i < count($words)) {

                $currentLine = $words[$i];


                $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i + 1]);

                if ($i + 1 >= count($words)) {
                    $checked = $this->check_long_words($image, $draw, $currentLine, $maxWidth);
                    $lines = array_merge($lines, $checked);

                    if ($metrics['textHeight'] > $lineHeight)
                        $lineHeight = $metrics['textHeight'];
                    //$lines = $lines + $checked;
                    break;
                }
                //Check to see if we can add another word to this line


                while ($metrics['textWidth'] <= $maxWidth) {
                    //If so, do it and keep doing it!
                    $currentLine .= ' ' . $words[++$i];
                    if ($i + 1 >= count($words))
                        break;
                    $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i + 1]);
                    $t++;
                }
                //We can't add the next word to this line, so loop to the next line

                $checked = $this->check_long_words($image, $draw, $currentLine, $maxWidth);
                $lines = array_merge($lines, $checked);

                $i++;
                //Finally, update line height
                if ($metrics['textHeight'] > $lineHeight)
                    $lineHeight = $metrics['textHeight'];
            }


        }
        return array(join("\n", $lines), $lineHeight);


    }

}