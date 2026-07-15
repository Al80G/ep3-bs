<?php

namespace Backend\Form\Championship;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class GroupForm extends Form
{

    public function init()
    {
        $this->setName('gf');

        $this->add(array(
            'name' => 'gf-name',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'gf-name',
                'style' => 'width: 160px',
            ),
            'options' => array(
                'label' => 'Name',
                'notes' => 'E.g. "Gruppe A"',
            ),
        ));

        $this->add(array(
            'name' => 'gf-members',
            'type' => 'MultiCheckbox',
            'attributes' => array(
                'id' => 'gf-members',
            ),
            'options' => array(
                'label' => 'Members',
                'value_options' => array(),
            ),
        ));

        $this->add(array(
            'name' => 'gf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save',
                'id' => 'gf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $this->setInputFilter($factory->createInputFilter(array(
            'gf-name' => array(
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'message' => 'Please type something here',
                        ),
                        'break_chain_on_failure' => true,
                    ),
                ),
            ),
            'gf-members' => array(
                'required' => false,
            ),
        )));
    }

    /**
     * Sets the participants that may be picked as members of this group.
     *
     * @param array $valueOptions       pid => label
     * @param int $maxSize
     */
    public function setMemberOptions(array $valueOptions, $maxSize)
    {
        $this->get('gf-members')->setValueOptions($valueOptions);
        $this->get('gf-members')->setLabel(sprintf('Members (max. %d)', $maxSize));
    }

}
