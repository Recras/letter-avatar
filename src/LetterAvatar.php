<?php

namespace YoHang88\LetterAvatar;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\ImageManager;

class LetterAvatar
{
    /**
     * Image Type PNG
     */
    const MIME_TYPE_PNG = 'image/png';

    /**
     * Image Type JPEG
     */
    const MIME_TYPE_JPEG = 'image/jpeg';

    private string $name;

    private string $shape;

    private int $size;

    private ImageManager $imageManager;

    private string $backgroundColor = '';

    private string $foregroundColor = '';

    /**
     * LetterAvatar constructor.
     * @param string $name
     * @param string $shape
     * @param int    $size
     */
    public function __construct(string $name, string $shape = 'circle', int $size = 48)
    {
        $this->setName($name);
        $this->setImageManager(new ImageManager(new Driver()));
        $this->setShape($shape);
        $this->setSize($size);
    }

    /**
     * color in RGB format (example: #FFFFFF)
     */
    public function setColor(string $backgroundColor, string $foregroundColor): self
    {
        $this->backgroundColor = $backgroundColor;
        $this->foregroundColor = $foregroundColor;
        return $this;
    }

    private function setName(string $name): void
    {
        $this->name = $name;
    }

    private function setImageManager(ImageManager $imageManager): void
    {
        $this->imageManager = $imageManager;
    }

    private function setShape(string $shape): void
    {
        $this->shape = $shape;
    }

    private function setSize(int $size): void
    {
        $this->size = $size;
    }

    private function generate(): \Intervention\Image\Image
    {
        $isCircle = $this->shape === 'circle';

        $nameInitials = $this->getInitials($this->name);
        $this->backgroundColor = $this->backgroundColor ?: $this->stringToColor($this->name);
        $this->foregroundColor = $this->foregroundColor ?: '#fafafa';

        $canvas = $this->imageManager->create(480, 480);

        if ($isCircle) {
            $canvas->drawCircle(240, 240, function (CircleFactory $draw) {
                $draw->diameter(480);
                $draw->background($this->backgroundColor);
            });
        } else {
            $canvas->fill($this->backgroundColor);
        }

        $canvas->text($nameInitials, 240, 240, function ($font) {
            $font->filename(__DIR__ . '/fonts/arial-bold.ttf');
            $font->size(220);
            $font->color($this->foregroundColor);
            $font->valign('middle');
            $font->align('center');
        });

        return $canvas->resize($this->size, $this->size);
    }

    private function getInitials(string $name): string
    {
        $nameParts = $this->break_name($name);

        if(!$nameParts) {
            return '';
        }

        $secondLetter = isset($nameParts[1]) ? $this->getFirstLetter($nameParts[1]) : '';

        return $this->getFirstLetter($nameParts[0]) . $secondLetter;

    }

    private function getFirstLetter(string $word): string
    {
        return mb_strtoupper(trim(mb_substr($word, 0, 1, 'UTF-8')));
    }

    /**
     * Get the generated Letter-Avatar as a png or jpg string
     */
    public function encode(string $mimetype = self::MIME_TYPE_PNG, int $quality = 90): string
    {
        $allowedMimeTypes = [
            self::MIME_TYPE_PNG,
            self::MIME_TYPE_JPEG,
        ];
        if(!in_array($mimetype, $allowedMimeTypes, true)) {
            throw new \InvalidArgumentException('Invalid mimetype');
        }
        return $this->generate()->encodeByMediaType($mimetype, $quality);
    }

    /**
     * Save the generated Letter-Avatar as a file
      */
    public function saveAs(string $path, string $mimetype = self::MIME_TYPE_PNG, int $quality = 90): bool
    {
        if (empty($path)) {
            return false;
        }

        return \is_int(@file_put_contents($path, $this->encode($mimetype, $quality)));
    }

    public function __toString(): string
    {
        return $this->generate()->toPng()->toDataUri();
    }

    /**
     * Explodes Name into an array.
     * The function will check if a part is , or blank
     *
     * @param string $name Name to be broken up
     * @return array Name broken up to an array
     */
    private function break_name(string $name): array
    {
        $words = \explode(' ', $name);
        $words = array_filter($words, function($word) {
            return $word!=='' && $word !== ',';
        });
        return array_values($words);
    }

    private function stringToColor(string $string): string
    {
        $crc = hash('crc32b', $string);
        // random color
        $rgb = substr($crc, 0, 6);
        // make it darker
        $darker = 2;
        list($R16, $G16, $B16) = str_split($rgb, 2);
        $R = sprintf('%02X', floor(hexdec($R16) / $darker));
        $G = sprintf('%02X', floor(hexdec($G16) / $darker));
        $B = sprintf('%02X', floor(hexdec($B16) / $darker));
        return '#' . $R . $G . $B;
    }
}
