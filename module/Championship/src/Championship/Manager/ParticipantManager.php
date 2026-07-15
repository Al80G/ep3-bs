<?php

namespace Championship\Manager;

use Base\Manager\AbstractManager;
use Championship\Entity\Category;
use Championship\Entity\Participant;
use Championship\Entity\ParticipantFactory;
use Championship\Table\ParticipantTable;
use DateTime;
use InvalidArgumentException;
use RuntimeException;
use Zend\Db\Sql\Where;

class ParticipantManager extends AbstractManager
{

    protected $participantTable;

    /**
     * Creates a new championship participant manager object.
     *
     * @param ParticipantTable $participantTable
     */
    public function __construct(ParticipantTable $participantTable)
    {
        $this->participantTable = $participantTable;
    }

    /**
     * Registers a new participant (single player, or one half of a pair) for a category.
     *
     * @param int|Category $category
     * @param int $uid
     * @param int $partnerUid
     * @return Participant
     * @throws InvalidArgumentException
     */
    public function register($category, $uid, $partnerUid = null)
    {
        if ($category instanceof Category) {
            $catid = $category->need('catid');
        } else {
            $catid = $category;
        }

        if (! (is_numeric($catid) && $catid > 0)) {
            throw new InvalidArgumentException('Category id must be numeric');
        }

        if (! (is_numeric($uid) && $uid > 0)) {
            throw new InvalidArgumentException('User id must be numeric');
        }

        $participant = new Participant(array(
            'catid' => $catid,
            'uid' => $uid,
            'partner_uid' => $partnerUid ?: null,
            'status' => 'registered',
        ));

        $this->save($participant);

        $this->getEventManager()->trigger('register', $participant);

        return $participant;
    }

    /**
     * Saves (updates or creates) a participant.
     *
     * @param Participant $participant
     * @return Participant
     * @throws RuntimeException
     */
    public function save(Participant $participant)
    {
        if ($participant->get('pid')) {

            /* Update existing participant */

            $updates = array();

            foreach ($participant->need('updatedProperties') as $property) {
                $updates[$property] = $participant->get($property);
            }

            if ($updates) {
                $this->participantTable->update($updates, array('pid' => $participant->get('pid')));
            }

            $participant->reset();

            $this->getEventManager()->trigger('save.update', $participant);

        } else {

            /* Insert participant */

            if (! $participant->get('created')) {
                $participant->add('created', (new DateTime())->format('Y-m-d H:i:s'));
            }

            $this->participantTable->insert(array(
                'catid' => $participant->need('catid'),
                'uid' => $participant->need('uid'),
                'partner_uid' => $participant->get('partner_uid'),
                'status' => $participant->get('status', 'registered'),
                'seed' => $participant->get('seed'),
                'created' => $participant->get('created'),
            ));

            $pid = $this->participantTable->getLastInsertValue();

            if (! (is_numeric($pid) && $pid > 0)) {
                throw new RuntimeException('Failed to save participant');
            }

            $participant->add('pid', $pid);

            $this->getEventManager()->trigger('save.insert', $participant);
        }

        $this->getEventManager()->trigger('save', $participant);

        return $participant;
    }

    /**
     * Gets the participant by primary id.
     *
     * @param int $pid
     * @param boolean $strict
     * @return Participant
     * @throws RuntimeException
     */
    public function get($pid, $strict = true)
    {
        $participant = $this->getBy(array('pid' => $pid));

        if (empty($participant)) {
            if ($strict) {
                throw new RuntimeException('This participant does not exist');
            }

            return null;
        } else {
            return current($participant);
        }
    }

    /**
     * Gets all participants that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getBy($where, $order = null, $limit = null, $offset = null)
    {
        $select = $this->participantTable->getSql()->select();

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

        $resultSet = $this->participantTable->selectWith($select);

        return ParticipantFactory::fromResultSet($resultSet);
    }

    /**
     * Gets all registered participants of the passed category.
     *
     * @param int|Category $category
     * @param string $order
     * @return array
     * @throws InvalidArgumentException
     */
    public function getByCategory($category, $order = 'created ASC')
    {
        if ($category instanceof Category) {
            $catid = $category->need('catid');
        } else {
            $catid = $category;
        }

        if (! (is_numeric($catid) && $catid > 0)) {
            throw new InvalidArgumentException('Category id must be numeric');
        }

        return $this->getBy(array('catid' => $catid, 'status' => 'registered'), $order);
    }

    /**
     * Gets all registrations (as player or partner) of the passed user, across all categories.
     *
     * @param int $uid
     * @param string $order
     * @return array
     */
    public function getByUser($uid, $order = 'created ASC')
    {
        $where = new Where();
        $where->equalTo('status', 'registered');

        $where->and;

        $nested = $where->nest();
        $nested->equalTo('uid', $uid);
        $nested->or;
        $nested->equalTo('partner_uid', $uid);
        $nested->unnest();

        return $this->getBy($where, $order);
    }

    /**
     * Whether the passed user is already registered (as player or partner) for the given category.
     *
     * @param int|Category $category
     * @param int $uid
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function isRegistered($category, $uid)
    {
        foreach ($this->getByCategory($category) as $participant) {
            if ($participant->hasPlayer($uid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets all participants.
     *
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($order = 'created ASC', $limit = null, $offset = null)
    {
        return $this->getBy(null, $order, $limit, $offset);
    }

    /**
     * Deletes one participant.
     *
     * @param int|Participant $participant
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($participant)
    {
        if ($participant instanceof Participant) {
            $pid = $participant->need('pid');
        } else {
            $pid = $participant;
        }

        if (! (is_numeric($pid) && $pid > 0)) {
            throw new InvalidArgumentException('Participant id must be numeric');
        }

        $participant = $this->get($pid);

        $deletion = $this->participantTable->delete(array('pid' => $pid));

        $this->getEventManager()->trigger('delete', $participant);

        return $deletion;
    }

}
