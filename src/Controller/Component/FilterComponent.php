<?php

namespace Filter\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;

/**
    CakePHP Filter Plugin

    Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
    <http://lecterror.com/>

    Multi-licensed under:
        MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
        LGPL <http://www.gnu.org/licenses/lgpl.html>
        GPL <http://www.gnu.org/licenses/gpl.html>
 */

/**
 * @property \Filter\Controller\Component\RequestHandlerComponent $RequestHandler
 * @property \Filter\Controller\Component\SessionComponent $Session
 */
class FilterComponent extends Component
{
    /**
     * @var array<string>
     */
    public $components = ['Session'];

    /**
     * @var array<mixed>
     */
    public $settings = [];

    /**
     * @var array<mixed>
     */
    public $nopersist = [];

    /**
     * @var array<mixed>
     */
    public $formData = [];

    /**
     * @var array<mixed>
     */
    protected $_request_settings = [];

    /**
     * {@inheritDoc}
     *
     * @param \Cake\Controller\ComponentRegistry $registry A ComponentRegistry this component can use to lazy load its components
     * @param array<mixed> $config Array of configuration settings.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);
        $this->_request_settings = $config;
    }

    /**
     * Is called before the controller’s beforeFilter method, but after the controller’s initialize() method.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        $controller = $this->getController();
        if (!isset($controller->filters)) {
            return;
        }

        $this->__updatePersistence($this->_request_settings);
        $controllerName = $controller->getName();
        $this->settings[$controllerName] = $controller->filters;

        $action = $controller->getRequest()->getParam('action');
        if (!isset($this->settings[$controllerName][$action])) {
            return;
        }

        $settings = $this->settings[$controllerName][$action];

        foreach ($settings as $model => $filter) {
            if (!isset($controller->{$model})) {
                trigger_error(sprintf('Filter model not found: %s', $model));
                continue;
            }

            $controller->$model->addBehavior('Filter.Filtered', $filter);
        }
    }

    /**
     * Is called after the controller’s beforeFilter method but before the controller executes the current action handler.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function startup(Event $event)
    {
        $controller = $this->getController();
        $controllerName = $controller->getName();
        $action = $controller->getRequest()->getParam('action');
        if (!isset($this->settings[$controllerName][$action])) {
            return;
        }

        $settings = $this->settings[$controllerName][$action];

        if (!in_array('Filter.Filter', $controller->viewBuilder()->getHelpers())) {
            $controller->viewBuilder()->setHelpers(['Filter.Filter']);
        }

        $sessionKey = sprintf('FilterPlugin.Filters.%s.%s', $controllerName, $action);
        $Session = $controller->getRequest()->getSession();
        $filterFormId = $controller->request->getQuery('filterFormId');
        if ($controller->request->is('get') && !empty($filterFormId)) {
            /** @var array<mixed> $requestData */
            $requestData = $controller->request->getQuery('data', []);
            $this->formData = $requestData;
        } elseif (!$controller->request->is('post') || $controller->request->getData('Filter.filterFormId') === null) {
            $persistedData = [];

            if ($Session->check($sessionKey)) {
                $persistedData = $Session->read($sessionKey);
            }

            if (empty($persistedData)) {
                return;
            }

            $this->formData = $persistedData;
        } else {
            /** @var array<mixed> $requestData */
            $requestData = $controller->request->getData();
            $this->formData = $requestData;
            if ($Session->started()) {
                $Session->write($sessionKey, $this->formData);
            }
        }
        foreach ($settings as $model => $options) {
            if (!isset($controller->{$model})) {
                trigger_error(__('Filter model not found: %s', $model));
                continue;
            }

            $controller->$model->setFilterValues($this->formData);
        }
    }

    /**
     * Is called after the controller executes the requested action’s logic, but before the controller renders views and layout.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function beforeRender(Event $event)
    {
        $controller = $this->getController();
        $controllerName = $controller->getName();
        $action = $controller->getRequest()->getParam('action');
        if (!isset($this->settings[$controllerName][$action])) {
            return;
        }

        $models = $this->settings[$controllerName][$action];
        $viewFilterParams = [];

        foreach ($models as $model => $fields) {
            if (!isset($controller->$model)) {
                trigger_error(__('Filter model not found: %s', $model));
                continue;
            }

            foreach ($fields as $field => $settings) {
                if (!is_array($settings)) {
                    $field = $settings;
                    $settings = [];
                }

                if (!isset($settings['required'])) {
                    $settings['required'] = false;
                }

                if (!isset($settings['type'])) {
                    $settings['type'] = 'text';
                }

                $options = [];

                $fieldName = $field;
                $fieldModel = $model;
                $className = null;
                if (isset($settings['className'])) {
                    $className = $settings['className'];
                }
                if (strpos($field, '.') !== false) {
                    list($fieldModel, $fieldName) = explode('.', $field);
                }

                if (!empty($this->formData)) {
                    if (isset($this->formData[$fieldModel][$fieldName])) {
                        $options['value'] = $this->formData[$fieldModel][$fieldName];

                        if ($options['value']) {
                            $options['class'] = 'filter-active';
                        }
                    }
                }

                if (isset($settings['inputOptions'])) {
                    if (!is_array($settings['inputOptions'])) {
                        $settings['inputOptions'] = [$settings['inputOptions']];
                    }

                    $options = array_merge($options, $settings['inputOptions']);
                }

                if (isset($settings['label'])) {
                    $options['label'] = $settings['label'];
                }

                switch ($settings['type']) {
                    case 'select':
                        $options['type'] = 'select';

                        $selectOptions = [];
                        $TableLocator = $this->getController()->getTableLocator();
                        if ($TableLocator->exists($fieldModel)) {
                            $workingModel = $TableLocator->get($fieldModel);
                        } else {
                            if ($className !== null) {
                                $workingModel = $TableLocator->get($fieldModel, [
                                    'className' => $className,
                                ]);
                            } else {
                                $workingModel = $TableLocator->get($fieldModel);
                            }
                        }

                        if (isset($settings['selectOptions'])) {
                            $selectOptions = $settings['selectOptions'];
                        }

                        if (isset($settings['selector'])) {
                            if (!method_exists($workingModel, $settings['selector'])) {
                                trigger_error(
                                    sprintf(
                                        'Selector method "%s" not found in model "%s" for field "%s"!',
                                        $settings['selector'],
                                        $fieldModel,
                                        $fieldName
                                    )
                                );

                                return;
                            }

                            $selectorName = $settings['selector'];
                            $options['options'] = $workingModel->$selectorName($selectOptions);
                        } else {
                            if ($fieldModel == $model) {
                                $listOptions = array_merge(
                                    $selectOptions,
                                    [
                                        'nofilter' => true,
                                        'keyField' => $fieldName,
                                        'valueField' => $fieldName,
                                        'fields' => [$fieldName, $fieldName],
                                    ]
                                );
                            } else {
                                $listOptions = array_merge($selectOptions, ['nofilter' => true]);
                            }
                            $options['options'] = $workingModel->find('list', $listOptions)
                                ->toArray();
                        }

                        if (!$settings['required']) {
                            $options['empty'] = '';
                        }

                        if (isset($settings['multiple'])) {
                            $options['multiple'] = $settings['multiple'];
                        }

                        break;

                    case 'checkbox':
                        $options['type'] = 'checkbox';

                        if (isset($options['value'])) {
                            $options['checked'] = (bool)$options['value'];
                            unset($options['value']);
                        } elseif (isset($settings['default'])) {
                            $options['checked'] = (bool)$settings['default'];
                        }
                        break;

                    default:
                        $options['type'] = $settings['type'];
                        break;
                }

                // if no value has been set, show the default one
                if (
                    !isset($options['value']) &&
                    isset($settings['default']) &&
                    $options['type'] != 'checkbox'
                ) {
                    $options['value'] = $settings['default'];
                }

                $viewFilterParams[] =
                    [
                        'name' => sprintf('%s.%s', $fieldModel, $fieldName),
                        'options' => $options,
                    ];
            }
        }

        if (
            !empty($this->settings['add_filter_value_to_title']) &&
            array_search($action, $this->settings['add_filter_value_to_title']) !== false
        ) {
            $title = $controller->viewVars['title_for_layout'];
            foreach ($viewFilterParams as $viewFilterParam) {
                if (
                    !empty($viewFilterParam['options']['class']) &&
                    $viewFilterParam['options']['class'] == 'filter-active'
                ) {
                    $titleValue = $viewFilterParam['options']['value'];
                    if ($viewFilterParam['options']['type'] == 'select') {
                        $titleValue = $viewFilterParam['options']['options'][$titleValue];
                    }
                    $title .= ' - ' . $titleValue;
                }
            }
            $controller->set('title_for_layout', $title);
        }
        $controller->set('viewFilterParams', $viewFilterParams);
    }

    /**
     * @param array<mixed> $settings Settings.
     * @return void
     */
    private function __updatePersistence($settings)
    {
        $controller = $this->getController();
        $controllerName = $controller->getName();
        $Session = $controller->getRequest()->getSession();
        if ($Session->check('FilterPlugin.NoPersist')) {
            $this->nopersist = $Session->read('FilterPlugin.NoPersist');
        }

        if (isset($settings['nopersist'])) {
            $this->nopersist[$controllerName] = $settings['nopersist'];
            if ($Session->started()) {
                $Session->write('FilterPlugin.NoPersist', $this->nopersist);
            }
        } elseif (isset($this->nopersist[$controllerName])) {
            unset($this->nopersist[$controllerName]);
            if ($Session->started()) {
                $Session->write('FilterPlugin.NoPersist', $this->nopersist);
            }
        }

        if (!empty($this->nopersist)) {
            foreach ($this->nopersist as $nopersistController => $actions) {
                if (is_string($actions)) {
                    $actions = [$actions];
                } elseif ($actions === true) {
                    $actions = [];
                }

                if (empty($actions) && $controllerName != $nopersistController) {
                    if ($Session->check(sprintf('FilterPlugin.Filters.%s', $nopersistController))) {
                        $Session->delete(sprintf('FilterPlugin.Filters.%s', $nopersistController));
                        continue;
                    }
                }

                $action = $controller->getRequest()->getParam('action');
                foreach ($actions as $noPersistAction) {
                    if ($controllerName == $nopersistController && $noPersistAction == $action) {
                        continue;
                    }
                    $sessionKey = sprintf('FilterPlugin.Filters.%s.%s', $nopersistController, $noPersistAction);
                    if ($Session->check($sessionKey)) {
                        $Session->delete($sessionKey);
                    }
                }
            }
        }
    }
}
