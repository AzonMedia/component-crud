<?php
declare(strict_types=1);

namespace GuzabaPlatform\Crud\Controllers;

use Guzaba2\Base\Exceptions\RunTimeException;
use Guzaba2\Http\Method;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;

class Admin extends BaseController
{
    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/crud'               => [
                Method::HTTP_GET              => [self::class, 'main']
            ],
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    public function main() : ResponseInterface
    {

        //throw new RunTimeException('asd', 0, NULL, '68788f0f-d36e-4995-8119-e23d22b3106a');
        $struct = ['message' => 'not implemented'];
        $Response = self::get_structured_ok_response($struct);
        return $Response;
    }
}