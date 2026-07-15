<?php

namespace Championship\Entity\Fixture;

use Base\Entity\AbstractEntity;

class SetScore extends AbstractEntity
{

    protected $msid;
    protected $mid;
    protected $set_number;
    protected $score_participant1;
    protected $score_participant2;
    protected $tiebreak_participant1;
    protected $tiebreak_participant2;

    /**
     * Which participant slot (1 or 2) won this set.
     *
     * @return int
     */
    public function getWinnerSlot()
    {
        return $this->need('score_participant1') > $this->need('score_participant2') ? 1 : 2;
    }

}
