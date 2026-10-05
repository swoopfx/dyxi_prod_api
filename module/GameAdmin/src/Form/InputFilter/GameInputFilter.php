<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: GameInputFilter.php
 * ============================================================================
 * Description: InputFilter specification for validating and filtering Game entity
 * creation and editing fields. Ensures data integrity for title, uniqueIdentifier,
 * launch URL, description text, uploaded .md files, and custom JSON configurations.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Form\InputFilter;

use Laminas\Filter\Digits;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

/**
 * InputFilter for Game entity form validation.
 */
class GameInputFilter extends InputFilter
{
    public function __construct()
    {
        $this->addTitleInput();
        $this->addUniqueIdentifierInput();
        $this->addGameTypeIdInput();
        $this->addCurriculumIdInput();
        $this->addGameAbsoluteUrlInput();
        $this->addTagsInput();
        $this->addMdFilesInput();
        $this->addDescriptionInput();
        $this->addCustomConfigInput();
    }

    private function addTitleInput(): void
    {
        $this->add([
            'name' => 'title',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Game title is required.',
                        ],
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'min' => 3,
                        'max' => 255,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Title must be at least 3 characters long.',
                            StringLength::TOO_LONG  => 'Title cannot exceed 255 characters.',
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function addUniqueIdentifierInput(): void
    {
        $this->add([
            'name' => 'uniqueIdentifier',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
                ['name' => StringToLower::class],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Unique identifier is required.',
                        ],
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'min' => 3,
                        'max' => 255,
                        'messages' => [
                            StringLength::TOO_SHORT => 'Unique identifier must be at least 3 characters long.',
                            StringLength::TOO_LONG  => 'Unique identifier cannot exceed 255 characters.',
                        ],
                    ],
                ],
                [
                    'name' => Regex::class,
                    'options' => [
                        'pattern' => '/^[a-z0-9_\-]+$/',
                        'messages' => [
                            Regex::NOT_MATCH => 'Unique identifier can only contain lowercase letters, numbers, underscores, and hyphens.',
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function addGameTypeIdInput(): void
    {
        $this->add([
            'name' => 'gameTypeId',
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
                            NotEmpty::IS_EMPTY => 'Game type is required. Please select a valid game type.',
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function addCurriculumIdInput(): void
    {
        $this->add([
            'name' => 'curriculumId',
            'required' => false,
            'allow_empty' => true,
            'filters' => [
                ['name' => Digits::class],
            ],
        ]);
    }

    private function addGameAbsoluteUrlInput(): void
    {
        $this->add([
            'name' => 'gameAbsoluteUrl',
            'required' => true,
            'allow_empty' => false,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
            ],
            'validators' => [
                [
                    'name' => NotEmpty::class,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Game absolute launch URL is required.',
                        ],
                    ],
                ],
                [
                    'name' => StringLength::class,
                    'options' => [
                        'max' => 512,
                        'messages' => [
                            StringLength::TOO_LONG => 'Launch URL cannot exceed 512 characters.',
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function addTagsInput(): void
    {
        $this->add([
            'name' => 'tags',
            'required' => false,
            'allow_empty' => true,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
                ['name' => StringToLower::class],
            ],
        ]);
    }

    private function addMdFilesInput(): void
    {
        $this->add([
            'name' => 'mdFiles',
            'required' => false,
            'allow_empty' => true,
        ]);
    }

    private function addDescriptionInput(): void
    {
        $this->add([
            'name' => 'description',
            'required' => false,
            'allow_empty' => true,
            'filters' => [
                ['name' => StripTags::class],
                ['name' => StringTrim::class],
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
                            Callback::INVALID_VALUE => 'Custom configuration must be valid JSON format (e.g. {"key": "value"}).',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
