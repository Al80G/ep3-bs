<?php

namespace Backend\Controller;

use Championship\Entity\Category;
use Championship\Entity\Championship;
use Championship\Entity\Group;
use DateTime;
use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;

class ChampionshipController extends AbstractActionController
{

    public function indexAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');

        return array(
            'championships' => $championshipManager->getAll(),
        );
    }

    public function editAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $formElementManager = $serviceManager->get('FormElementManager');

        $cid = $this->params()->fromRoute('cid');

        $championship = $cid ? $championshipManager->get($cid) : null;

        $editForm = $formElementManager->get('Backend\Form\Championship\EditForm');

        if ($this->getRequest()->isPost()) {
            $editForm->setData($this->params()->fromPost());

            if ($editForm->isValid()) {
                $data = $editForm->getData();

                $registrationStart = $data['chf-registration-start']
                    ? (new DateTime($data['chf-registration-start']))->setTime(0, 0)->format('Y-m-d H:i:s') : null;
                $registrationEnd = $data['chf-registration-end']
                    ? (new DateTime($data['chf-registration-end']))->setTime(23, 59, 59)->format('Y-m-d H:i:s') : null;

                if (! $championship) {
                    $championship = new Championship();
                }

                $championship->set('name', $data['chf-name']);
                $championship->set('status', $data['chf-status']);
                $championship->set('datetime_registration_start', $registrationStart);
                $championship->set('datetime_registration_end', $registrationEnd);

                $championshipManager->save($championship);

                $this->flashMessenger()->addSuccessMessage('Championship has been saved');

                return $this->redirect()->toRoute('backend/championship/edit', array('cid' => $championship->need('cid')));
            }
        } else {
            if ($championship) {
                $editForm->setData(array(
                    'chf-name' => $championship->get('name'),
                    'chf-status' => $championship->get('status'),
                    'chf-registration-start' => $championship->get('datetime_registration_start')
                        ? $this->dateFormat(new DateTime($championship->get('datetime_registration_start')), \IntlDateFormatter::MEDIUM) : '',
                    'chf-registration-end' => $championship->get('datetime_registration_end')
                        ? $this->dateFormat(new DateTime($championship->get('datetime_registration_end')), \IntlDateFormatter::MEDIUM) : '',
                ));
            } else {
                $editForm->setData(array(
                    'chf-status' => 'draft',
                ));
            }
        }

        return array(
            'championship' => $championship,
            'editForm' => $editForm,
            'categories' => $championship ? $categoryManager->getByChampionship($championship) : array(),
        );
    }

    public function deleteAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');

        $cid = $this->params()->fromRoute('cid');

        $championship = $championshipManager->get($cid);

        if ($this->params()->fromQuery('confirmed') == 'true') {
            $championshipManager->delete($championship);

            $this->flashMessenger()->addSuccessMessage('Championship has been deleted');

            return $this->redirect()->toRoute('backend/championship');
        }

        return array(
            'championship' => $championship,
        );
    }

    public function categoryEditAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $groupManager = $serviceManager->get('Championship\Manager\GroupManager');
        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');
        $bracketService = $serviceManager->get('Championship\Service\BracketService');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $formElementManager = $serviceManager->get('FormElementManager');

        $catid = $this->params()->fromRoute('catid');

        if ($catid) {
            $category = $categoryManager->get($catid);
            $championship = $championshipManager->get($category->need('cid'));
        } else {
            $cid = $this->params()->fromQuery('cid');

            if (! $cid) {
                throw new RuntimeException('A championship id is required to create a new category');
            }

            $championship = $championshipManager->get($cid);
            $category = null;
        }

        $categoryForm = $formElementManager->get('Backend\Form\Championship\CategoryForm');

        if ($this->getRequest()->isPost()) {
            $categoryForm->setData($this->params()->fromPost());

            if ($categoryForm->isValid()) {
                $data = $categoryForm->getData();

                if (! $category) {
                    $category = new Category();
                    $category->set('cid', $championship->need('cid'));
                }

                $category->set('name', $data['caf-name']);
                $category->set('discipline', $data['caf-discipline']);
                $category->set('gender', $data['caf-gender']);
                $category->set('group_size_max', $data['caf-group-size-max']);
                $category->set('advance_per_group', $data['caf-advance-per-group']);
                $category->set('status', $data['caf-status']);

                $categoryManager->save($category);

                $this->flashMessenger()->addSuccessMessage('Category has been saved');

                return $this->redirect()->toRoute('backend/championship/category-edit', array('catid' => $category->need('catid')));
            }
        } else {
            if ($category) {
                $categoryForm->setData(array(
                    'caf-name' => $category->get('name'),
                    'caf-discipline' => $category->get('discipline'),
                    'caf-gender' => $category->get('gender'),
                    'caf-group-size-max' => $category->get('group_size_max', 6),
                    'caf-advance-per-group' => $category->get('advance_per_group', 2),
                    'caf-status' => $category->get('status', 'enabled'),
                ));
            } else {
                $categoryForm->setData(array(
                    'caf-group-size-max' => 6,
                    'caf-advance-per-group' => 2,
                    'caf-status' => 'enabled',
                ));
            }
        }

        $groups = array();
        $participants = array();
        $koGenerated = false;

        if ($category) {
            $groups = $groupManager->getByCategory($category);
            $participants = $participantManager->getByCategory($category);
            $koGenerated = $bracketService->isGenerated($category);

            foreach ($participants as $participant) {
                $participant->setExtra('label', $this->championshipParticipantLabel($participant, $userManager));
            }

            foreach ($groups as $group) {
                $group->setExtra('memberCount', count($groupManager->getMemberPids($group)));
                $group->setExtra('matchesGenerated', (bool) $matchManager->getByGroup($group));
            }
        }

        return array(
            'championship' => $championship,
            'category' => $category,
            'categoryForm' => $categoryForm,
            'groups' => $groups,
            'participants' => $participants,
            'koGenerated' => $koGenerated,
        );
    }

    public function categoryDeleteAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');

        $catid = $this->params()->fromRoute('catid');

        $category = $categoryManager->get($catid);

        if ($this->params()->fromQuery('confirmed') == 'true') {
            $cid = $category->need('cid');

            $categoryManager->delete($category);

            $this->flashMessenger()->addSuccessMessage('Category has been deleted');

            return $this->redirect()->toRoute('backend/championship/edit', array('cid' => $cid));
        }

        return array(
            'category' => $category,
        );
    }

    public function groupEditAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $groupManager = $serviceManager->get('Championship\Manager\GroupManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $formElementManager = $serviceManager->get('FormElementManager');

        $gid = $this->params()->fromRoute('gid');

        if ($gid) {
            $group = $groupManager->get($gid);
            $category = $categoryManager->get($group->need('catid'));
        } else {
            $catid = $this->params()->fromQuery('catid');

            if (! $catid) {
                throw new RuntimeException('A category id is required to create a new group');
            }

            $category = $categoryManager->get($catid);
            $group = null;
        }

        $maxSize = $category->get('group_size_max', 6);

        $participants = $participantManager->getByCategory($category);

        $otherGroupsMemberPids = array();

        foreach ($groupManager->getByCategory($category) as $otherGroup) {
            if ($group && $otherGroup->need('gid') == $group->need('gid')) {
                continue;
            }

            foreach ($groupManager->getMemberPids($otherGroup) as $pid) {
                $otherGroupsMemberPids[] = $pid;
            }
        }

        $memberOptions = array();

        foreach ($participants as $participant) {
            if (in_array($participant->need('pid'), $otherGroupsMemberPids)) {
                continue;
            }

            $memberOptions[$participant->need('pid')] = $this->championshipParticipantLabel($participant, $userManager);
        }

        $groupForm = $formElementManager->get('Backend\Form\Championship\GroupForm');
        $groupForm->setMemberOptions($memberOptions, $maxSize);

        if ($this->getRequest()->isPost()) {
            $groupForm->setData($this->params()->fromPost());

            if ($groupForm->isValid()) {
                $data = $groupForm->getData();

                try {
                    if (! $group) {
                        $group = new Group();
                        $group->set('catid', $category->need('catid'));
                    }

                    $group->set('name', $data['gf-name']);

                    $groupManager->save($group);

                    $currentMemberPids = $groupManager->getMemberPids($group);
                    $desiredMemberPids = array_map('intval', (array) $data['gf-members']);

                    foreach (array_diff($currentMemberPids, $desiredMemberPids) as $pid) {
                        $groupManager->removeMember($group, $participantManager->get($pid));
                    }

                    foreach (array_diff($desiredMemberPids, $currentMemberPids) as $pid) {
                        $groupManager->addMember($group, $participantManager->get($pid), $maxSize);
                    }

                    $this->flashMessenger()->addSuccessMessage('Group has been saved');

                    return $this->redirect()->toRoute('backend/championship/category-edit', array('catid' => $category->need('catid')));
                } catch (RuntimeException $e) {
                    $this->flashMessenger()->addErrorMessage($e->getMessage());
                }
            }
        } else {
            if ($group) {
                $groupForm->setData(array(
                    'gf-name' => $group->get('name'),
                    'gf-members' => $groupManager->getMemberPids($group),
                ));
            }
        }

        return array(
            'category' => $category,
            'group' => $group,
            'groupForm' => $groupForm,
        );
    }

    public function groupDeleteAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $groupManager = $serviceManager->get('Championship\Manager\GroupManager');

        $gid = $this->params()->fromRoute('gid');

        $group = $groupManager->get($gid);

        if ($this->params()->fromQuery('confirmed') == 'true') {
            $catid = $group->need('catid');

            $groupManager->delete($group);

            $this->flashMessenger()->addSuccessMessage('Group has been deleted');

            return $this->redirect()->toRoute('backend/championship/category-edit', array('catid' => $catid));
        }

        return array(
            'group' => $group,
        );
    }

    public function groupMatchesGenerateAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $groupManager = $serviceManager->get('Championship\Manager\GroupManager');
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');

        $gid = $this->params()->fromRoute('gid');

        $group = $groupManager->get($gid);
        $category = $categoryManager->get($group->need('catid'));

        $memberPids = $groupManager->getMemberPids($group);

        if (count($memberPids) < 2) {
            $this->flashMessenger()->addErrorMessage('A group needs at least two members to generate matches');
        } else {
            try {
                if ($matchManager->getByGroup($group)) {
                    $matchManager->regenerateGroupMatches($category, $group, $memberPids);

                    $this->flashMessenger()->addSuccessMessage('Group matches have been regenerated');
                } else {
                    $matchManager->generateGroupMatches($category, $group, $memberPids);

                    $this->flashMessenger()->addSuccessMessage('Group matches have been generated');
                }
            } catch (RuntimeException $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }
        }

        return $this->redirect()->toRoute('backend/championship/category-edit', array('catid' => $category->need('catid')));
    }

    public function participantAddAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $formElementManager = $serviceManager->get('FormElementManager');

        $catid = $this->params()->fromQuery('catid') ?: $this->params()->fromPost('pf-catid');

        if (! $catid) {
            throw new RuntimeException('A category id is required to add a participant');
        }

        $category = $categoryManager->get($catid);

        $alreadyRegisteredUids = array();

        foreach ($participantManager->getByCategory($category) as $participant) {
            $alreadyRegisteredUids[] = $participant->need('uid');

            if ($participant->get('partner_uid')) {
                $alreadyRegisteredUids[] = $participant->get('partner_uid');
            }
        }

        $userOptions = array();

        foreach ($userManager->getBy(array('status' => array('enabled', 'assist', 'admin')), 'alias ASC') as $candidate) {
            $candidateUid = $candidate->need('uid');

            if (in_array($candidateUid, $alreadyRegisteredUids)) {
                continue;
            }

            $userOptions[$candidateUid] = $candidate->need('alias');
        }

        $isDouble = $category->need('discipline') == 'double';

        $participantForm = $formElementManager->get('Backend\Form\Championship\ParticipantForm');
        $participantForm->setUserOptions($userOptions);

        if ($isDouble) {
            $participantForm->setPartnerOptions($userOptions);
        } else {
            $participantForm->removePartnerField();
        }

        if ($this->getRequest()->isPost()) {
            $participantForm->setData($this->params()->fromPost());

            if ($participantForm->isValid()) {
                $data = $participantForm->getData();

                $uid = $data['pf-uid'];
                $partnerUid = $isDouble ? ($data['pf-partner-uid'] ?: null) : null;

                if ($isDouble && ! $partnerUid) {
                    $this->flashMessenger()->addErrorMessage('Please select a partner');
                } else if ($isDouble && $uid == $partnerUid) {
                    $this->flashMessenger()->addErrorMessage('Player and partner must be different');
                } else if ($isDouble && ! $this->championshipIsValidPairGender($category, $uid, $partnerUid, $userManager)) {
                    $this->flashMessenger()->addErrorMessage($this->championshipPairGenderErrorMessage($category));
                } else {
                    try {
                        $participantManager->register($category, $uid, $partnerUid);

                        $this->flashMessenger()->addSuccessMessage('Player has been registered');

                        return $this->redirect()->toRoute('backend/championship/category-edit', array('catid' => $catid));
                    } catch (RuntimeException $e) {
                        $this->flashMessenger()->addErrorMessage($e->getMessage());
                    }
                }
            }
        } else {
            $participantForm->get('pf-catid')->setValue($catid);
        }

        return array(
            'category' => $category,
            'participantForm' => $participantForm,
        );
    }

    public function participantDeleteAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');

        $pid = $this->params()->fromRoute('pid');

        $participant = $participantManager->get($pid);

        $catid = $participant->need('catid');

        $hasMatches = (bool) $matchManager->getByParticipant($catid, $participant->need('pid'));

        if (! $hasMatches && $this->params()->fromQuery('confirmed') == 'true') {
            $participantManager->delete($participant);

            $this->flashMessenger()->addSuccessMessage('Participant has been removed');

            return $this->redirect()->toRoute('backend/championship/category-edit', array('catid' => $catid));
        }

        $participant->setExtra('label', $this->championshipParticipantLabel($participant, $userManager));

        return array(
            'category' => $categoryManager->get($catid),
            'participant' => $participant,
            'hasMatches' => $hasMatches,
        );
    }

    public function bracketAction()
    {
        $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $championshipManager = $serviceManager->get('Championship\Manager\ChampionshipManager');
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $groupManager = $serviceManager->get('Championship\Manager\GroupManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');
        $standingsService = $serviceManager->get('Championship\Service\StandingsService');
        $bracketService = $serviceManager->get('Championship\Service\BracketService');
        $userManager = $serviceManager->get('User\Manager\UserManager');

        $catid = $this->params()->fromRoute('catid');

        $category = $categoryManager->get($catid);
        $championship = $championshipManager->get($category->need('cid'));

        if ($this->getRequest()->isPost()) {
            try {
                $bracketService->generate($category, (bool) $this->params()->fromPost('regenerate'));

                $this->flashMessenger()->addSuccessMessage('Knock-out stage has been generated');
            } catch (RuntimeException $e) {
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            }

            return $this->redirect()->toRoute('backend/championship/bracket', array('catid' => $catid));
        }

        $participantLabels = array();

        foreach ($participantManager->getByCategory($category) as $participant) {
            $participantLabels[$participant->need('pid')] = $this->championshipParticipantLabel($participant, $userManager);
        }

        $groups = $groupManager->getByCategory($category);

        $standingsByGroup = array();
        $groupMatchesByGroup = array();

        foreach ($groups as $group) {
            $standingsByGroup[$group->need('gid')] = $standingsService->getStandings($group);
            $groupMatchesByGroup[$group->need('gid')] = $matchManager->getByGroup($group);
        }

        $koMatches = $matchManager->getBy(array('catid' => $catid, 'round_type' => 'ko'));

        return array(
            'championship' => $championship,
            'category' => $category,
            'groups' => $groups,
            'standingsByGroup' => $standingsByGroup,
            'groupMatchesByGroup' => $groupMatchesByGroup,
            'koMatches' => $koMatches,
            'participantLabels' => $participantLabels,
            'koGenerated' => $bracketService->isGenerated($category),
        );
    }

    public function matchEditAction()
    {
        $user = $this->authorize('admin.championship');

        $serviceManager = @$this->getServiceLocator();
        $categoryManager = $serviceManager->get('Championship\Manager\CategoryManager');
        $participantManager = $serviceManager->get('Championship\Manager\ParticipantManager');
        $matchManager = $serviceManager->get('Championship\Manager\FixtureManager');
        $userManager = $serviceManager->get('User\Manager\UserManager');
        $formElementManager = $serviceManager->get('FormElementManager');

        $mid = $this->params()->fromRoute('mid');

        $match = $matchManager->get($mid);
        $category = $categoryManager->get($match->need('catid'));

        $participantLabels = array();

        foreach ($participantManager->getByCategory($category) as $participant) {
            $participantLabels[$participant->need('pid')] = $this->championshipParticipantLabel($participant, $userManager);
        }

        $matchForm = $formElementManager->get('Backend\Form\Championship\MatchForm');
        $matchForm->setParticipantOptions($participantLabels);

        if ($this->getRequest()->isPost()) {
            $matchForm->setData($this->params()->fromPost());

            if ($matchForm->isValid()) {
                $data = $matchForm->getData();

                try {
                    $match->set('participant1_pid', $data['mf-participant1'] ?: null);
                    $match->set('participant2_pid', $data['mf-participant2'] ?: null);

                    $matchManager->save($match);

                    if ($data['mf-walkover-winner']) {
                        $matchManager->recordWalkover($match, $data['mf-walkover-winner'], $user->need('uid'));
                    } else {
                        $sets = array();

                        foreach (array(1, 2, 3) as $setNumber) {
                            $score1 = $data['mf-set' . $setNumber . '-p1'];
                            $score2 = $data['mf-set' . $setNumber . '-p2'];

                            if ($score1 !== '' && $score2 !== '') {
                                $sets[] = array(
                                    'score_participant1' => (int) $score1,
                                    'score_participant2' => (int) $score2,
                                );
                            }
                        }

                        if ($sets) {
                            $matchManager->recordResult($match, $sets, $user->need('uid'));
                        }
                    }

                    $this->flashMessenger()->addSuccessMessage('Match has been saved');

                    return $this->redirect()->toRoute('backend/championship/bracket', array('catid' => $category->need('catid')));
                } catch (RuntimeException $e) {
                    $this->flashMessenger()->addErrorMessage($e->getMessage());
                }
            }
        } else {
            $formData = array(
                'mf-participant1' => $match->get('participant1_pid', ''),
                'mf-participant2' => $match->get('participant2_pid', ''),
                'mf-walkover-winner' => $match->get('status') == 'walkover' ? $match->get('winner_pid') : '',
            );

            $setNumber = 1;

            foreach ($matchManager->getSets($match) as $set) {
                $formData['mf-set' . $setNumber . '-p1'] = $set->get('score_participant1');
                $formData['mf-set' . $setNumber . '-p2'] = $set->get('score_participant2');

                $setNumber++;
            }

            $matchForm->setData($formData);
        }

        return array(
            'category' => $category,
            'match' => $match,
            'matchForm' => $matchForm,
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
     * Whether the passed pair matches the given category's gender: "mixed" requires one man and
     * one woman, "men"/"women" requires both players to be of that gender. Unknown genders on
     * either side (e.g. family/firm accounts) are not restricted, so misconfigured accounts don't
     * get blocked outright.
     *
     * @param \Championship\Entity\Category $category
     * @param int $uid
     * @param int $partnerUid
     * @param \User\Manager\UserManager $userManager
     * @return boolean
     */
    protected function championshipIsValidPairGender($category, $uid, $partnerUid, $userManager)
    {
        $gender1 = $userManager->get($uid)->getMeta('gender');
        $gender2 = $userManager->get($partnerUid)->getMeta('gender');

        if (! ($gender1 && $gender2)) {
            return true;
        }

        switch ($category->need('gender')) {
            case 'mixed':
                return ($gender1 == 'male' && $gender2 == 'female') || ($gender1 == 'female' && $gender2 == 'male');
            case 'men':
                return $gender1 == 'male' && $gender2 == 'male';
            case 'women':
                return $gender1 == 'female' && $gender2 == 'female';
            default:
                return true;
        }
    }

    /**
     * Gets the error message to show when a pair does not match a category's gender requirement.
     *
     * @param \Championship\Entity\Category $category
     * @return string
     */
    protected function championshipPairGenderErrorMessage($category)
    {
        switch ($category->need('gender')) {
            case 'mixed':
                return 'A mixed pair requires one man and one woman';
            case 'men':
                return 'This category requires two men';
            case 'women':
                return 'This category requires two women';
            default:
                return 'Invalid pair for this category';
        }
    }

}
