<?php

namespace Championship\Controller;

use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;

class ChampionshipController extends AbstractActionController
{

    public function indexAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            $this->redirectBack()->setOrigin('championship');

            return $this->redirect()->toRoute('user/login');
        }

        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');

        $championships = $championshipManager->getActive();

        $allowedGenders = $this->championshipAllowedCategoryGenders($user);

        $categoriesByChampionship = array();

        foreach ($championships as $championship) {
            $categoriesByChampionship[$championship->need('cid')] = array_filter(
                $categoryManager->getByChampionship($championship),
                function ($category) use ($allowedGenders) {
                    if ($category->get('status') != 'enabled') {
                        return false;
                    }

                    return ! $allowedGenders || in_array($category->need('gender'), $allowedGenders);
                }
            );
        }

        $registeredCatids = array();

        foreach ($participantManager->getByUser($user->need('uid')) as $participant) {
            $registeredCatids[] = $participant->need('catid');
        }

        return array(
            'user' => $user,
            'championships' => $championships,
            'categoriesByChampionship' => $categoriesByChampionship,
            'registeredCatids' => $registeredCatids,
        );
    }

    public function categoryAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            $this->redirectBack()->setOrigin('championship');

            return $this->redirect()->toRoute('user/login');
        }

        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');
        $groupManager = $serviceManager->get('Championship\Manager\GroupManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');
        $standingsService = $serviceManager->get('Championship\Service\StandingsService');
        $bracketService = $serviceManager->get('Championship\Service\BracketService');
        $matchResultValidator = $serviceManager->get('Championship\Service\MatchResultValidator');
        $userManager = $serviceManager->get('User\Manager\UserManager');

        $catid = $this->params()->fromRoute('catid');

        $category = $categoryManager->get($catid);
        $championship = $championshipManager->get($category->need('cid'));

        $participantLabels = array();
        $myParticipantPid = null;

        foreach ($participantManager->getByCategory($category) as $participant) {
            $participantLabels[$participant->need('pid')] = $this->championshipParticipantLabel($participant, $userManager);

            if ($participant->hasPlayer($user->need('uid'))) {
                $myParticipantPid = $participant->need('pid');
            }
        }

        $groups = $groupManager->getByCategory($category);

        $standingsByGroup = array();
        $groupMatchesByGroup = array();

        foreach ($groups as $group) {
            $standingsByGroup[$group->need('gid')] = $standingsService->getStandings($group);
            $groupMatchesByGroup[$group->need('gid')] = $matchManager->getByGroup($group);
        }

        $koMatches = $matchManager->getBy(array('catid' => $catid, 'round_type' => 'ko'));

        $allMatches = $koMatches;

        foreach ($groupMatchesByGroup as $groupMatches) {
            $allMatches = array_merge($allMatches, $groupMatches);
        }

        $editableMatchIds = array();

        foreach ($allMatches as $match) {
            if ($matchResultValidator->isEditableBy($match, $user)) {
                $editableMatchIds[] = $match->need('mid');
            }
        }

        return array(
            'user' => $user,
            'championship' => $championship,
            'category' => $category,
            'groups' => $groups,
            'standingsByGroup' => $standingsByGroup,
            'groupMatchesByGroup' => $groupMatchesByGroup,
            'koMatches' => $koMatches,
            'participantLabels' => $participantLabels,
            'myParticipantPid' => $myParticipantPid,
            'editableMatchIds' => $editableMatchIds,
            'koGenerated' => $bracketService->isGenerated($category),
        );
    }

    public function registerAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            $this->redirectBack()->setOrigin('championship/register');

            return $this->redirect()->toRoute('user/login');
        }

        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $formElementManager = $serviceManager->get('FormElementManager');

        $catid = $this->params()->fromQuery('catid') ?: $this->params()->fromPost('rf-catid');

        if (! $catid) {
            throw new RuntimeException('A category id is required to register');
        }

        $category = $categoryManager->get($catid);
        $championship = $championshipManager->get($category->need('cid'));

        $allowedGenders = $this->championshipAllowedCategoryGenders($user);

        if ($allowedGenders && ! in_array($category->need('gender'), $allowedGenders)) {
            return array(
                'category' => $category,
                'championship' => $championship,
                'alreadyRegistered' => false,
                'registrationClosed' => false,
                'genderMismatch' => true,
                'registrationForm' => null,
            );
        }

        if ($championship->get('status') != 'open') {
            return array(
                'category' => $category,
                'championship' => $championship,
                'alreadyRegistered' => false,
                'registrationClosed' => true,
                'genderMismatch' => false,
                'registrationForm' => null,
            );
        }

        if ($participantManager->isRegistered($category, $user->need('uid'))) {
            return array(
                'category' => $category,
                'championship' => $championship,
                'alreadyRegistered' => true,
                'registrationClosed' => false,
                'genderMismatch' => false,
                'registrationForm' => null,
            );
        }

        $registrationForm = $formElementManager->get('Championship\Form\RegistrationForm');

        $isDouble = $category->need('discipline') == 'double';
        $isMixed = $category->need('gender') == 'mixed';

        if ($isDouble) {
            $alreadyTeamedUpUids = array();

            foreach ($participantManager->getByCategory($category) as $participant) {
                $alreadyTeamedUpUids[] = $participant->need('uid');

                if ($participant->get('partner_uid')) {
                    $alreadyTeamedUpUids[] = $participant->get('partner_uid');
                }
            }

            $ownGender = $user->getMeta('gender');
            $oppositeGender = $ownGender == 'male' ? 'female' : ($ownGender == 'female' ? 'male' : null);

            $partnerOptions = array();

            foreach ($userManager->getBy(array('status' => array('enabled', 'assist', 'admin')), 'alias ASC') as $candidate) {
                $candidateUid = $candidate->need('uid');

                if ($candidateUid == $user->need('uid') || in_array($candidateUid, $alreadyTeamedUpUids)) {
                    continue;
                }

                if ($isMixed && $oppositeGender && $candidate->getMeta('gender') != $oppositeGender) {
                    continue;
                }

                $partnerOptions[$candidateUid] = $candidate->need('alias');
            }

            $registrationForm->setPartnerOptions($partnerOptions);
        } else {
            $registrationForm->removePartnerField();
        }

        if ($this->getRequest()->isPost()) {
            $registrationForm->setData($this->params()->fromPost());

            if ($registrationForm->isValid()) {
                $data = $registrationForm->getData();

                $partnerUid = $isDouble ? ($data['rf-partner-uid'] ?: null) : null;

                if ($isDouble && ! $partnerUid) {
                    $this->flashMessenger()->addErrorMessage('Please select a partner');
                } else if ($isDouble && ! $this->championshipIsValidMixedPair($category, $user->need('uid'), $partnerUid, $userManager)) {
                    $this->flashMessenger()->addErrorMessage('A mixed pair requires one man and one woman');
                } else {
                    try {
                        $participantManager->register($category, $user->need('uid'), $partnerUid);

                        $this->flashMessenger()->addSuccessMessage('You have been registered');

                        return $this->redirect()->toRoute('championship/my');
                    } catch (RuntimeException $e) {
                        $this->flashMessenger()->addErrorMessage($e->getMessage());
                    }
                }
            }
        } else {
            $registrationForm->get('rf-catid')->setValue($catid);
        }

        return array(
            'category' => $category,
            'championship' => $championship,
            'alreadyRegistered' => false,
            'registrationClosed' => false,
            'genderMismatch' => false,
            'registrationForm' => $registrationForm,
        );
    }

    public function withdrawAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            $this->redirectBack()->setOrigin('championship/my');

            return $this->redirect()->toRoute('user/login');
        }

        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');

        $pid = $this->params()->fromRoute('pid');

        $participant = $participantManager->get($pid);

        if (! $participant->hasPlayer($user->need('uid'))) {
            throw new RuntimeException('You are not allowed to withdraw this registration');
        }

        $category = $categoryManager->get($participant->need('catid'));

        $hasMatches = (bool) $matchManager->getByParticipant($category, $participant->need('pid'));

        if (! $hasMatches && $this->params()->fromQuery('confirmed') == 'true') {
            $participantManager->delete($participant);

            $this->flashMessenger()->addSuccessMessage('You have been withdrawn from this category');

            return $this->redirect()->toRoute('championship/my');
        }

        return array(
            'category' => $category,
            'participant' => $participant,
            'hasMatches' => $hasMatches,
        );
    }

    public function myAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            $this->redirectBack()->setOrigin('championship/my');

            return $this->redirect()->toRoute('user/login');
        }

        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');
        $matchResultValidator = $serviceManager->get('Championship\Service\MatchResultValidator');
        $userManager = $serviceManager->get('User\Manager\UserManager');

        $registrations = array();

        foreach ($participantManager->getByUser($user->need('uid')) as $participant) {
            $category = $categoryManager->get($participant->need('catid'));
            $championship = $championshipManager->get($category->need('cid'));

            $participantLabels = array();

            foreach ($participantManager->getByCategory($category) as $otherParticipant) {
                $participantLabels[$otherParticipant->need('pid')] = $this->championshipParticipantLabel($otherParticipant, $userManager);
            }

            $matchRows = array();

            foreach ($matchManager->getByParticipant($category, $participant->need('pid')) as $match) {
                $matchRows[] = array(
                    'match' => $match,
                    'editable' => $matchResultValidator->isEditableBy($match, $user),
                    'opponentLabel' => $this->championshipOpponentLabel($match, $participant->need('pid'), $participantLabels),
                );
            }

            $registrations[] = array(
                'category' => $category,
                'championship' => $championship,
                'participant' => $participant,
                'partnerLabel' => $participant->get('partner_uid')
                    ? $userManager->get($participant->need('partner_uid'))->get('alias', '?') : null,
                'matches' => $matchRows,
            );
        }

        return array(
            'user' => $user,
            'registrations' => $registrations,
        );
    }

    public function matchResultAction()
    {
        $serviceManager = @$this->getServiceLocator();
        $userSessionManager = $serviceManager->get('User\Manager\UserSessionManager');

        $user = $userSessionManager->getSessionUser();

        if (! $user) {
            $this->redirectBack()->setOrigin('championship/my');

            return $this->redirect()->toRoute('user/login');
        }

        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $matchResultValidator = $serviceManager->get('Championship\Service\MatchResultValidator');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $formElementManager = $serviceManager->get('FormElementManager');

        $mid = $this->params()->fromRoute('mid');

        $match = $matchManager->get($mid);
        $category = $categoryManager->get($match->need('catid'));

        if (! $matchResultValidator->isEditableBy($match, $user)) {
            throw new RuntimeException('You are not allowed to enter the result of this match');
        }

        $participantLabels = array();

        foreach ($participantManager->getByCategory($category) as $participant) {
            $participantLabels[$participant->need('pid')] = $this->championshipParticipantLabel($participant, $userManager);
        }

        $matchResultForm = $formElementManager->get('Championship\Form\MatchResultForm');

        if ($this->getRequest()->isPost()) {
            $matchResultForm->setData($this->params()->fromPost());

            if ($matchResultForm->isValid()) {
                $data = $matchResultForm->getData();

                $sets = array();

                foreach (array(1, 2, 3) as $setNumber) {
                    $score1 = $data['mrf-set' . $setNumber . '-p1'];
                    $score2 = $data['mrf-set' . $setNumber . '-p2'];

                    if ($score1 !== '' && $score2 !== '') {
                        $sets[] = array(
                            'score_participant1' => (int) $score1,
                            'score_participant2' => (int) $score2,
                        );
                    }
                }

                try {
                    $matchManager->recordResult($match, $sets, $user->need('uid'));

                    $this->flashMessenger()->addSuccessMessage('Result has been saved');

                    return $this->redirect()->toRoute('championship/category', array('catid' => $category->need('catid')));
                } catch (RuntimeException $e) {
                    $this->flashMessenger()->addErrorMessage($e->getMessage());
                }
            }
        } else {
            $setNumber = 1;

            foreach ($matchManager->getSets($match) as $set) {
                $matchResultForm->get('mrf-set' . $setNumber . '-p1')->setValue($set->get('score_participant1'));
                $matchResultForm->get('mrf-set' . $setNumber . '-p2')->setValue($set->get('score_participant2'));

                $setNumber++;
            }
        }

        return array(
            'category' => $category,
            'match' => $match,
            'participant1Label' => $match->get('participant1_pid') ? $participantLabels[$match->get('participant1_pid')] : '— bye —',
            'participant2Label' => $match->get('participant2_pid') ? $participantLabels[$match->get('participant2_pid')] : '— bye —',
            'matchResultForm' => $matchResultForm,
        );
    }

    /**
     * Builds a human-readable label for a participant (player name, or "player / partner" for pairs).
     *
     * @param \Championship\Entity\Participant $participant
     * @param \User\Manager\UserManager $userManager
     * @return string
     */
    protected function championshipParticipantLabel($participant, $userManager)
    {
        $label = $userManager->get($participant->need('uid'))->get('alias', '?');

        if ($participant->get('partner_uid')) {
            $label .= ' / ' . $userManager->get($participant->need('partner_uid'))->get('alias', '?');
        }

        return $label;
    }

    /**
     * Builds the opponent's label for a match, from the perspective of the passed participant id.
     *
     * @param \Championship\Entity\Fixture $match
     * @param int $myPid
     * @param array $participantLabels
     * @return string
     */
    protected function championshipOpponentLabel($match, $myPid, array $participantLabels)
    {
        $opponentPid = $match->get('participant1_pid') == $myPid ? $match->get('participant2_pid') : $match->get('participant1_pid');

        return $opponentPid ? ($participantLabels[$opponentPid] ?? '?') : '— bye —';
    }

    /**
     * Gets the category genders the passed user may see/register for, based on their account's gender.
     *
     * Men only see men's and mixed categories, women only see women's and mixed categories.
     * Accounts without a personal gender (e.g. family/firm accounts, or none set) are not restricted.
     *
     * @param \User\Entity\User $user
     * @return array|null      null means no restriction (all genders allowed)
     */
    protected function championshipAllowedCategoryGenders($user)
    {
        $gender = $user->getMeta('gender');

        if ($gender == 'male') {
            return array('men', 'mixed');
        } else if ($gender == 'female') {
            return array('women', 'mixed');
        }

        return null;
    }

    /**
     * Whether the passed pair is valid for the given category: for "mixed" categories, one of the
     * two players must be male and the other female. Any other category (or unknown genders,
     * so that accounts without a gender set don't get blocked outright) is not restricted.
     *
     * @param \Championship\Entity\Category $category
     * @param int $uid
     * @param int $partnerUid
     * @param \User\Manager\UserManager $userManager
     * @return boolean
     */
    protected function championshipIsValidMixedPair($category, $uid, $partnerUid, $userManager)
    {
        if ($category->need('gender') != 'mixed') {
            return true;
        }

        $gender1 = $userManager->get($uid)->getMeta('gender');
        $gender2 = $userManager->get($partnerUid)->getMeta('gender');

        if (! ($gender1 && $gender2)) {
            return true;
        }

        return ($gender1 == 'male' && $gender2 == 'female') || ($gender1 == 'female' && $gender2 == 'male');
    }

}
