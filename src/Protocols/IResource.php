<?php

namespace BreakDown\PdfToImage\Protocols;

interface IResource
{

    /**
     * Based on what type of resource is used, the relevant data should be returned.
     *
     * @return string
     */
    public function getData();

    /**
     * Based on what kind of resource is used, the proper validation should be performed and
     * identified the resource as either valid or invalid.
     *
     * @return true
     */
    public function isValid();
}
