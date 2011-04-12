<?php

namespace Chitanka\LibBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
// use Symfony\Component\DependencyInjection\ContainerInterface;
// use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LibBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }
}
