<?php

return array(
    'router' => array(
        'routes' => array(
            'championship' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/championship',
                    'defaults' => array(
                        'controller' => 'Championship\Controller\Championship',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'category' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/category/:catid',
                            'defaults' => array(
                                'action' => 'category',
                            ),
                            'constraints' => array(
                                'catid' => '[0-9]+',
                            ),
                        ),
                    ),
                    'register' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/register',
                            'defaults' => array(
                                'action' => 'register',
                            ),
                        ),
                    ),
                    'my' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/my',
                            'defaults' => array(
                                'action' => 'my',
                            ),
                        ),
                    ),
                    'match-result' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/match-result/:mid',
                            'defaults' => array(
                                'action' => 'matchResult',
                            ),
                            'constraints' => array(
                                'mid' => '[0-9]+',
                            ),
                        ),
                    ),
                    'withdraw' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/withdraw/:pid',
                            'defaults' => array(
                                'action' => 'withdraw',
                            ),
                            'constraints' => array(
                                'pid' => '[0-9]+',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'Championship\Controller\Championship' => 'Championship\Controller\ChampionshipController',
        ),
    ),

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
