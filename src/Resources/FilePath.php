<?php

namespace BreakDown\PdfToImage\Resources;

use BreakDown\PdfToImage\Protocols\IResource;

class FilePath implements IResource
{

    /**
     *
     * @var string
     */
    protected $path;

    /**
     *
     * @var string
     */
    protected $data;

    function __construct($path)
    {
        $this->path = $path;
    }

    public function getData()
    {
        if (!$this->isValid()) {
            return null;
        }

        if (!$this->data) {
            $this->data = file_get_contents($this->path);
        }
        return $this->data;
    }

    public function isValid()
    {
        if (!$this->path) {
            return false;
        }

        if (!file_exists($this->path)) {
            return false;
        }

        return true;
    }

}
