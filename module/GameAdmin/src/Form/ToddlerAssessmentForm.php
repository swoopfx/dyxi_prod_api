<?php

/**
 * ============================================================================
 * DYXI Platform - GameAdmin Subsystem
 * File: ToddlerAssessmentForm.php
 * ============================================================================
 * Description: Form representation for managing and creating Toddler Assessment / 
 * ToddlerGamesList entities. Collects game selection from database, optional custom
 * JSON config, and handles CSRF validation.
 * ============================================================================
 */

declare(strict_types=1);

namespace GameAdmin\Form;

use GameAdmin\Form\InputFilter\ToddlerAssessmentInputFilter;
use Laminas\Form\Element;
use Laminas\Form\Form;

/**
 * Form for Toddler Assessment / ToddlerGamesList entity creation and configuration.
 */
class ToddlerAssessmentForm extends Form
{
    private array $gameOptions;

    /**
     * ToddlerAssessmentForm Constructor.
     *
     * @param array $gameOptions Array of Game select options [id => title] from DB.
     * @param string $name Form instance name.
     */
    public function __construct(array $gameOptions = [], $name = 'toddler_assessment_form')
    {
        parent::__construct($name);

        $this->gameOptions = $gameOptions;

        $this->setAttributes([
            'method' => 'POST',
            'class'  => 'ga-toddler-assessment-form',
        ]);

        $this->setInputFilter(new ToddlerAssessmentInputFilter());
        $this->addElements();
    }

    /**
     * Adds form fields and elements.
     */
    private function addElements(): void
    {
        // Game Select Field
        $this->add([
            'name' => 'gameId',
            'type' => Element\Select::class,
            'options' => [
                'label'         => 'Select Game for Toddler Assessment',
                'value_options' => $this->gameOptions,
                'empty_option'  => '-- Select Target Game --',
            ],
            'attributes' => [
                'class'    => 'ga-form-control',
                'required' => 'required',
            ],
        ]);

        // Custom Config Field (JSON)
        $this->add([
            'name' => 'customConfig',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Custom Configuration (JSON)',
            ],
            'attributes' => [
                'class'       => 'ga-form-control',
                'rows'        => 5,
                'placeholder' => '{"min_age": 2, "max_age": 4, "interactive_sound": true, "high_contrast": true, "auto_advance": true}',
            ],
        ]);

        // Is Active Checkbox
        $this->add([
            'name' => 'isActive',
            'type' => Element\Checkbox::class,
            'options' => [
                'label'              => 'Set as Active Toddler Assessment (Deactivates all other Toddler Assessment entries)',
                'use_hidden_element' => true,
                'checked_value'      => '1',
                'unchecked_value'    => '0',
            ],
            'attributes' => [
                'value' => '1',
                'id'    => 'ga-is-active-checkbox',
            ],
        ]);

        // CSRF Protection Token
        $this->add([
            'name' => 'csrf',
            'type' => Element\Csrf::class,
            'options' => [
                'csrf_options' => [
                    'timeout' => 600,
                ],
            ],
        ]);

        // Submit Button
        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Save Toddler Assessment Entity',
                'class' => 'ga-btn-primary',
            ],
        ]);
    }
}
