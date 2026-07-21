<?php

namespace Backend\Form\Championship;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class MatchForm extends Form
{

    public function init()
    {
        $this->setName('mf');

        $this->add(array(
            'name' => 'mf-participant1',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'mf-participant1',
                'style' => 'width: 220px;',
            ),
            'options' => array(
                'label' => 'Participant 1',
                'value_options' => array(),
            ),
        ));

        $this->add(array(
            'name' => 'mf-participant2',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'mf-participant2',
                'style' => 'width: 220px;',
            ),
            'options' => array(
                'label' => 'Participant 2',
                'value_options' => array(),
            ),
        ));

        foreach (array(1, 2, 3) as $setNumber) {
            $this->add(array(
                'name' => 'mf-set' . $setNumber . '-p1',
                'type' => 'Text',
                'attributes' => array(
                    'id' => 'mf-set' . $setNumber . '-p1',
                    'style' => 'width: 40px;',
                ),
                'options' => array(
                    'label' => sprintf('Set %d', $setNumber),
                ),
            ));

            $this->add(array(
                'name' => 'mf-set' . $setNumber . '-p2',
                'type' => 'Text',
                'attributes' => array(
                    'id' => 'mf-set' . $setNumber . '-p2',
                    'style' => 'width: 40px;',
                ),
                'options' => array(
                    'label' => '',
                ),
            ));
        }

        $this->add(array(
            'name' => 'mf-walkover-winner',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'mf-walkover-winner',
                'style' => 'width: 220px;',
            ),
            'options' => array(
                'label' => 'Walkover in favour of',
                'notes' => 'Use this instead of set scores if the match was decided without being played',
                'value_options' => array(
                    '' => '-- no walkover --',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'mf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save',
                'id' => 'mf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $inputSpecs = array(
            'mf-participant1' => array('required' => false),
            'mf-participant2' => array('required' => false),
            'mf-walkover-winner' => array('required' => false),
        );

        foreach (array(1, 2, 3) as $setNumber) {
            $inputSpecs['mf-set' . $setNumber . '-p1'] = array(
                'required' => false,
                'filters' => array(array('name' => 'StringTrim')),
                'validators' => array(
                    array(
                        'name' => 'Digits',
                        'options' => array('message' => 'Please type a number here'),
                    ),
                ),
            );

            $inputSpecs['mf-set' . $setNumber . '-p2'] = array(
                'required' => false,
                'filters' => array(array('name' => 'StringTrim')),
                'validators' => array(
                    array(
                        'name' => 'Digits',
                        'options' => array('message' => 'Please type a number here'),
                    ),
                ),
            );
        }

        $this->setInputFilter($factory->createInputFilter($inputSpecs));
    }

    /**
     * Sets the participants that may be picked for this match's slots (including a bye option).
     *
     * @param array $valueOptions       pid => label
     */
    public function setParticipantOptions(array $valueOptions)
    {
        $options = array('' => '-- bye / not yet decided --') + $valueOptions;

        $this->get('mf-participant1')->setValueOptions($options);
        $this->get('mf-participant2')->setValueOptions($options);
        $this->get('mf-walkover-winner')->setValueOptions(array('' => '-- no walkover --') + $valueOptions);
    }

}
