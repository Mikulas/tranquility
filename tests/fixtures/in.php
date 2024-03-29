<?php

namespace Bin\Services;

use App\Models\Orm\Mapper;
use App\Models\Orm\Model;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Inflect\Inflect;
use Nette\Object;
use Nette\Reflection\ClassType;
use Nextras\Orm\Entity\Reflection\PropertyMetadata;
use Nextras\Orm\Repository\Repository;
use Nextras\Orm\StorageReflection\UnderscoredDbStorageReflection;
class SchemaBuilder extends Object
{
	public function create(Model $model)
	{
		list ($a,$b)=[1 ,2] ;
		$schema = new Schema();
		foreach ($model->getRepositories()['entity'] as $entityClass => $repoClass) {
			$this->createTable($schema, $model, $entityClass, $repoClass);
		}
		return $schema;
	}
	protected function createTable(Schema $schema, Model $model, $entityClass, $repoClass)
	{
		/** @var Repository $repo */
		$repo = $model->getRepository($repoClass);
		/** @var Mapper $mapper */
		$mapper = $repo->getMapper();
		$tableName = $mapper->getTableName();
		$table = $schema->createTable($tableName);
		$meta = $model->getMetadataStorage()->get($entityClass);
		foreach ($meta->getProperties() as $param) {
			if ($param->relationshipType === PropertyMetadata::RELATIONSHIP_ONE_HAS_MANY) {
				continue;
			} else if ($param->relationshipType === PropertyMetadata::RELATIONSHIP_MANY_HAS_MANY) {
				if (!$param->relationshipIsMain) {
					continue;
				}
				/** @var Repository $targetRepo */
				$targetRepo = $model->getRepository($param->relationshipRepository);
				$joinTableName = $mapper->getStorageReflection()->getManyHasManyStorageName($targetRepo->getMapper());
				$joinTable = $schema->createTable($joinTableName);
				$columnThis = Inflect::singularize($param->name) . '_id';
				$joinTable->addColumn($columnThis, 'integer');
				$joinTable->addForeignKeyConstraint($tableName, array($columnThis), array('id'));
				$columnThat = Inflect::singularize($param->relationshipProperty) . '_id';
				$joinTable->addColumn($columnThat, 'integer');
				$joinTable->addForeignKeyConstraint($targetRepo->getMapper()->getTableName(), array($columnThat), array('id'));
				$joinTable->setPrimaryKey(array($columnThis, $columnThat));
				continue;
			}
			$name = UnderscoredDbStorageReflection::underscore($param->name);
			$type = NULL;
			foreach (array_keys($param->types) as $type) {
				if ($type === 'nextras\\orm\\relationships\\manyhasone') {
					continue;
				}
				break;
			}
			if (strpos($type, 'app\\') === 0) {
				$name = "{$name}_id";
				$table->addColumn($name, 'integer');
				$fTable = Inflect::pluralize(ClassType::from($type)->getShortName());
				$table->addForeignKeyConstraint($fTable, array($name), array('id'));
			} else if ($param->name === 'id') {
				$type = 'integer';
				$table->addColumn($name, 'integer', array('autoincrement' => TRUE));
			} else {
				$table->addColumn($name, $type);
			}
		}
		$table->setPrimaryKey($meta->primaryKey);
	}
}
