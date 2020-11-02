<?php

namespace ZF\Doctrine\Audit\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use ZF\Doctrine\Audit\Persistence;
use ZF\Doctrine\Audit\Entity;
use Exception;

class EntityDriver implements
    MappingDriver,
    Persistence\EntityConfigCollectionAwareInterface,
    Persistence\ObjectManagerAwareInterface,
    Persistence\AuditObjectManagerAwareInterface,
    Persistence\AuditOptionsAwareInterface
{
    use Persistence\EntityConfigCollectionAwareTrait;
    use Persistence\ObjectManagerAwareTrait;
    use Persistence\AuditObjectManagerAwareTrait;
    use Persistence\AuditOptionsAwareTrait;

    /**
     * Load the metadata for the specified class into the provided container.
     *
     * @param string        $className
     * @param ClassMetadata $metadata
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $metadataFactory = $this->getObjectManager()->getMetadataFactory();
        $builder = new ClassMetadataBuilder($metadata);

        // Get the entity this entity audits
        $metadataClassName = $metadata->getName();
        $metadataClass = new $metadataClassName();
        $auditedClassName = $metadataClass->getAuditedEntityClass();

        $auditedClassMetadata = $metadataFactory->getMetadataFor($auditedClassName);
        $auditedClassName = $metadataClass->getAuditedEntityClass();

        // Is the passed class name a regular entity?
        if (! $this->getEntityConfigCollection()->containsKey($auditedClassName)) {
            return false;
        }

        // Add primary key
        $primaryKey = $builder->createField('_id', 'bigint')
            ->makePrimaryKey()
            ->generatedValue()
            ->unique()
            ->columnName('_id')
            ->build()
            ;

        $association = $builder->createManyToOne('revisionEntity', Entity\RevisionEntity::class);
        $association->build();

        // Add fields from target to audit entity
        foreach ($auditedClassMetadata->getFieldNames() as $fieldName) {
            $builder->addField(
                $fieldName,
                $auditedClassMetadata->getTypeOfField($fieldName),
                [
                    'columnName' => $auditedClassMetadata->getColumnName($fieldName),
                    'nullable' => true,
                    'quoted' => true
                ]
            );

            // Created index on audited primary key
            if ($auditedClassMetadata->isIdentifier($fieldName)) {
                $builder->addIndex(
                    [$fieldName],
                    $this->generateIdentifierName([$auditedClassMetadata->getTableName(), $fieldName], 'idx')
                );
            }
        }

        foreach ($auditedClassMetadata->getAssociationMappings() as $mapping) {
            if (! $mapping['isOwningSide'] || isset($mapping['joinTable'])) {
                continue;
            }

            if (isset($mapping['joinTableColumns'])) {
                foreach ($mapping['joinTableColumns'] as $field) {
                    // FIXME:  set data type correct for mapping info
                    $builder->addField(
                        $mapping['fieldName'],
                        'bigint',
                        ['nullable' => true, 'columnName' => $field]
                    );
                }
            } elseif (isset($mapping['joinColumnFieldNames'])) {
                foreach ($mapping['joinColumnFieldNames'] as $field) {
                    // FIXME:  set data type correct for mapping info
                    $builder->addField(
                        $mapping['fieldName'],
                        'bigint',
                        ['nullable' => true, 'columnName' => $field]
                    );
                }
            } else {
                throw new Exception('Unhandled association mapping');
            }
        }

        $metadata->setTableName(
            $this->getAuditOptions()->getAuditTableNamePrefix()
                . $auditedClassMetadata->getTableName()
                . $this->getAuditOptions()->getAuditTableNameSuffix()
        );

        return;
    }

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    public function getAllClassNames(): array
    {
        $auditEntityRepository = $this->getAuditObjectManager()
            ->getRepository(Entity\AuditEntity::class);

        $classNames = [];
        foreach ($this->getEntityConfigCollection() as $className => $config) {
            $classNames[] = $auditEntityRepository->generateClassName($className);
        }

        return $classNames;
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a
     * MappedSuperclass.
     *
     * @param  string $className
     * @return boolean
     */
    // @codeCoverageIgnoreStart
    public function isTransient($className): bool
    {
        return true;
    }
    // @codeCoverageIgnoreEnd


    // Copied from Doctrine\DBAL\Schema\AbstractAsset
    protected function generateIdentifierName($columnNames, $prefix = '', $maxSize = 30)
    {
        $hash = implode('', array_map(static function ($column) {
            return dechex(crc32($column));
        }, $columnNames));

        return strtoupper(substr($prefix . '_' . $hash, 0, $maxSize));
    }
}
