<?php

namespace Backend\Form\Championship;

use Zend\Form\Form;

class ConfigForm extends Form
{

    public function init()
    {
        $this->setName('ccf');

        $this->add(array(
            'name' => 'ccf-enabled',
            'type' => 'Checkbox',
            'attributes' => array(
                'id' => 'ccf-enabled',
            ),
            'options' => array(
                'label' => 'Show the championship menu item to members',
                'checked_value' => 'true',
                'unchecked_value' => 'false',
            ),
        ));

        $this->add(array(
            'name' => 'ccf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save',
                'id' => 'ccf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));
    }

}
