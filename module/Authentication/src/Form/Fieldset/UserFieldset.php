<?php

namespace Authentication\Form\Fieldset;

use Authentication\Entity\User;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use DoctrineModule\Validator\NoObjectExists;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Identical;
use Laminas\Validator\StringLength;

class UserFieldset extends Fieldset implements InputFilterProviderInterface
{
    private $entityManager;


    public function init()
    {
        $hydrator  = new DoctrineObject($this->entityManger);
        $this->setHydrator($hydrator)->setObject(new User());

        $this->add([
            'name' => 'username',
            'type' => 'text',
            'options' => [
                'label' => 'Staff Username',
                'label_attributes' => [
                    'class' => 'control-label col-md-3 col-sm-3 col-xs-12'
                ]
            ],
            'attributes' => [
                'class' => 'form-control col-md-9 col-xs-12',
                'id' => 'username',
                'required' => 'required',
                'title' => 'Provide Staffs phone number'
            ]
        ]);
        $this->add([
            'name' => 'email',
            'type' => 'Laminas\Form\Element\Email',
            'options' => [
                'label' => 'Staff Email',
                'label_attributes' => [
                    'class' => 'control-label col-md-3 col-sm-3 col-xs-12'
                ]
            ],
            'attributes' => [
                'id' => 'staff_email',
                'required' => 'required',
                'class' => 'form-control col-md-9 col-xs-12',
                'title' => 'Provide Email accessible by the staff',
                'placeholder' => 'az@xyz.com'
            ]
        ]);
        $this->add([
            'name' => 'password',
            'type' => 'Laminas\Form\Element\Password',
            'options' => [
                'label' => 'Proposed Password',
                'label_attributes' => [
                    'class' => 'control-label col-md-3 col-sm-3 col-xs-12'
                ]
            ],
            'attributes' => [
                'id' => 'password',
                'required' => 'required',
                'class' => 'form-control col-md-9 col-xs-12'
            ]
        ]);

        $this->add([
            'name' => 'passwordVerify',
            'type' => 'Laminas\Form\Element\Password',
            'options' => [
                'label' => 'Confirm Password',
                'label_attributes' => [
                    'class' => 'control-label col-md-3 col-sm-3 col-xs-12'
                ]
            ],
            'attributes' => [
                'class' => 'form-control col-md-9 col-xs-12',
                'id' => 'passwordVerify',
                'required' => 'required'
            ]
        ]);


        $this->add([
            'name' => 'usernameOrEmail',
            'type' => 'text',
            'options' => [
                'label' => 'Username',
                'label_attributes' => [
                    'class' => ''
                ]
            ],
            'attributes' => [
                'class' => 'form-control col-md-9 col-xs-12',
                'id' => 'username',
                'required' => 'required',
                'title' => 'Provide Staffs phone number'
            ]
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [
            "password" => [
                "required" => true,
                "allow_empty" => false,
                "filters" => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                "validators" => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 6,
                            'max' => 50,
                            "messages" => [
                                StringLength::TOO_SHORT => "The password must be more than 6 characters",
                                StringLength::TOO_LONG => "This password is too long to memorize"
                            ]
                        ]
                    ]
                ]
            ],

            "passwordVerify" => [
                "required" => true,
                "allow_empty" => false,
                "validators" => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 6,
                            'max' => 50,
                            "messages" => [
                                StringLength::TOO_SHORT => "The password must be more than 6 characters",
                                StringLength::TOO_LONG => "This password is too long to memorize"
                            ]
                        ]
                    ],
                    [
                        'name' => 'Identical',
                        'options' => [
                            'token' => 'password',
                            "messages" => [
                                Identical::NOT_SAME => "The passwords are not identical"
                            ]
                        ]
                    ]
                ]
            ],
            'email' => [
                'required' => true,
                'allow_empty' => false,
                'break_chain_on_failure' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    // array(
                    // 'name' => 'Regex',
                    // 'options' => array(
                    // 'pattern' => '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/',
                    // 'messages' => array(
                    // Regex::NOT_MATCH => 'Please provide a valid email address.'
                    // )
                    // ),
                    // 'break_chain_on_failure' => true
                    // ),
                    [
                        'name' => 'DoctrineModule\Validator\NoObjectExists',
                        'options' => [
                            'use_context' => true,
                            'object_repository' => $this->entityManager->getRepository('CsnUser\Entity\User'),
                            'object_manager' => $this->entityManager,
                            'fields' => [
                                'email'
                            ],
                            'messages' => [

                                NoObjectExists::ERROR_OBJECT_FOUND => 'Someone else is registered with this email'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Laminas\Validator\StringLength',
                        'options' => [
                            'messages' => [],
                            'min' => 3,
                            'max' => 256,
                            'messages' => [
                                StringLength::TOO_SHORT => 'Email Too short',
                                StringLength::TOO_LONG => 'We dont think this is a genuine email'
                            ]
                        ],

                        [
                            'name' => 'EmailAddress',

                            'options' => [

                                'messages' => [
                                    EmailAddress::INVALID_FORMAT => 'Please check your email something is not right'
                                ]
                            ]
                        ]
                    ]

                ]
            ],
            'username' => [
                'required' => true,
                'allow_empty' => false,
                'break_chain_on_failure' => true,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [

                        'name' => 'DoctrineModule\Validator\NoObjectExists',
                        'options' => [
                            'use_context' => false,
                            'object_repository' => $this->entityManager->getRepository('CsnUser\Entity\User'),
                            'object_manager' => $this->entityManager,
                            'fields' => [
                                'username'
                            ],
                            'use_context' => true,
                            'messages' => [
                                NoObjectExists::ERROR_OBJECT_FOUND => 'Someone else is registered with this phone number'
                            ]
                        ]
                    ],

                    [
                        'name' => 'StringLength',
                        'options' => [
                            'min' => 9,
                            'max' => 11,
                            'messages' => [
                                StringLength::TOO_SHORT => 'Please insert the correct amount of digits',
                                StringLength::TOO_LONG => 'We dont think this is a genuine phone number'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }



    /**
     * Get the value of entityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Set the value of entityManager
     *
     * @return  self
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }
}
