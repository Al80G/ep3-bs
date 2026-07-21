<?php

namespace Championship\Form;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class RegistrationForm extends Form
{

    public function init()
    {
        $this->setName('rf');

        $this->add(array(
            'name' => 'rf-catid',
            'type' => 'Hidden',
        ));

        $this->add(array(
            'name' => 'rf-partner-uid',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'rf-partner-uid',
                'style' => 'width: 260px;',
            ),
            'options' => array(
                'label' => 'Partner',
                'value_options' => array(),
            ),
        ));

        $this->add(array(
            'name' => 'rf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Register',
                'id' => 'rf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $this->setInputFilter($factory->createInputFilter(array(
            'rf-catid' => array(
                'validators' => array(
                    array(
                        'name' => 'Digits',
                        'options' => array('message' => 'Invalid category'),
                    ),
                ),
            ),
            'rf-partner-uid' => array(
                'required' => false,
            ),
        )));
    }

    /**
     * Sets the eligible partners for this registration (only relevant for double/mixed categories).
     *
     * @param array $valueOptions       uid => label
     */
    public function setPartnerOptions(array $valueOptions)
    {
        $options = array('' => '-- select partner --') + $valueOptions;

        $this->get('rf-partner-uid')->setValueOptions($options);
    }

    /**
     * Removes the partner field entirely (for single categories).
     */
    public function removePartnerField()
    {
        $this->remove('rf-partner-uid');
    }

}
