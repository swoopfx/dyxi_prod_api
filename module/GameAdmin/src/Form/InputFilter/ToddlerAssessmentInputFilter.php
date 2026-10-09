<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: ToddlerAssessmentInputFilter.php
 * ============================================================================
 * Description: InputFilter specification for validating and filtering Toddler Assessment
 * / ToddlerGamesList form input fields. Validates selected game ID and optional
 * JSON custom configuration.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Form\InputFilter;

use Laminas\Filter\Digits;
use Laminas\Filter\StringTrim;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;

/**
 * InputFilter for Toddler Assessment / ToddlerGamesList entity form validation.
 */
class ToddlerAssessmentInputFilter extends InputFilter
{
    public function __construct()
    {
        $this->addGameIdInput();
        $this->addCustomConfigInput();
        $this->addIsActiveInput();
    }

    private function addIsActiveInput(): void
    {
        $this->add([
            'name' => 'isActive',
            'required' => false,
            'allow_empty' => true,
        ]);
    }

    private function addGameIdInput(): void
    {
        $this->add([
            'name' => 'gameId',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                ['name' => Digits::class],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Please select a game for Toddler Assessment.',
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function addCustomConfigInput(): void
    {
        $this->add([
            'name' => 'customConfig',
            'required' => false,
            'allow_empty' => true,
            'filters' => [
                ['name' => StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => Callback::class,
                    'options' => [
                        'callback' => function ($value) {
                            if (empty($value)) {
                                return true;
                            }
                            json_decode($value);
                            return json_last_error() === JSON_ERROR_NONE;
                        },
                        'messages' => [
                            Callback::INVALID_VALUE => 'Custom configuration must be valid JSON format (e.g. {"min_age": 2, "max_age": 4}).',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
