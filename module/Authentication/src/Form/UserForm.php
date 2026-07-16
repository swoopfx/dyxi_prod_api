<?php

namespace Authentication\Form;

use Laminas\Form\Form;

class UserForm extends Form
{
    public function init()
    {

        $this->setAttributes([
            'action' => '',
            'method' => 'POST',
            "autocomplete" => "off"
        ]);
        $this->addFields();
        $this->addCommon();
    }

    public function addFields()
    {
        $this->add([
            'name' => 'userBasicField',
            'type' => 'CsnUser\Form\Fieldset\UserBasicFieldset',
            'options' => [
                'use_as_base_fieldset' => true
            ]
        ]);

        $this->add([
            'name' => 'securityQuestion',
            'type' => '',
        ]);
    }

    public function addCommon()
    {
        $this->form->add([
            'name' => 'csrf',
            'type' => 'Laminas\Form\Element\Csrf',
            'options' => [
                'csrf_options' => [
                    'timeout' => 600
                ]
            ]
        ]);



        $this->form->add([
            'name' => 'submit',
            'type' => 'Laminas\Form\Element\Submit',
            'attributes' => [
                'type' => 'submit'
            ]
        ]);
    }
}
