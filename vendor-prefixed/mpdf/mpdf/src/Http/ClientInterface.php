<?php

namespace Mpdf\Http;

use ForminatorPDFAddon\Psr\Http\Message\RequestInterface;
interface ClientInterface
{
    public function sendRequest(RequestInterface $request);
}