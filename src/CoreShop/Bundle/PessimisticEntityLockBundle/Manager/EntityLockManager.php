<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2020 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Bundle\PessimisticEntityLockBundle\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\LockMode;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;

final class EntityLockManager implements EntityLockManagerInterface
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function write(Concrete $dataObject)
    {
        $this->connection->beginTransaction();

        $platform = $this->connection->getDatabasePlatform();
        $lockSql = $platform->getWriteLockSQL();
        $lock = $platform->appendLockHint(
            'FROM '
            . 'object_' . $dataObject->getClassId(),
            LockMode::PESSIMISTIC_WRITE
        );
        $where = ' WHERE o_id = :id ';

        $sql = 'SELECT 1 '
             . $lock
             . $where
             . $lockSql;

        $retries = 0;

        do {
            try {
                $this->connection->executeQuery($sql, ['id' => $dataObject->getId()]);

                $dataObject->save();

                $this->connection->commit();

                return;
            } catch (RetryableException $ex) {
                $this->connection->rollBack();

                ++$retries;
            }
            catch (\Exception $ex) {
                $this->connection->rollBack();
                throw $ex;
            }
        }
        while ($retries < 10);
    }

    public function read(string $classId, int $id, int $lockMode)
    {
        $this->connection->beginTransaction();

        $platform = $this->connection->getDatabasePlatform();

        switch ($lockMode) {
            case LockMode::PESSIMISTIC_READ:
                $lockSql = $platform->getReadLockSQL();

                break;
            case LockMode::PESSIMISTIC_WRITE:

                $lockSql = $platform->getWriteLockSQL();
                break;
        }

        $lock = $platform->appendLockHint(
            'FROM '
            . 'object_' . $classId,
            $lockMode
        );
        $where = ' WHERE o_id = :id ';

        $sql = 'SELECT 1 '
             . $lock
             . $where
             . $lockSql;

        $retries = 0;

        do {
            try {
                $this->connection->executeQuery($sql, ['id' => $id]);

                $obj = DataObject::getById($id);

                $this->connection->commit();

                return $obj;
            } catch (RetryableException $ex) {
                $this->connection->rollBack();

                ++$retries;
            }
            catch (\Exception $ex) {
                $this->connection->rollBack();
                throw $ex;
            }
        }
        while ($retries < 10);

        throw $ex;
    }
}
