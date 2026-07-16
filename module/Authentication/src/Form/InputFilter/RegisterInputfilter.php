<?php

namespace Authentication\Form\InputFilter;

use Authentication\Entity\User;
use DoctrineModule\Validator\NoObjectExists;
use Laminas\InputFilter\InputFilter;
use Doctrine\ORM\EntityManager;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Identical;
use Laminas\Validator\StringLength;

class RegisterInputfilter extends InputFilter
{
    /**
     *
     *
     * @var EntityManager
     */
    private $entityManager;


    public function __construct($entityManager)
    {
        // $this->entityManager= $entityManager;
        $this->add([
            'name' => 'username',
            'required' => true,
            "allow_empty" => false,
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
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Identity is required'
                        ]
                    ]
                ],
                [
                    "name" => NoObjectExists::class,
                    "options" => [
                        "use_context" => true,
                        "object_repository" => $entityManager->getRepository(User::class),
                        "objject_manager" => $entityManager,
                        "fields" => [
                            "username"
                        ],
                        "messages" => [
                            NoObjectExists::ERROR_OBJECT_FOUND => "please use another identity"
                        ]
                    ]
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'messages' => [],
                        'min' => 6,
                        'max' => 256,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Try Something more longer',
                            StringLength::TOO_LONG => 'Could you really remember this long identity'
                        ]
                    ],
                ]
            ]
        ]);


        $this->add([
            'name' => 'fullname',
            'required' => true,
            "allow_empty" => false,
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
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Full Name is required'
                        ]
                    ]
                ],

                [
                    'name' => StringLength::class,
                    'options' => [
                        'messages' => [],
                        'min' => 1,
                        'max' => 256,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Try Something more longer',
                            StringLength::TOO_LONG => 'Could you really remember this long identity'
                        ]
                    ],
                ]
            ]
        ]);

        $this->add([
            'name' => 'address',
            'required' => true,
            "allow_empty" => false,
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
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Address is required'
                        ]
                    ]
                ],

                [
                    'name' => StringLength::class,
                    'options' => [
                        'messages' => [],
                        'min' => 6,
                        'max' => 256,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Try Something more longer',
                            StringLength::TOO_LONG => 'Could you really remember this long identity'
                        ]
                    ],
                ]
            ]
        ]);

        $this->add([
            'name' => 'address_google_place_id',
            'required' => true,
            "allow_empty" => false,
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
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Google place ID is required'
                        ]
                    ]
                ],

                [
                    'name' => StringLength::class,
                    'options' => [
                        'messages' => [],
                        'min' => 6,
                        'max' => 256,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Try Something more longer',
                            StringLength::TOO_LONG => 'Could you really remember this long identity'
                        ]
                    ],
                ]
            ]
        ]);



        $this->add([
            'name' => 'address_longitude',
            'required' => true,
            "allow_empty" => false,
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
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Address longitude is required'
                        ]
                    ]
                ],

                [
                    'name' => StringLength::class,
                    'options' => [
                        'messages' => [],
                        'min' => 6,
                        'max' => 256,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Try Something more longer',
                            StringLength::TOO_LONG => 'Could you really remember this long identity'
                        ]
                    ],
                ]
            ]
        ]);

        $this->add([
            'name' => 'address_latitude',
            'required' => true,
            "allow_empty" => false,
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
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'Address latitude is required'
                        ]
                    ]
                ],

                [
                    'name' => StringLength::class,
                    'options' => [
                        'messages' => [],
                        'min' => 6,
                        'max' => 256,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Try Something more longer',
                            StringLength::TOO_LONG => 'Could you really remember this long identity'
                        ]
                    ],
                ]
            ]
        ]);


        $this->add([
            "name" => 'email',
            'required' => true,
            'allow_empty' => false,
            // 'break_chain_on_failure' => true,
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
                        'object_repository' => $entityManager->getRepository(User::class),
                        'object_manager' => $entityManager,
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
        ]);

        $this->add([
            "name" => "password",
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

        ]);

        $this->add([
            "name" => "confirm_password",
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

        ]);
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
