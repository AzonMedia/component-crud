<?php
declare(strict_types=1);

namespace GuzabaPlatform\Crud\Hooks;

use Guzaba2\Base\Base;
use Guzaba2\Http\Body\Structured;
use GuzabaPlatform\Platform\Application\BaseController;
use GuzabaPlatform\Platform\Application\GuzabaPlatform;
use Psr\Http\Message\ResponseInterface;
use Guzaba2\Translator\Translator as t;

class AdminEntry extends Base
{

    public function execute_hook(ResponseInterface $Response) : ResponseInterface
    {
        $Body = $Response->getBody();
        $struct = $Body->getStructure();//no ref here - we adhere to the immutability
        $struct['links'][] = [
            'name'          => t::_('CRUD'),
            //'route'         => GuzabaPlatform::API_ROUTE_PREFIX.'/crud',
            'route'         => '/admin/crud',
        ];
        $Response = $Response->withBody( new Structured($struct) );
        return $Response;
    }
}