<?php

namespace ForminatorPDFAddon\DeepCopy\Matcher\Doctrine;

use ForminatorPDFAddon\DeepCopy\Matcher\Matcher;
use ForminatorPDFAddon\Doctrine\Persistence\Proxy;
/**
 * @final
 */
class DoctrineProxyMatcher implements Matcher
{
    /**
     * Matches a Doctrine Proxy class.
     *
     * {@inheritdoc}
     */
    public function matches($object, $property)
    {
        return $object instanceof Proxy;
    }
}