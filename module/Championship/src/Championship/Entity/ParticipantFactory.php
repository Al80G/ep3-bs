<?php

namespace Championship\Entity;

use Base\Entity\AbstractEntityFactory;

class ParticipantFactory extends AbstractEntityFactory
{

    protected static $entityClass = 'Championship\Entity\Participant';
    protected static $entityPrimary = 'pid';

}
