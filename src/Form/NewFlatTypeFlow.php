<?php

namespace App\Form;

use App\Form\NewFlatFormType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class NewFlatTypeFlow extends FormFlow {
    protected $allowDynamicStepNavigation = true;
    protected $handleFileUploads = true;
	protected function loadStepsConfig(): array
    {
		return [
			[
				'label' => 'Basic flat info',
				'form_type' => NewFlatFormType::class,
                'form_options' => [
                    'validation_groups' => ['Default'],
                ],
			],
			[
				'label' => 'Fees',
				'form_type' => NewFlatFormType::class,
                'form_options' => [
                    'validation_groups' => ['Default'],
                ],
			],
            [
                'label' => 'Pictures',
                'form_type' => NewFlatFormType::class,
                'form_options' => [
                    'validation_groups' => ['Default'],
                ],
            ],
            [
                'label' => 'Additional info',
                'form_type' => NewFlatFormType::class,
                'form_options' => [
                    'validation_groups' => ['Default'],
                ],
            ],
			[
				'label' => 'Confirmation',
			],

		];
	}

    /**
     * {@inheritDoc}
     */
    public function isStepDone($stepNumber)
    {
        if ($this->getFormData() != null && $this->getFormData()->getId()) {
            return true;
        }

        return parent::isStepDone($stepNumber);
    }

}