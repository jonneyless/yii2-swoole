<?php

namespace ijony\yiis\Yii2;

use ijony\yiis\yii2web\Application;
use yii\web\Response;

class ReflectionApp
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var \ReflectionObject
     */
    protected $reflectionApp;

    /**
     * ReflectionApp constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->reflectionApp = new \ReflectionObject($app);
    }

    /**
     * Get all bindings from application container.
     *
     * @return array
     * @throws \ReflectionException
     */
    public function instances()
    {
        $instances = $this->reflectionApp->getProperty('instances');
        $instances->setAccessible(true);
        $instances = array_merge($this->app->getBindings(), $instances->getValue($this->app));

        return $instances;
    }

    /**
     * Call terminable middleware of Lumen.
     *
     * @param Response $response
     *
     * @throws \ReflectionException
     */
    public function callTerminableMiddleware(Response $response)
    {
        $middleware = $this->reflectionApp->getProperty('middleware');
        $middleware->setAccessible(true);

        if (!empty($middleware->getValue($this->app))) {
            $callTerminableMiddleware = $this->reflectionApp->getMethod('callTerminableMiddleware');
            $callTerminableMiddleware->setAccessible(true);
            $callTerminableMiddleware->invoke($this->app, $response);
        }
    }

    /**
     * The parameter count of 'register' method in app container.
     *
     * @return int
     * @throws \ReflectionException
     */
    public function registerMethodParameterCount()
    {
        return $this->reflectionApp->getMethod('register')->getNumberOfParameters();
    }

    /**
     * Get 'loadedProviders' of application container.
     *
     * @return array
     * @throws \ReflectionException
     */
    public function loadedProviders()
    {
        $loadedProviders = $this->reflectLoadedProviders();

        return $loadedProviders->getValue($this->app);
    }

    /**
     * Get the reflect loadedProviders of application container.
     *
     * @return \ReflectionProperty
     * @throws \ReflectionException
     */
    protected function reflectLoadedProviders()
    {
        $loadedProviders = $this->reflectionApp->getProperty('loadedProviders');
        $loadedProviders->setAccessible(true);

        return $loadedProviders;
    }

    /**
     * Set 'loadedProviders' of application container.
     *
     * @param array $loadedProviders
     *
     * @throws \ReflectionException
     */
    public function setLoadedProviders(array $loadedProviders)
    {
        $reflectLoadedProviders = $this->reflectLoadedProviders();
        $reflectLoadedProviders->setValue($this->app, $loadedProviders);
    }
}
