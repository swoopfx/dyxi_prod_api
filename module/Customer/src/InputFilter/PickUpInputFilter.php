<?php

namespace Customer\InputFilter;

use Laminas\InputFilter\InputFilter;

class PickUpInputFilter extends InputFilter
{
    private $entityManager;

    public function init()
    {

        $this->add([
            'name' => 'request_type',
            'break_chain_on_failure' => true,
            'required' => true,
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
                            'isEmpty' => 'User  is required'
                        ]
                    ]
                ]
            ]
        ]);
        $this->add([
            'name' => 'estimated_weight',
            'break_chain_on_failure' => true,
            'required' => true,
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
                            'isEmpty' => 'Waste type is required'
                        ]
                    ]
                ]
            ]
        ]);
        $this->add([
            'name' => 'waste_type',
            'break_chain_on_failure' => true,
            'required' => true,
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
                            'isEmpty' => 'Waste request type is required'
                        ]
                    ]
                ]
            ]
        ]);

        $this->add([
            'name' => 'pickup_address',
            'break_chain_on_failure' => true,
            'required' => true,
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
                            'isEmpty' => 'Pick address is required'
                        ]
                    ]
                ]
            ]
        ]);

        $this->add([
            'name' => 'address_google_place_id',
            'break_chain_on_failure' => true,
            'required' => true,
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
                            'isEmpty' => 'Pickup Place Id is required'
                        ]
                    ]
                ]
            ]
        ]);

        $this->add([
            'name' => 'address_longitude',
            'break_chain_on_failure' => true,
            'required' => true,
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
                            'isEmpty' => 'Longitude is required'
                        ]
                    ]
                ]
            ]
        ]);

        $this->add([
            'name' => 'address_latitude',
            'break_chain_on_failure' => true,
            'required' => true,
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
                            'isEmpty' => 'latitude of address is required'
                        ]
                    ]
                ]
            ]
        ]);

        $this->add([
            'name' => 'datetime_of_pickup',
            'break_chain_on_failure' => true,
            'required' => true,
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
                            'isEmpty' => 'Date request made is required'
                        ]
                    ]
                ]
            ]
        ]);

        $this->add([
            'name' => 'note',
            'break_chain_on_failure' => true,
            'required' => false,
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
                            'isEmpty' => 'Username is required'
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
