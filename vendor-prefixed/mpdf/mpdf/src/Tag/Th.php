<?php

namespace Mpdf\Tag;

class Th extends \Mpdf\Tag\Td
{
    public function close(&$ahtml, &$ihtml)
    {
        $this->mpdf->SetStyle('B', \false);
        parent::close($ahtml, $ihtml);
    }
}