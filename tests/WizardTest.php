<?php

declare(strict_types=1);

use jamesRUS52\Laravel\Exceptions\StepNotFoundException;
use jamesRUS52\Laravel\Step;
use jamesRUS52\Laravel\Wizard;

class WizardTest extends PHPUnit\Framework\TestCase
{
    protected $wizard;

    protected function setUp(): void
    {
        $step1 = $this->getMockForAbstractClass(Step::class, ['slug-1','label 1','view1'], 'Step1Wizard');
        $step2 = $this->getMockForAbstractClass(Step::class, ['slug-2','label 2','view2'], 'Step2Wizard');
        $step3 = $this->getMockForAbstractClass(Step::class, ['slug-3','label 3','view3'], 'Step3Wizard');
        $this->wizard = new Wizard('Wizard', [$step1, $step2, $step3]);
    }

    protected function tearDown(): void
    {
        $this->wizard = null;
    }


    public function testWizardSteps()
    {
        $this->assertInstanceOf(Wizard::class, $this->wizard);
    }

    public function testWizardWrongSteps()
    {
        $step1 = new \stdClass();
        $step2 = $this->getMockForAbstractClass(Step::class, ['slug-2','label 2','view2'], 'Step2Wizard');
        $step3 = $this->getMockForAbstractClass(Step::class, ['slug-3','label 3','view3'], 'Step3Wizard');

        $this->expectException(InvalidArgumentException::class);

        $wizard = new Wizard('Wizard', [$step1, $step2, $step3]);
    }

    public function testCountSteps()
    {
        $this->assertEquals(3, $this->wizard->stepsCount());
        $this->assertEquals('slug-1', $this->wizard->first()->slug);
        $this->assertEquals('slug-2', $this->wizard->getByIndex(1)->slug);
        $this->assertEquals('slug-3', $this->wizard->last()->slug);
    }

    public function testAppendStep()
    {
        $step4 = $this->getMockForAbstractClass(Step::class, ['slug-4','label 4','view4'], 'Step4Wizard');
        $this->wizard->appendStep($step4);

        $this->assertEquals(4, $this->wizard->stepsCount());
        $this->assertEquals('slug-4', $this->wizard->last()->slug);
    }

    public function testInsertStep()
    {
        $step4 = $this->getMockForAbstractClass(Step::class, ['slug-4','label 4','view4'], 'Step4Wizard');
        $this->wizard->insertStep(1, $step4);

        $this->assertEquals(4, $this->wizard->stepsCount());
        $this->assertEquals('slug-4', $this->wizard->getByIndex(1)->slug);
        $this->assertEquals('slug-2', $this->wizard->getByIndex(2)->slug);
    }

    public function testReplaceStep()
    {
        $step4 = $this->getMockForAbstractClass(Step::class, ['slug-4','label 4','view4'], 'Step4Wizard');
        $this->wizard->replaceStep(1, $step4);

        $this->assertEquals(3, $this->wizard->stepsCount());
        $this->assertEquals('slug-4', $this->wizard->getByIndex(1)->slug);
        $this->assertEquals('slug-3', $this->wizard->getByIndex(2)->slug);
    }

    public function testDestroyStep()
    {
        $this->wizard->destroyStep(1);

        $this->assertEquals(2, $this->wizard->stepsCount());
        $this->assertEquals('slug-1', $this->wizard->getByIndex(0)->slug);
        $this->assertEquals('slug-3', $this->wizard->getByIndex(1)->slug);
    }

    public function testWizardCompletionPercent(): void
    {
        $this->assertEquals(34, $this->wizard->completionPercent());
        $this->assertEquals(0, $this->wizard->completionPercent(true));
    }

    public function testMoveStep(): void
    {
        $this->assertEquals(0, $this->wizard->currentStep()->index);
        $this->wizard->nextStep();
        $this->assertEquals(1, $this->wizard->currentStep()->index);
        $this->wizard->nextStep();
        $this->assertEquals(2, $this->wizard->currentStep()->index);
        $this->wizard->prevStep();
        $this->assertEquals(1, $this->wizard->currentStep()->index);
        $this->wizard->prevStep();
        $this->assertEquals(0, $this->wizard->currentStep()->index);

    }
}