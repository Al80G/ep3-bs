<?php

namespace Championship\Manager;

use Base\Manager\AbstractManager;
use Championship\Entity\Championship;
use Championship\Entity\ChampionshipFactory;
use Championship\Table\ChampionshipTable;
use DateTime;
use InvalidArgumentException;
use RuntimeException;

class ChampionshipManager extends AbstractManager
{

    protected $championshipTable;

    /**
     * Creates a new championship manager object.
     *
     * @param ChampionshipTable $championshipTable
     */
    public function __construct(ChampionshipTable $championshipTable)
    {
        $this->championshipTable = $championshipTable;
    }

    /**
     * Saves (updates or creates) a championship.
     *
     * @param Championship $championship
     * @return Championship
     * @throws RuntimeException
     */
    public function save(Championship $championship)
    {
        if ($championship->get('cid')) {

            /* Update existing championship */

            $updates = array();

            foreach ($championship->need('updatedProperties') as $property) {
                $updates[$property] = $championship->get($property);
            }

            if ($updates) {
                $this->championshipTable->update($updates, array('cid' => $championship->get('cid')));
            }

            $championship->reset();

            $this->getEventManager()->trigger('save.update', $championship);

        } else {

            /* Insert championship */

            if (! $championship->get('created')) {
                $championship->add('created', (new DateTime())->format('Y-m-d H:i:s'));
            }

            if (! $championship->get('status')) {
                $championship->add('status', 'draft');
            }

            $this->championshipTable->insert(array(
                'name' => $championship->need('name'),
                'status' => $championship->get('status', 'draft'),
                'datetime_registration_start' => $championship->get('datetime_registration_start'),
                'datetime_registration_end' => $championship->get('datetime_registration_end'),
                'info_text' => $championship->get('info_text'),
                'created' => $championship->get('created'),
            ));

            $cid = $this->championshipTable->getLastInsertValue();

            if (! (is_numeric($cid) && $cid > 0)) {
                throw new RuntimeException('Failed to save championship');
            }

            $championship->add('cid', $cid);

            $this->getEventManager()->trigger('save.insert', $championship);
        }

        $this->getEventManager()->trigger('save', $championship);

        return $championship;
    }

    /**
     * Gets the championship by primary id.
     *
     * @param int $cid
     * @param boolean $strict
     * @return Championship
     * @throws RuntimeException
     */
    public function get($cid, $strict = true)
    {
        $championship = $this->getBy(array('cid' => $cid));

        if (empty($championship)) {
            if ($strict) {
                throw new RuntimeException('This championship does not exist');
            }

            return null;
        } else {
            return current($championship);
        }
    }

    /**
     * Gets all championships that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getBy($where, $order = null, $limit = null, $offset = null)
    {
        $select = $this->championshipTable->getSql()->select();

        if ($where) {
            $select->where($where);
        }

        if ($order) {
            $select->order($order);
        }

        if ($limit) {
            $select->limit($limit);

            if ($offset) {
                $select->offset($offset);
            }
        }

        $resultSet = $this->championshipTable->selectWith($select);

        return ChampionshipFactory::fromResultSet($resultSet);
    }

    /**
     * Gets all championships that are currently open for registration or running.
     *
     * @param string $order
     * @return array
     */
    public function getActive($order = 'created DESC')
    {
        return $this->getBy(array('status' => array('open', 'running')), $order);
    }

    /**
     * Gets all championships.
     *
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($order = 'created DESC', $limit = null, $offset = null)
    {
        return $this->getBy(null, $order, $limit, $offset);
    }

    /**
     * Deletes one championship (and all its categories/participants/groups/matches through database foreign keys).
     *
     * @param int|Championship $championship
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($championship)
    {
        if ($championship instanceof Championship) {
            $cid = $championship->need('cid');
        } else {
            $cid = $championship;
        }

        if (! (is_numeric($cid) && $cid > 0)) {
            throw new InvalidArgumentException('Championship id must be numeric');
        }

        $championship = $this->get($cid);

        $deletion = $this->championshipTable->delete(array('cid' => $cid));

        $this->getEventManager()->trigger('delete', $championship);

        return $deletion;
    }

}
