<?php

namespace jamesRUS52\Laravel;

use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Mixed_;

abstract class Step
{

    public int $index = -1;
    protected Wizard $wizard;
    public string $slug;
    public string $label;
    public string $view;

    public function __construct(string $slug, string $label, string $view)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->view = $view;
    }

    public function setIndex(int $index)
    {
        $this->index = $index;
    }

    /**
     * @param Wizard $wizard
     */
    public function setWizard(Wizard $wizard): void
    {
        $this->wizard = $wizard;
    }



    abstract public function process(Request $request);

    public function rules(Request $request = null): array
    {
        return [];
    }

    public function attributes(Request $request = null): array
    {
        return [];
    }

    public function messages(Request $request = null): array
    {
        return [];
    }

    public function saveProgress(Request $request, array $additionalData = [])
    {
        $wizardData = $this->wizard->data();
        $wizardData[$this->slug] = $request->except('step', '_token');
        $wizardData = array_merge($wizardData, $additionalData);

        $this->wizard->data($wizardData);
    }

    public function clearData()
    {
        $data = $this->wizard->data();
        unset($data[$this->slug]);
        $this->wizard->data($data);
    }

    public function getData($field = null)
    {
        $data = $this->wizard->getData();
        if (!key_exists($this->slug, $data)) {
            return empty($field) ? [] : null;
        }
        return $field !== null
            ? key_exists($field, $data[$this->slug]) ? $data[$this->slug][$field] : null
            : $data[$this->slug] ?? [];
    }

    public function getAuxData() : array
    {
        return [];
    }

    public function getNumber(): int
    {
        return $this->index + 1;
    }
}
