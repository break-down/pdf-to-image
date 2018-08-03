<?php

namespace BreakDown\PdfToImage\Resources;

use BreakDown\PdfToImage\Protocols\IResource;

class FileData implements IResource
{

    /**
     *
     * @var string
     */
    protected $data;

    function __construct($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        if (!$this->isValid()) {
            return null;
        }

        return $this->data;
    }

    public function isValid()
    {
        if (!$this->data) {
            return false;
        }

        return true;
    }

}
