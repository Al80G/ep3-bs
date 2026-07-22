<?php

namespace Backend\Form\Championship;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class EditForm extends Form
{

    public function init()
    {
        $this->setName('chf');

        $this->add(array(
            'name' => 'chf-name',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'chf-name',
                'style' => 'width: 260px',
            ),
            'options' => array(
                'label' => 'Name',
                'notes' => 'E.g. "Vereinsmeisterschaft 2026"',
            ),
        ));

        $this->add(array(
            'name' => 'chf-status',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'chf-status',
                'style' => 'width: 220px;',
            ),
            'options' => array(
                'label' => 'Status',
                'value_options' => array(
                    'draft' => 'Draft (not yet visible)',
                    'open' => 'Open for registration',
                    'running' => 'Running',
                    'finished' => 'Finished',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'chf-registration-start',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'chf-registration-start',
                'class' => 'datepicker',
                'style' => 'width: 110px;',
            ),
            'options' => array(
                'label' => 'Registration open from',
            ),
        ));

        $this->add(array(
            'name' => 'chf-registration-end',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'chf-registration-end',
                'class' => 'datepicker',
                'style' => 'width: 110px;',
            ),
            'options' => array(
                'label' => 'Registration open until',
            ),
        ));

        $this->add(array(
            'name' => 'chf-info-text',
            'type' => 'Textarea',
            'attributes' => array(
                'id' => 'chf-info-text',
                'class' => 'wysiwyg-editor',
                'style' => 'width: 500px; height: 120px;',
            ),
            'options' => array(
                'label' => 'Info text',
                'notes' => 'Shown to members below the category table on the championship overview page (max. 500 characters)',
            ),
        ));

        $this->add(array(
            'name' => 'chf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save',
                'id' => 'chf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $this->setInputFilter($factory->createInputFilter(array(
            'chf-name' => array(
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
            'chf-registration-start' => array(
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
            ),
            'chf-registration-end' => array(
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
            ),
            'chf-info-text' => array(
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array(
                            'max' => 500,
                            'messages' => array(
                                \Zend\Validator\StringLength::TOO_LONG => 'Please use at most %max% characters',
                            ),
                        ),
                    ),
                ),
            ),
        )));
    }

}
