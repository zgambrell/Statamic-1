<?php

namespace Statamic\Addons\Form;

use Statamic\API\Crypt;
use Statamic\API\Form;
use DebugBar\DebugBarException;
use DebugBar\DataCollector\ConfigCollector;
use Statamic\Addons\Collection\CollectionTags;

class FormTags extends CollectionTags
{
    /**
     * @var string
     */
    private $formset_name;

    /**
     * Maps to {{ form:set }}
     *
     * Allows you to inject the formset into the context so child tags can use it.
     *
     * @return string
     */
    public function set()
    {
        $this->context['formset'] = $this->getParam(['in', 'is', 'formset']);

        return $this->parse($this->context);
    }

    /**
     * Maps to {{ form:create }}
     *
     * @return string
     */
    public function create()
    {
        $data = [];

        $this->formset_name = $formset = $this->getFormset();

        $html = $this->formOpen('create');

        if ($this->hasErrors()) {
            $data['errors'] = session('errors')->getBag('form.'.$formset)->all();
        }

        if ($this->flash->exists('success')) {
            $data['success'] = true;
        }

        // Make formset data available to the tag
        $data['fields'] = (Form::fields($formset));

        $this->addToDebugBar($data);

        $params = compact('formset');

        if ($redirect = $this->get('redirect')) {
            $params['redirect'] = $redirect;
        }

        if ($error_redirect = $this->get('error_redirect')) {
            $params['error_redirect'] = $error_redirect;
        }

        $html .= '<input type="hidden" name="_params" value="'. Crypt::encrypt($params) .'" />';

        $html .= $this->parse($data);

        $html .= '</form>';

        return $html;
    }

    /**
     * Maps to {{ form:errors }}
     *
     * @return string
     */
    public function errors()
    {
        if (! $formset = $this->getFormset()) {
            return false;
        }

        if (! $this->hasErrors()) {
            return false;
        }

        $errors = [];

        foreach (session('errors')->getBag('form.'.$formset)->all() as $error) {
            $errors[]['value'] = $error;
        }

        return ($this->content === '')    // If this is a single tag...
            ? !empty($errors)             // just output a boolean.
            : $this->parseLoop($errors);  // Otherwise, parse the content loop.
    }

    /**
     * Maps to {{ form:success }}
     *
     * @return bool
     */
    public function success()
    {
        return $this->flash->exists('success');
    }

    /**
     * Maps to {{ form:submission }}
     *
     * @return array
     */
    public function submission()
    {
        if ($this->success()) {
            return $this->flash->get('submission')->toArray();
        }
    }

    /**
     * Maps to {{ form:submissions }}
     *
     * @return array
     */
    public function submissions()
    {
        $submissions = Form::get($this->getFormset())->submissions();

        $this->collection = collect_content($submissions);

        $this->filter();

        return $this->output();
    }

    /**
     * Get the sort order for a collection
     *
     * @return string
     */
    protected function getSortOrder()
    {
        return $this->get('sort', 'date');
    }

    /**
     * Open a form tag
     *
     * @param  string $action
     * @return string
     */
    protected function formOpen($action)
    {
        $attr_str = '';
        if ($attrs = $this->getList('attr')) {
            foreach ($attrs as $attr) {
                list($param, $value) = explode(':', $attr);
                $attr_str .= $param . '="' . $value . '" ';
            }
        }

        if ($this->getBool('files')) {
            $attr_str .= 'enctype="multipart/form-data"';
        }

        $action = $this->eventUrl($action);

        $html = '<form method="POST" action="'.$action.'" '.$attr_str.'>';

        return $html;
    }

    /**
     * Get the formset specified either by the parameter or from within the context
     *
     * @return string
     */
    private function getFormset()
    {
        if (! $formset = $this->get(['formset', 'in'], array_get($this->context, 'formset'))) {
            throw new \Exception('A formset is required on Form tags. Please refer to the docs for more information.');
        }

        return $formset;
    }

    /**
     * Does this form have errors?
     *
     * @return bool
     */
    private function hasErrors()
    {
        if (! $formset = $this->getFormset()) {
            return false;
        }

        return (session()->has('errors'))
               ? session()->get('errors')->hasBag('form.'.$formset)
               : false;
    }

    /**
     * Add data to the debug bar
     *
     * Each form on the page will have its data placed in an array named
     * by its name. We'll use blink to keep track of the data as
     * we go and just update the collector.
     *
     * @param array $data
     */
    private function addToDebugBar($data)
    {
        $debug = [];
        $debug[$this->formset_name] = $data;

        if ($this->blink->exists('debug_bar_data')) {
            $debug = array_merge($debug, $this->blink->get('debug_bar_data'));
        }

        $this->blink->put('debug_bar_data', $debug);

        try {
            debugbar()->getCollector('Forms')->setData($debug);
        } catch (DebugBarException $e) {
            // Collector doesn't exist yet. We'll create it.
            $collector = debugbar()->addCollector(new ConfigCollector($debug, 'Forms'));
        }
    }
}