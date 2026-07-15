<?php

namespace Championship\Manager;

use Base\Manager\AbstractManager;
use Championship\Entity\Category;
use Championship\Entity\Group;
use Championship\Entity\GroupFactory;
use Championship\Entity\GroupMemberFactory;
use Championship\Entity\Participant;
use Championship\Table\GroupMemberTable;
use Championship\Table\GroupTable;
use DateTime;
use InvalidArgumentException;
use RuntimeException;

class GroupManager extends AbstractManager
{

    protected $groupTable;
    protected $groupMemberTable;

    /**
     * Creates a new championship group manager object.
     *
     * @param GroupTable $groupTable
     * @param GroupMemberTable $groupMemberTable
     */
    public function __construct(GroupTable $groupTable, GroupMemberTable $groupMemberTable)
    {
        $this->groupTable = $groupTable;
        $this->groupMemberTable = $groupMemberTable;
    }

    /**
     * Saves (updates or creates) a group.
     *
     * @param Group $group
     * @return Group
     * @throws RuntimeException
     */
    public function save(Group $group)
    {
        if ($group->get('gid')) {

            /* Update existing group */

            $updates = array();

            foreach ($group->need('updatedProperties') as $property) {
                $updates[$property] = $group->get($property);
            }

            if ($updates) {
                $this->groupTable->update($updates, array('gid' => $group->get('gid')));
            }

            $group->reset();

            $this->getEventManager()->trigger('save.update', $group);

        } else {

            /* Insert group */

            if (! $group->get('created')) {
                $group->add('created', (new DateTime())->format('Y-m-d H:i:s'));
            }

            $this->groupTable->insert(array(
                'catid' => $group->need('catid'),
                'name' => $group->need('name'),
                'created' => $group->get('created'),
            ));

            $gid = $this->groupTable->getLastInsertValue();

            if (! (is_numeric($gid) && $gid > 0)) {
                throw new RuntimeException('Failed to save group');
            }

            $group->add('gid', $gid);

            $this->getEventManager()->trigger('save.insert', $group);
        }

        $this->getEventManager()->trigger('save', $group);

        return $group;
    }

    /**
     * Gets the group by primary id.
     *
     * @param int $gid
     * @param boolean $strict
     * @return Group
     * @throws RuntimeException
     */
    public function get($gid, $strict = true)
    {
        $group = $this->getBy(array('gid' => $gid));

        if (empty($group)) {
            if ($strict) {
                throw new RuntimeException('This group does not exist');
            }

            return null;
        } else {
            return current($group);
        }
    }

    /**
     * Gets all groups that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @return array
     */
    public function getBy($where, $order = null)
    {
        $select = $this->groupTable->getSql()->select();

        if ($where) {
            $select->where($where);
        }

        if ($order) {
            $select->order($order);
        }

        $resultSet = $this->groupTable->selectWith($select);

        return GroupFactory::fromResultSet($resultSet);
    }

    /**
     * Gets all groups of the passed category.
     *
     * @param int|Category $category
     * @param string $order
     * @return array
     * @throws InvalidArgumentException
     */
    public function getByCategory($category, $order = 'name ASC')
    {
        if ($category instanceof Category) {
            $catid = $category->need('catid');
        } else {
            $catid = $category;
        }

        if (! (is_numeric($catid) && $catid > 0)) {
            throw new InvalidArgumentException('Category id must be numeric');
        }

        return $this->getBy(array('catid' => $catid), $order);
    }

    /**
     * Adds a participant to a group.
     *
     * Throws an exception if the group is already at its maximum size (when $maxSize is passed)
     * or if the participant is already a member of this group.
     *
     * @param Group $group
     * @param Participant $participant
     * @param int $maxSize
     * @throws RuntimeException
     */
    public function addMember(Group $group, Participant $participant, $maxSize = null)
    {
        $pid = $participant->need('pid');

        if (in_array($pid, $this->getMemberPids($group))) {
            throw new RuntimeException('This participant is already a member of this group');
        }

        if ($maxSize && count($this->getMemberPids($group)) >= $maxSize) {
            throw new RuntimeException( sprintf('This group already has the maximum of %d members', $maxSize) );
        }

        $this->groupMemberTable->insert(array(
            'gid' => $group->need('gid'),
            'pid' => $pid,
        ));

        $this->getEventManager()->trigger('member.add', array('group' => $group, 'participant' => $participant));
    }

    /**
     * Removes a participant from a group.
     *
     * @param Group $group
     * @param Participant $participant
     */
    public function removeMember(Group $group, Participant $participant)
    {
        $this->groupMemberTable->delete(array('gid' => $group->need('gid'), 'pid' => $participant->need('pid')));

        $this->getEventManager()->trigger('member.remove', array('group' => $group, 'participant' => $participant));
    }

    /**
     * Gets the participant ids that are members of the passed group.
     *
     * @param Group $group
     * @return array
     */
    public function getMemberPids(Group $group)
    {
        $select = $this->groupMemberTable->getSql()->select();
        $select->where(array('gid' => $group->need('gid')));

        $resultSet = $this->groupMemberTable->selectWith($select);

        $members = GroupMemberFactory::fromResultSet($resultSet);

        $pids = array();

        foreach ($members as $member) {
            $pids[] = $member->need('pid');
        }

        return $pids;
    }

    /**
     * Gets the group a participant is a member of within a category, if any.
     *
     * @param int|Category $category
     * @param int $pid
     * @return Group|null
     * @throws InvalidArgumentException
     */
    public function getByParticipant($category, $pid)
    {
        foreach ($this->getByCategory($category) as $group) {
            if (in_array($pid, $this->getMemberPids($group))) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Gets all groups.
     *
     * @param string $order
     * @return array
     */
    public function getAll($order = 'name ASC')
    {
        return $this->getBy(null, $order);
    }

    /**
     * Deletes one group (and all its memberships/matches through database foreign keys).
     *
     * @param int|Group $group
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($group)
    {
        if ($group instanceof Group) {
            $gid = $group->need('gid');
        } else {
            $gid = $group;
        }

        if (! (is_numeric($gid) && $gid > 0)) {
            throw new InvalidArgumentException('Group id must be numeric');
        }

        $group = $this->get($gid);

        $deletion = $this->groupTable->delete(array('gid' => $gid));

        $this->getEventManager()->trigger('delete', $group);

        return $deletion;
    }

}
