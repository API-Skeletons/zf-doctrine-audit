<?php

namespace ZF\Doctrine\Audit\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;

class ControllersAbstractFactory extends AbstractAbstractFactory
{
    protected $factoryClasses = [
        'ZF\Doctrine\Audit\Controller\SchemaTool' =>
            'ZF\Doctrine\Audit\Controller\SchemaToolController',
        'ZF\Doctrine\Audit\Controller\DataFixture' =>
            'ZF\Doctrine\Audit\Controller\DataFixtureController',
    ];
}