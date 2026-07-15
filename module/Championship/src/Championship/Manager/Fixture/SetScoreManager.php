<?php

namespace Championship\Manager\Fixture;

use Base\Manager\AbstractManager;
use Championship\Entity\Fixture;
use Championship\Entity\Fixture\SetScore;
use Championship\Entity\Fixture\SetScoreFactory;
use Championship\Table\Fixture\SetScoreTable;
use InvalidArgumentException;
use RuntimeException;

class SetScoreManager extends AbstractManager
{

    protected $setScoreTable;

    /**
     * Creates a new championship match set score manager object.
     *
     * @param SetScoreTable $setScoreTable
     */
    public function __construct(SetScoreTable $setScoreTable)
    {
        $this->setScoreTable = $setScoreTable;
    }

    /**
     * Saves (updates or creates) a set score.
     *
     * @param SetScore $setScore
     * @return SetScore
     * @throws RuntimeException
     */
    public function save(SetScore $setScore)
    {
        if ($setScore->get('msid')) {

            /* Update existing set score */

            $updates = array();

            foreach ($setScore->need('updatedProperties') as $property) {
                $updates[$property] = $setScore->get($property);
            }

            if ($updates) {
                $this->setScoreTable->update($updates, array('msid' => $setScore->get('msid')));
            }

            $setScore->reset();

            $this->getEventManager()->trigger('save.update', $setScore);

        } else {

            /* Insert set score */

            $this->setScoreTable->insert(array(
                'mid' => $setScore->need('mid'),
                'set_number' => $setScore->need('set_number'),
                'score_participant1' => $setScore->need('score_participant1'),
                'score_participant2' => $setScore->need('score_participant2'),
                'tiebreak_participant1' => $setScore->get('tiebreak_participant1'),
                'tiebreak_participant2' => $setScore->get('tiebreak_participant2'),
            ));

            $msid = $this->setScoreTable->getLastInsertValue();

            if (! (is_numeric($msid) && $msid > 0)) {
                throw new RuntimeException('Failed to save set score');
            }

            $setScore->add('msid', $msid);

            $this->getEventManager()->trigger('save.insert', $setScore);
        }

        $this->getEventManager()->trigger('save', $setScore);

        return $setScore;
    }

    /**
     * Gets all set scores that match the passed conditions.
     *
     * @param mixed $where
     * @param string $order
     * @return array
     */
    public function getBy($where, $order = 'set_number ASC')
    {
        $select = $this->setScoreTable->getSql()->select();

        if ($where) {
            $select->where($where);
        }

        if ($order) {
            $select->order($order);
        }

        $resultSet = $this->setScoreTable->selectWith($select);

        return SetScoreFactory::fromResultSet($resultSet);
    }

    /**
     * Gets all set scores of the passed match, ordered by set number.
     *
     * @param int|Fixture $match
     * @return array
     * @throws InvalidArgumentException
     */
    public function getByMatch($match)
    {
        if ($match instanceof Fixture) {
            $mid = $match->need('mid');
        } else {
            $mid = $match;
        }

        if (! (is_numeric($mid) && $mid > 0)) {
            throw new InvalidArgumentException('Match id must be numeric');
        }

        return $this->getBy(array('mid' => $mid));
    }

    /**
     * Replaces all set scores of a match with the passed set of scores.
     *
     * Each entry of $sets must be an array with keys score_participant1, score_participant2
     * and optionally tiebreak_participant1/tiebreak_participant2.
     *
     * @param int|Fixture $match
     * @param array $sets
     * @return array
     * @throws InvalidArgumentException
     */
    public function replaceForMatch($match, array $sets)
    {
        if ($match instanceof Fixture) {
            $mid = $match->need('mid');
        } else {
            $mid = $match;
        }

        if (! (is_numeric($mid) && $mid > 0)) {
            throw new InvalidArgumentException('Match id must be numeric');
        }

        $this->setScoreTable->delete(array('mid' => $mid));

        $setScores = array();

        $setNumber = 1;

        foreach ($sets as $set) {
            $setScore = new SetScore(array(
                'mid' => $mid,
                'set_number' => $setNumber,
                'score_participant1' => $set['score_participant1'],
                'score_participant2' => $set['score_participant2'],
                'tiebreak_participant1' => $set['tiebreak_participant1'] ?? null,
                'tiebreak_participant2' => $set['tiebreak_participant2'] ?? null,
            ));

            $this->save($setScore);

            $setScores[] = $setScore;

            $setNumber++;
        }

        return $setScores;
    }

    /**
     * Deletes all set scores of a match.
     *
     * @param int|Fixture $match
     * @throws InvalidArgumentException
     */
    public function deleteByMatch($match)
    {
        if ($match instanceof Fixture) {
            $mid = $match->need('mid');
        } else {
            $mid = $match;
        }

        if (! (is_numeric($mid) && $mid > 0)) {
            throw new InvalidArgumentException('Match id must be numeric');
        }

        $this->setScoreTable->delete(array('mid' => $mid));
    }

}
