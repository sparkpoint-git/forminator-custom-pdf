<?php

namespace ForminatorPDFAddon\DeepCopy\Filter\Doctrine;

use ForminatorPDFAddon\DeepCopy\Filter\Filter;
use ForminatorPDFAddon\DeepCopy\Reflection\ReflectionHelper;
use ForminatorPDFAddon\Doctrine\Common\Collections\ArrayCollection;
/**
 * @final
 */
class DoctrineEmptyCollectionFilter implements Filter
{
    /**
     * Sets the object property to an empty doctrine collection.
     *
     * @param object   $object
     * @param string   $property
     * @param callable $objectCopier
     */
    public function apply($object, $property, $objectCopier)
    {
        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $reflectionProperty->setAccessible(\true);
        $reflectionProperty->setValue($object, new ArrayCollection());
    }
}