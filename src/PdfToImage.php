<?php

namespace BreakDown\PdfToImage;

use BreakDown\PdfToImage\Exceptions as Exceptions;
use BreakDown\PdfToImage\Protocols\IResource;
use Imagick;

class PdfToImage
{

    /**
     *
     * @var IResource
     */
    protected $resourcePdf;

    /**
     *
     * @var int
     */
    protected $resolution = 144;

    /**
     *
     * @var string
     */
    protected $outputFormat = 'jpg';

    /**
     *
     * @var int
     */
    protected $page = 1;

    /**
     *
     * @var int
     */
    protected $numberOfPages;

    /**
     *
     * @var string[]
     */
    protected $validOutputFormats = ['jpg', 'jpeg', 'png'];

    /**
     *
     * @var int
     */
    protected $layerMethod = Imagick::LAYERMETHOD_FLATTEN;

    /**
     *
     * @var int
     */
    protected $colorspace;

    /**
     *
     * @var int
     */
    protected $compressionQuality;

    /**
     * @param IResource $resourse
     *
     * @throws Exceptions\PdfDoesNotExist
     */
    public function __construct(IResource $resourse)
    {
        if (!$resourse->isValid()) {
            throw new Exceptions\PdfDoesNotExist("Invalid PDF Resource Provided.");
        }

        $this->resourcePdf = $resourse;
    }

    // <editor-fold defaultstate="collapsed" desc="Setters">

    /**
     * Set the raster resolution.
     *
     * @param int $resolution
     *
     * @return self
     */
    public function setResolution($resolution)
    {
        $this->resolution = $resolution;

        return $this;
    }

    /**
     * Set the output format.
     *
     * @param string $outputFormat
     *
     * @return self
     *
     * @throws Exceptions\InvalidFormat
     */
    public function setOutputFormat($outputFormat)
    {
        if (!$this->isValidOutputFormat($outputFormat)) {
            throw new Exceptions\InvalidFormat("Format {$outputFormat} is not supported");
        }

        $this->outputFormat = $outputFormat;

        return $this;
    }

    /**
     * Sets the layer method for Imagick::mergeImageLayers()
     * If int, should correspond to a predefined LAYERMETHOD constant.
     * If null, Imagick::mergeImageLayers() will not be called.
     *
     * @param int|null
     *
     * @return self
     *
     * @throws Exceptions\InvalidLayerMethod
     *
     * @see https://secure.php.net/manual/en/imagick.constants.php
     * @see Pdf::getImageData()
     */
    public function setLayerMethod($layerMethod)
    {
        if (is_int($layerMethod) === false && is_null($layerMethod) === false) {
            throw new Exceptions\InvalidLayerMethod('LayerMethod must be an integer or null');
        }

        $this->layerMethod = $layerMethod;

        return $this;
    }

    /**
     * @param int $colorspace
     *
     * @return self
     */
    public function setColorspace($colorspace)
    {
        $this->colorspace = $colorspace;

        return $this;
    }

    /**
     * @param int $compressionQuality
     *
     * @return self
     */
    public function setCompressionQuality($compressionQuality)
    {
        $this->compressionQuality = $compressionQuality;

        return $this;
    }

    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Getters">

    /**
     * Get the output format.
     *
     * @return string
     */
    public function getOutputFormat()
    {
        return $this->outputFormat;
    }

    /**
     * Get the number of pages in the PDF file.
     *
     * @return int
     */
    public function getNumberOfPages()
    {
        if (!$this->numberOfPages) {
            $imagick = new Imagick($this->resourcePdf->getData());
            $this->numberOfPages = $imagick->getNumberImages();
        }
        return $this->numberOfPages;
    }

    /**
     * Return raw image data.
     *
     * @param int $page
     * @param string $outputPath
     *
     * @return string
     */
    public function getPageAsImage($page, $outputPath)
    {
        /*
         * Reinitialize imagick because the target resolution must be set
         * before reading the actual image.
         */
        $imagick = new Imagick();

        $imagick->setResolution($this->resolution, $this->resolution);

        if ($this->colorspace !== null) {
            $imagick->setColorspace($this->colorspace);
        }

        if ($this->compressionQuality !== null) {
            $imagick->setCompressionQuality($this->compressionQuality);
        }

        $imagick->readImageBlob($this->resourcePdf->getData());
        $imagick->setIteratorIndex($page - 1);

        if (is_int($this->layerMethod)) {
            $imagick = $imagick->mergeImageLayers($this->layerMethod);
        }

        $imagick->setFormat($this->determineOutputFormat($outputPath));

        return $imagick->getImageBlob();
    }

    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Save Image">

    /**
     * Save the image to the given path.
     *
     * @param int $page
     * @param string $pathToImage
     *
     * @return bool
     */
    public function savePageAsImage($page, $pathToImage)
    {
        if (is_dir($pathToImage)) {
            $pathToImage = rtrim($pathToImage, '\/') . DIRECTORY_SEPARATOR . $page . '.' . $this->outputFormat;
        }

        $imageData = $this->getPageAsImage($pathToImage);

        return file_put_contents($pathToImage, $imageData) !== false;
    }

    /**
     * Save the file as images to the given directory.
     *
     * @param string $directory
     * @param string $prefix
     *
     * @return array $files the paths to the created images
     */
    public function saveAllPagesAsImages($directory, $prefix = '')
    {
        $numberOfPages = $this->getNumberOfPages();

        if ($numberOfPages === 0) {
            return [];
        }

        return array_map(function ($pageNumber) use ($directory, $prefix) {
            $this->setPage($pageNumber);

            $destination = "{$directory}/{$prefix}{$pageNumber}.{$this->outputFormat}";

            $this->savePageAsImage($destination);

            return $destination;
        }, range(1, $numberOfPages));
    }

    // </editor-fold>
    // <editor-fold defaultstate="collapsed" desc="Helper Methods">

    /**
     * Determine in which format the image must be rendered.
     *
     * @param $pathToImage
     *
     * @return string
     */
    protected function determineOutputFormat($pathToImage)
    {
        $outputFormat = pathinfo($pathToImage, PATHINFO_EXTENSION);

        if ($this->outputFormat != '') {
            $outputFormat = $this->outputFormat;
        }

        $outputFormat = strtolower($outputFormat);

        if (!$this->isValidOutputFormat($outputFormat)) {
            $outputFormat = 'jpg';
        }

        return $outputFormat;
    }

    /**
     * Determine if the given format is a valid output format.
     *
     * @param $outputFormat
     *
     * @return bool
     */
    protected function isValidOutputFormat($outputFormat)
    {
        return in_array($outputFormat, $this->validOutputFormats);
    }

    // </editor-fold>
}
