<?php

namespace Championship\Service;

use User\Service\MailService as UserMailService;

class MailService
{

    protected $userMailService;

    /**
     * Creates a new championship mail service object.
     *
     * @param UserMailService $userMailService
     */
    public function __construct(UserMailService $userMailService)
    {
        $this->userMailService = $userMailService;
    }

    /**
     * Sends the same subject/text to each of the passed users (skipping any without an email address).
     *
     * @param array $users      User\Entity\User entities
     * @param string $subject
     * @param string $text
     * @return int              Number of emails actually sent
     */
    public function notify(array $users, $subject, $text)
    {
        $sent = 0;

        foreach ($users as $user) {
            if (! $user->get('email')) {
                continue;
            }

            try {
                $this->userMailService->send($user, $subject, $text);

                $sent++;
            } catch (\Exception $e) {
                // Skip this recipient and continue with the rest of the batch.
            }
        }

        return $sent;
    }

}
