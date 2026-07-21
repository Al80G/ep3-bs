<?php

namespace Championship\Service;

use Championship\Entity\Fixture;
use Championship\Manager\ParticipantManager;
use User\Entity\User;

class MatchResultValidator
{

    protected $participantManager;

    /**
     * Creates a new championship match result validator object.
     *
     * @param ParticipantManager $participantManager
     */
    public function __construct(ParticipantManager $participantManager)
    {
        $this->participantManager = $participantManager;
    }

    /**
     * Whether the passed user may enter or edit the result of the passed match.
     *
     * Admins (and assist users with the "admin.championship" privilege) may always edit.
     * Otherwise only one of the match's own participants may enter a result, and only
     * as long as no result has been recorded for it yet (once played, only an admin may correct it).
     *
     * @param Fixture $match
     * @param User $user
     * @return boolean
     */
    public function isEditableBy(Fixture $match, User $user)
    {
        if ($user->can('admin.championship')) {
            return true;
        }

        if ($match->isPlayed()) {
            return false;
        }

        if (! $match->isReady()) {
            return false;
        }

        $uid = $user->need('uid');

        foreach (array('participant1_pid', 'participant2_pid') as $slot) {
            $participant = $this->participantManager->get($match->need($slot));

            if ($participant->hasPlayer($uid)) {
                return true;
            }
        }

        return false;
    }

}
