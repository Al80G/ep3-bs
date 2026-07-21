<?php

namespace Championship\Form;

use Zend\Form\Form;
use Zend\InputFilter\Factory;

class MatchResultForm extends Form
{

    public function init()
    {
        $this->setName('mrf');

        foreach (array(1, 2, 3) as $setNumber) {
            $this->add(array(
                'name' => 'mrf-set' . $setNumber . '-p1',
                'type' => 'Text',
                'attributes' => array(
                    'id' => 'mrf-set' . $setNumber . '-p1',
                    'style' => 'width: 40px;',
                ),
                'options' => array(
                    'label' => sprintf('Set %d', $setNumber),
                ),
            ));

            $this->add(array(
                'name' => 'mrf-set' . $setNumber . '-p2',
                'type' => 'Text',
                'attributes' => array(
                    'id' => 'mrf-set' . $setNumber . '-p2',
                    'style' => 'width: 40px;',
                ),
                'options' => array(
                    'label' => '',
                ),
            ));
        }

        $this->add(array(
            'name' => 'mrf-submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Save result',
                'id' => 'mrf-submit',
                'class' => 'default-button',
                'style' => 'width: 200px;',
            ),
        ));

        /* Input filters */

        $factory = new Factory();

        $inputSpecs = array();

        foreach (array(1, 2, 3) as $setNumber) {
            foreach (array('p1', 'p2') as $slot) {
                $inputSpecs['mrf-set' . $setNumber . '-' . $slot] = array(
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
        }

        $this->setInputFilter($factory->createInputFilter($inputSpecs));
    }

}
