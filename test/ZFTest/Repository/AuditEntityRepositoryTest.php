<?php

namespace ZFTest\Doctrine\Audit\Service;

use ZFTest\Doctrine\Audit\Bootstrap;
use PHPUnit_Framework_TestCase;
use ZFTest\Doctrine\Audit\Entity;
use ZF\Doctrine\Audit\Entity\AuditEntity;

class AuditEntityRepositoryTest extends PHPUnit_Framework_TestCase
{
    public function testGetRevisionIdentifierValue()
    {
        $serviceManager = Bootstrap::getApplication()->getServiceManager();

        $auditObjectManager = $serviceManager->get('doctrine.entitymanager.orm_zf_doctrine_audit');

        $artist = new Entity\Artist();

        $auditClass = $auditObjectManager->getRepository(AuditEntity::class)
            ->generateClassName(get_class($artist));

        $this->assertEquals('ZF\\Doctrine\\Audit\\AuditEntity\\ZFTest_Doctrine_Audit_Entity_Artist', $auditClass);
    }
}
