<?php

namespace Backend\Form\Championship;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class ParticipantForm extends Form
{

    public function init()
    {
        $this->setName('pf');

        $this->add(array(
            'name' => 'pf-catid',
            'type' => 'Hidden',
        ));

        $this->add(array(
            'name' => 'pf-uid',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'pf-uid',
                'style' => 'width: 260px;',
            ),
            'options' => array(
                'label' => 'Player',
                'value_options' => array(),
            ),
        ));

        $this->add(array(
            'name' => 'pf-partner-uid',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'pf-partner-uid',
                'style' => 'width: 260px;',
            ),
            'options' => array(
                'label' => 'Partner',
                'value_options' => array(),
            ),
        ));

        $this->add(array(
            'name' => 'pf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Register',
                'id' => 'pf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $this->setInputFilter($factory->createInputFilter(array(
            'pf-catid' => array(
                'validators' => array(
                    array(
                        'name' => 'Digits',
                        'options' => array('message' => 'Invalid category'),
                    ),
                ),
            ),
            'pf-uid' => array(
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array('message' => 'Please select a player'),
                    ),
                ),
            ),
            'pf-partner-uid' => array(
                'required' => false,
            ),
        )));
    }

    /**
     * Sets the eligible players for this registration.
     *
     * @param array $valueOptions       uid => label
     */
    public function setUserOptions(array $valueOptions)
    {
        $options = array('' => '-- select player --') + $valueOptions;

        $this->get('pf-uid')->setValueOptions($options);
    }

    /**
     * Sets the eligible partners for this registration (only relevant for double/mixed categories).
     *
     * @param array $valueOptions       uid => label
     */
    public function setPartnerOptions(array $valueOptions)
    {
        $options = array('' => '-- select partner --') + $valueOptions;

        $this->get('pf-partner-uid')->setValueOptions($options);
    }

    /**
     * Removes the partner field entirely (for single categories).
     */
    public function removePartnerField()
    {
        $this->remove('pf-partner-uid');
    }

}
