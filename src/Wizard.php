<?php

namespace jamesRUS52\Laravel;

use InvalidArgumentException;
use jamesRUS52\Laravel\Exceptions\StepNotFoundException;

class Wizard
{

    const SESSION_NAME = 'james.rus52.wizard';
    /**
     * @var Step[]
     */
    protected $steps = [];
    protected $currentIndex = -1;
    protected $sessionKeyName = '';
    protected $title = '';

    /**
     * @var Step[] $steps
     * @throws StepNotFoundException
     */
    public function __construct(string $title, array $steps, string $sessionKeyName = '')
    {
        if (empty($steps)) {
            throw new StepNotFoundException();
        }

        $this->title = $title;
        $this->currentIndex = $index = 0;

        foreach ($steps as $step) {
            if (!$step instanceof Step) {
                throw new InvalidArgumentException("Steps must be instance of ".Step::class);
            }
            $this->steps[] = $step;
            $step->setIndex($index);
            $step->setWizard($this);
            $index++;
        }

        $this->sessionKeyName = self::SESSION_NAME . '.' . $sessionKeyName;
        if (function_exists('view')) {
            view()->share(['wizard' => $this]);
        }
    }

    public function prevStep(bool $move = true): ?Step
    {
        if ($this->hasPrev()) {
            return $this->getStep($this->currentIndex - 1, $move);
        }
        return null;
    }

    public function hasPrev(): bool
    {
        return $this->currentIndex > 0 && isset($this->steps[$this->currentIndex - 1]);
    }

    /**
     * @throws StepNotFoundException
     */
    protected function getStep(int $index, bool $moveCurrentIndex = true): Step
    {
        if (!isset($this->steps[$index])) {
            throw new StepNotFoundException();
        }
        if ($moveCurrentIndex) {
            $this->currentIndex = $index;
        }
        return $this->steps[$index];
    }

    public function nextStep(bool $move = true): ?Step
    {
        if ($this->hasNext()) {
            return $this->getStep($this->currentIndex + 1, $move);
        }
        return null;
    }

    public function hasNext(): bool
    {
        return $this->currentIndex < $this->stepsCount() && isset($this->steps[$this->currentIndex + 1]);
    }

    public function stepsCount(): int
    {
        return count($this->steps);
    }

    /**
     * @throws StepNotFoundException
     */
    public function getBySlug(string $slug = ''): Step
    {
        foreach ($this->steps as $key => $step) {
            if ($step->slug == $slug) {
                $this->currentIndex = $step->index;
                return $step;
            }
        }
        throw new StepNotFoundException();
    }

    public function first(): Step
    {
        if ($this->stepsCount() == 0 ) {
            throw new StepNotFoundException();
        }
        return $this->steps[0];
    }

    public function firstOrLastProcessed(int $moveSteps = 0): Step
    {
        $lastProcessed = $this->lastProcessedIndex() ?: 0;
        $lastProcessed += $moveSteps;
        $this->currentIndex = $lastProcessed;
        return $this->steps[$lastProcessed];
    }

    public function lastProcessedIndex(): ?int
    {
        $data = $this->data();
        if ($data) {
            $lastProcessed = isset($data['lastProcessed']) ? $data['lastProcessed'] : null;
            return $lastProcessed;
        }
        return null;
    }

    public function data($data = null): array
    {
        $default = [];
        if (!function_exists('session')) {
            return $default;
        }
        if (is_array($data)) {
            $data['lastProcessed'] = $this->currentIndex;
            session([$this->sessionKeyName => $data]);
        }
        return session($this->sessionKeyName, $default);
    }

    public function hasData(): bool
    {
        $data = $this->data();
        return isset($data) && !empty($data);
    }

    public function getData()
    {
        return $this->data();
    }

    /**
     * @return Step[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function replaceStep(int $index, Step $new_step, bool $clear_step_data = true): Step
    {
        $step = $this->steps[$index];
        if($clear_step_data) {
            $step->clearData();
        }

        $this->steps[$index] = $new_step;
        $new_step->setIndex($index);

        return $step;
    }

    public function appendStep(Step $step): Step
    {
        $this->steps[] = $step;
        $step->setIndex(count($this->steps) - 1);

        return $step;
    }

    public function insertStep(int $index, step $step): Step
    {
        if ($index >= count($this->steps) || count($this->steps) === 0) {
            return $this->appendStep($step);
        }

        for ($i = count($this->steps); $i > $index; $i--) {
            $this->steps[$i] = $this->steps[$i - 1];
            $this->steps[$i]->index++;
        }
        $this->steps[$index] = $step;
        $step->setIndex($index);

        return $step;
    }

    public function destroyStep(int $index): void
    {
        $step = $this->getStep($index);
        $step->clearData();

        for ($i=$index+1; $i < count($this->steps); $i++) {
            $this->steps[$i]->index--;
        }

        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
    }

    public function clearProgress()
    {
        $this->currentIndex = count($this->steps) > 0 ? 0 : -1;
        $this->clearData();
    }

    public function clearData()
    {
        $this->data([]);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    public function completionPercent(bool $first_is_zero = false): float
    {
        $start_index = $first_is_zero ? 0 : 1;
        return ceil(($this->currentIndex+$start_index) / $this->stepsCount() * 100);
    }

    public function currentStep(): Step
    {
        return $this->steps[$this->currentIndex];
    }

    public function last():Step
    {
        return $this->steps[$this->stepsCount()-1];
    }

    public function getByIndex(int $index): Step
    {
        return $this->steps[$index];
    }
}
