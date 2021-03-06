<?php
declare(strict_types=1);

namespace GuzabaPlatform\Crud;

use Guzaba2\Base\Exceptions\RunTimeException;
use GuzabaPlatform\Components\Base\BaseComponent;
use Guzaba2\Base\Base;
use Guzaba2\Mvc\Controller;
use GuzabaPlatform\Components\Base\Interfaces\ComponentInitializationInterface;
use GuzabaPlatform\Components\Base\Interfaces\ComponentInterface;
use GuzabaPlatform\Crud\Hooks\AdminEntry;
use GuzabaPlatform\Crud\Hooks\RoutesEntry;
use GuzabaPlatform\Platform\Admin\Controllers\Navigation;
use GuzabaPlatform\Platform\Application\VueRouter;
use GuzabaPlatform\Platform\Routes\Controllers\Routes;

/**
 * Class Component
 * @package Azonmedia\Tags
 */
class Component extends BaseComponent implements ComponentInterface, ComponentInitializationInterface
{

    protected const CONFIG_DEFAULTS = [
        'services'      => [
            'FrontendRouter',
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    protected const COMPONENT_NAME = "CRUD";
    //https://components.platform.guzaba.org/component/{vendor}/{component}
    protected const COMPONENT_URL = 'https://components.platform.guzaba.org/component/guzaba-platform/crud';
    //protected const DEV_COMPONENT_URL//this should come from composer.json
    protected const COMPONENT_NAMESPACE = 'GuzabaPlatform\\Crud';
    protected const COMPONENT_VERSION = '0.0.1';//TODO update this to come from the Composer.json file of the component
    protected const VENDOR_NAME = 'Azonmedia';
    protected const VENDOR_URL = 'https://azonmedia.com';
    protected const ERROR_REFERENCE_URL = 'https://github.com/AzonMedia/component-crud/tree/master/docs/ErrorReference/';


    /**
     * @return array
     * @throws RunTimeException
     */
    public static function run_all_initializations() : array
    {
        self::register_routes();
        return ['register_routes'];
    }


    /**
     * @throws RunTimeException
     */
    public static function register_routes() : void
    {
        $FrontendRouter = self::get_service('FrontendRouter');
        $additional = [
            'name'  => 'CRUD',
            'meta' => [
                'in_navigation' => TRUE, //to be shown in the admin navigation
                'additional_template' => '@GuzabaPlatform.Crud/CrudNavigationHook.vue',//here the list of classes will be expanded
            ],
        ];
        $FrontendRouter->{'/admin'}->add('crud', '@GuzabaPlatform.Crud/CrudAdmin.vue' ,$additional);

        $additional = [
            'name'  => 'CRUD Class',
            'meta' => [
                'additional_template' => '@GuzabaPlatform.Crud/CrudNavigationHook.vue',//here the list of classes will be expanded
            ],
        ];
        $FrontendRouter->{'/admin'}->add('crud/:class', '@GuzabaPlatform.Crud/CrudAdmin.vue', $additional);
    }

}