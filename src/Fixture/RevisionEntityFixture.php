<?php

namespace ZF\Doctrine\Audit\Fixture;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use ZF\Doctrine\Audit\Entity;
use DateTime;

class RevisionEntityFixture implements
    FixtureInterface,
    DependentFixtureInterface
{
    public $config;
    public $objectManager;

    public function getDependencies()
    {
        return [
            'ZF\Doctrine\Audit\Fixture\FieldStatusFixture',
        ];
    }

    public function load(ObjectManager $auditObjectManager)
    {
        $fieldStatusActive = $auditObjectManager->getRepository('ZF\Doctrine\Audit\Entity\FieldStatus')
            ->findOneBy([
                'name' => 'active',
            ]);

        // Create a revision to associate with field revision
        $revision = new Entity\Revision();
        $revision->setCreatedAt(new DateTime());
        $revision->setComment('Data Fixture Import');

        $auditObjectManager->persist($revision);

        foreach ($this->config['entities'] as $className => $route) {
            $targetEntity = $auditObjectManager
                ->getRepository('ZF\Doctrine\Audit\Entity\TargetEntity')
                ->findOneBy(['name' => $className]);

            if (! $targetEntity) {
                $auditEntityClassName = $auditObjectManager
                    ->getRepository('ZF\Doctrine\Audit\Entity\AuditEntity')
                    ->generateClassName($className)
                    ;

                $auditEntity = new Entity\AuditEntity();
                $auditEntity->setName($auditEntityClassName);
                $auditEntity->setTableName(
                    $auditObjectManager
                        ->getClassMetadata($auditEntityClassName)
                        ->getTableName()
                );

                $targetEntity = new Entity\TargetEntity();
                $targetEntity->setAuditEntity($auditEntity);
                $targetEntity->setName($className);
                $targetEntity->setTableName(
                    $this->objectManager
                        ->getClassMetadata($className)
                        ->getTableName()
                );

                $identifiers = $this->objectManager
                    ->getClassMetadata($className)
                    ->getIdentifierFieldNames()
                    ;

                foreach ($identifiers as $fieldName) {
                    $identifier = new Entity\Identifier();
                    $identifier->setTargetEntity($targetEntity);
                    $identifier->setFieldName($fieldName);
                    $identifier->setColumnName(
                        $this->objectManager
                            ->getClassMetadata($className)
                            ->getColumnName($fieldName)
                    );

                    $auditObjectManager->persist($identifier);
                }

                // Add Fields
                $fields = $this->objectManager
                    ->getClassMetadata($className)
                    ->getFieldNames()
                    ;

                foreach ($fields as $fieldName) {
                    $field = new Entity\Field();
                    $field->setTargetEntity($targetEntity);
                    $field->setName($fieldName);
                    $field->setColumnName(
                        $this->objectManager
                            ->getClassMetadata($className)
                            ->getColumnName($fieldName)
                    );

                    $fieldRevision = new Entity\FieldRevision();
                    $fieldRevision->setFieldStatus($fieldStatusActive);
                    $fieldRevision->setField($field);
                    $fieldRevision->setRevision($revision);

                    $auditObjectManager->persist($field);
                    $auditObjectManager->persist($fieldRevision);
                }

                // Add Associations to Fields
                $associations = $this->objectManager
                    ->getClassMetadata($className)
                    ->getAssociationNames()
                    ;

                foreach ($associations as $fieldName) {
                    $associationMapping = $this->objectManager
                        ->getClassMetadata($className)
                        ->getAssociationMapping($fieldName);

                    if (! isset($associationMapping['joinColumns'])) {
                        continue;
                    }

                    if (sizeof($associationMapping['joinColumns']) != 1) {
                        throw new Exception('Unable to handle > 1 join column per association');
                    }

                    $field = new Entity\Field();
                    $field->setTargetEntity($targetEntity);
                    $field->setName($fieldName);
                    $field->setColumnName(array_shift($associationMapping['joinColumns'])['name']);

                    $fieldRevision = new Entity\FieldRevision();
                    $fieldRevision->setFieldStatus($fieldStatusActive);
                    $fieldRevision->setField($field);
                    $fieldRevision->setRevision($revision);

                    $auditObjectManager->persist($field);
                    $auditObjectManager->persist($fieldRevision);
                }

                $auditObjectManager->persist($auditEntity);
                $auditObjectManager->persist($targetEntity);
            }
        }

        $auditObjectManager->flush();
    }
}