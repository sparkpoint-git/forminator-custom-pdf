<?php

namespace ForminatorPDFAddon\DeepCopy\TypeFilter\Spl;

use ForminatorPDFAddon\DeepCopy\DeepCopy;
use ForminatorPDFAddon\DeepCopy\TypeFilter\TypeFilter;
/**
 * In PHP 7.4 the storage of an ArrayObject isn't returned as
 * ReflectionProperty. So we deep copy its array copy.
 */
final class ArrayObjectFilter implements TypeFilter
{
    /**
     * @var DeepCopy
     */
    private $copier;
    public function __construct(DeepCopy $copier)
    {
        $this->copier = $copier;
    }
    /**
     * {@inheritdoc}
     */
    public function apply($arrayObject)
    {
        $clone = clone $arrayObject;
        foreach ($arrayObject->getArrayCopy() as $k => $v) {
            $clone->offsetSet($k, $this->copier->copy($v));
        }
        return $clone;
    }
}