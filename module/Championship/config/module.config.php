<?php

return array(
    'service_manager' => array(
        'factories' => array(
            'Championship\Table\ChampionshipTable' => 'Championship\Table\ChampionshipTableFactory',
            'Championship\Table\CategoryTable' => 'Championship\Table\CategoryTableFactory',
            'Championship\Table\ParticipantTable' => 'Championship\Table\ParticipantTableFactory',
            'Championship\Table\GroupTable' => 'Championship\Table\GroupTableFactory',
            'Championship\Table\GroupMemberTable' => 'Championship\Table\GroupMemberTableFactory',
            'Championship\Table\FixtureTable' => 'Championship\Table\FixtureTableFactory',
            'Championship\Table\Fixture\SetScoreTable' => 'Championship\Table\Fixture\SetScoreTableFactory',

            'Championship\Manager\ChampionshipManager' => 'Championship\Manager\ChampionshipManagerFactory',
            'Championship\Manager\CategoryManager' => 'Championship\Manager\CategoryManagerFactory',
            'Championship\Manager\ParticipantManager' => 'Championship\Manager\ParticipantManagerFactory',
            'Championship\Manager\GroupManager' => 'Championship\Manager\GroupManagerFactory',
            'Championship\Manager\FixtureManager' => 'Championship\Manager\FixtureManagerFactory',
            'Championship\Manager\Fixture\SetScoreManager' => 'Championship\Manager\Fixture\SetScoreManagerFactory',

            'Championship\Service\StandingsService' => 'Championship\Service\StandingsServiceFactory',
            'Championship\Service\BracketService' => 'Championship\Service\BracketServiceFactory',
            'Championship\Service\MatchResultValidator' => 'Championship\Service\MatchResultValidatorFactory',
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
