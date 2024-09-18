<?php
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRCodeOutputException;
use chillerlan\QRCode\Output\QRMarkupSVG;

class QRImageWithLogo extends QRGdImagePNG{
    public function dump(string|null $file = null, string|null $logo = null): string {
        $this->options->returnResource = true;
        if ($logo !== null) {
            if (!is_file($logo) || !is_readable($logo))
                throw new QRCodeOutputException('Invalid logo');
        }
        parent::dump($file);
        if ($logo !== null) {
            $im = imagecreatefrompng($logo);
            if ($im === false)
                throw new QRCodeOutputException('imagecreatefrompng() error');
            $w = imagesx($im);
            $h = imagesy($im);
            $lw = ($this->options->logoSpaceWidth - 2) * $this->options->scale;
            $lh = ($this->options->logoSpaceHeight - 2) * $this->options->scale;
            $ql = $this->matrix->getSize() * $this->options->scale;
            imagecopyresampled($this->image, $im, ($ql - $lw) / 2, ($ql - $lh) / 2, 0, 0, $lw, $lh, $w, $h);
            imagedestroy($im);
        }
        $imageData = $this->dumpImage();
        $this->saveToFile($imageData, $file);
        if ($this->options->outputBase64) 
            $imageData = $this->toBase64DataURI($imageData);
        return $imageData;
    }
} 