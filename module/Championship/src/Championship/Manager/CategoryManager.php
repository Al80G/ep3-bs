<?php

namespace Championship\Manager;

use Base\Manager\AbstractManager;
use Championship\Entity\Category;
use Championship\Entity\CategoryFactory;
use Championship\Entity\Championship;
use Championship\Table\CategoryTable;
use InvalidArgumentException;
use RuntimeException;

class CategoryManager extends AbstractManager
{

    protected $categoryTable;

    /**
     * Creates a new championship category manager object.
     *
     * @param CategoryTable $categoryTable
     */
    public function __construct(CategoryTable $categoryTable)
    {
        $this->categoryTable = $categoryTable;
    }

    /**
     * Saves (updates or creates) a category.
     *
     * @param Category $category
     * @return Category
     * @throws RuntimeException
     */
    public function save(Category $category)
    {
        if ($category->get('catid')) {

            /* Update existing category */

            $updates = array();

            foreach ($category->need('updatedProperties') as $property) {
                $updates[$property] = $category->get($property);
            }

            if ($updates) {
                $this->categoryTable->update($updates, array('catid' => $category->get('catid')));
            }

            $category->reset();

            $this->getEventManager()->trigger('save.update', $category);

        } else {

            /* Insert category */

            $this->categoryTable->insert(array(
                'cid' => $category->need('cid'),
                'discipline' => $category->need('discipline'),
                'gender' => $category->need('gender'),
                'name' => $category->need('name'),
                'group_size_max' => $category->get('group_size_max', 6),
                'advance_per_group' => $category->get('advance_per_group', 2),
                'admin_assigns_partners' => $category->get('admin_assigns_partners', 0),
                'status' => $category->get('status', 'enabled'),
            ));

            $catid = $this->categoryTable->getLastInsertValue();

            if (! (is_numeric($catid) && $catid > 0)) {
                throw new RuntimeException('Failed to save category');
            }

            $category->add('catid', $catid);

            $this->getEventManager()->trigger('save.insert', $category);
        }

        $this->getEventManager()->trigger('save', $category);

        return $category;
    }

    /**
     * Gets the category by primary id.
     *
     * @param int $catid
     * @param boolean $strict
     * @return Category
     * @throws RuntimeException
     */
    public function get($catid, $strict = true)
    {
        $category = $this->getBy(array('catid' => $catid));

        if (empty($category)) {
            if ($strict) {
                throw new RuntimeException('This category does not exist');
            }

            return null;
        } else {
            return current($category);
        }
    }

    /**
     * Gets all categories that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getBy($where, $order = null, $limit = null, $offset = null)
    {
        $select = $this->categoryTable->getSql()->select();

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

        $resultSet = $this->categoryTable->selectWith($select);

        return CategoryFactory::fromResultSet($resultSet);
    }

    /**
     * Gets all categories of the passed championship.
     *
     * @param int|Championship $championship
     * @param string $order
     * @return array
     * @throws InvalidArgumentException
     */
    public function getByChampionship($championship, $order = 'name ASC')
    {
        if ($championship instanceof Championship) {
            $cid = $championship->need('cid');
        } else {
            $cid = $championship;
        }

        if (! (is_numeric($cid) && $cid > 0)) {
            throw new InvalidArgumentException('Championship id must be numeric');
        }

        return $this->getBy(array('cid' => $cid), $order);
    }

    /**
     * Gets all categories.
     *
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($order = 'name ASC', $limit = null, $offset = null)
    {
        return $this->getBy(null, $order, $limit, $offset);
    }

    /**
     * Deletes one category (and all its participants/groups/matches through database foreign keys).
     *
     * @param int|Category $category
     * @return int
     * @throws InvalidArgumentException
     */
    public function delete($category)
    {
        if ($category instanceof Category) {
            $catid = $category->need('catid');
        } else {
            $catid = $category;
        }

        if (! (is_numeric($catid) && $catid > 0)) {
            throw new InvalidArgumentException('Category id must be numeric');
        }

        $category = $this->get($catid);

        $deletion = $this->categoryTable->delete(array('catid' => $catid));

        $this->getEventManager()->trigger('delete', $category);

        return $deletion;
    }

}
