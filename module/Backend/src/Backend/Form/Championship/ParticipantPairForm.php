<?php

namespace Backend\Form\Championship;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class ParticipantPairForm extends Form
{

    public function init()
    {
        $this->setName('ppf');

        $this->add(array(
            'name' => 'ppf-pid',
            'type' => 'Hidden',
        ));

        $this->add(array(
            'name' => 'ppf-partner-pid',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'ppf-partner-pid',
                'style' => 'width: 260px;',
            ),
            'options' => array(
                'label' => 'Partner',
                'value_options' => array(),
            ),
        ));

        $this->add(array(
            'name' => 'ppf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save',
                'id' => 'ppf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $this->setInputFilter($factory->createInputFilter(array(
            'ppf-pid' => array(
                'validators' => array(
                    array(
                        'name' => 'Digits',
                        'options' => array('message' => 'Invalid participant'),
                    ),
                ),
            ),
            'ppf-partner-pid' => array(
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array('message' => 'Please select a partner'),
                    ),
                ),
            ),
        )));
    }

    /**
     * Sets the eligible pairing candidates (other unpaired participants of the same category).
     *
     * @param array $valueOptions       pid => label
     */
    public function setPartnerOptions(array $valueOptions)
    {
        $options = array('' => '-- select partner --') + $valueOptions;

        $this->get('ppf-partner-pid')->setValueOptions($options);
    }

}
