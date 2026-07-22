<?php

namespace Backend\Form\Championship;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class CategoryForm extends Form
{

    public function init()
    {
        $this->setName('caf');

        $this->add(array(
            'name' => 'caf-name',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'caf-name',
                'style' => 'width: 220px',
            ),
            'options' => array(
                'label' => 'Name',
                'notes' => 'E.g. "Einzel Herren"',
            ),
        ));

        $this->add(array(
            'name' => 'caf-discipline',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'caf-discipline',
                'style' => 'width: 160px;',
            ),
            'options' => array(
                'label' => 'Discipline',
                'value_options' => array(
                    'single' => 'Single',
                    'double' => 'Double',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'caf-gender',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'caf-gender',
                'style' => 'width: 160px;',
            ),
            'options' => array(
                'label' => 'Gender',
                'value_options' => array(
                    'men' => 'Men',
                    'women' => 'Women',
                    'mixed' => 'Mixed',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'caf-group-size-max',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'caf-group-size-max',
                'style' => 'width: 60px;',
            ),
            'options' => array(
                'label' => 'Max. group size',
            ),
        ));

        $this->add(array(
            'name' => 'caf-advance-per-group',
            'type' => 'Text',
            'attributes' => array(
                'id' => 'caf-advance-per-group',
                'style' => 'width: 60px;',
            ),
            'options' => array(
                'label' => 'Advancing per group',
                'notes' => 'How many players/pairs of each group advance to the knock-out stage',
            ),
        ));

        $this->add(array(
            'name' => 'caf-admin-assigns-partners',
            'type' => 'Checkbox',
            'attributes' => array(
                'id' => 'caf-admin-assigns-partners',
            ),
            'options' => array(
                'label' => 'Partners are assigned by the admin',
                'checked_value' => 'true',
                'unchecked_value' => 'false',
                'notes' => 'Players only register for the category (without picking a partner); you pair them up into teams afterwards',
            ),
        ));

        $this->add(array(
            'name' => 'caf-status',
            'type' => 'Select',
            'attributes' => array(
                'id' => 'caf-status',
                'style' => 'width: 160px;',
            ),
            'options' => array(
                'label' => 'Status',
                'value_options' => array(
                    'enabled' => 'Enabled',
                    'disabled' => 'Disabled',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'caf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save',
                'id' => 'caf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $this->setInputFilter($factory->createInputFilter(array(
            'caf-name' => array(
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
            'caf-group-size-max' => array(
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
                    array(
                        'name' => 'Digits',
                        'options' => array(
                            'message' => 'Please type a number here',
                        ),
                    ),
                ),
            ),
            'caf-advance-per-group' => array(
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
                    array(
                        'name' => 'Digits',
                        'options' => array(
                            'message' => 'Please type a number here',
                        ),
                    ),
                ),
            ),
        )));
    }

}
