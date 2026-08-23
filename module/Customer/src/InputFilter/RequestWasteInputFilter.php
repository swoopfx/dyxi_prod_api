<?php

namespace Customer\InputFilter;

use Laminas\InputFilter\InputFilter;

class RequestWasteInputFilter extends InputFilter
{
    public function __construct($entityManager)
    {
        $this->add([
            'name' => 'user',
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
            'name' => 'wasteType',
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
            'name' => 'requestType',
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
            'name' => 'pickupAddress',
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
            'name' => 'pickupPlaceId',
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
            'name' => 'longitude',
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
            'name' => 'requestDate',
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

        $this->add([
            'name' => 'latitude',
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
                            'isEmpty' => 'Latitude is required'
                        ]
                    ]
                ]
            ]
        ]);
    }
}
