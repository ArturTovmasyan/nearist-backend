<?php
/**
 * Created by PhpStorm.
 * User: haykg
 * Date: 04/05/2018
 * Time: 15:10
 */

namespace AppBundle\Model\Log;


class LogType
{
    const AUTH           = 0;
    const TEMPERATURE    = 1;
    const MEMORY         = 2;
    const CPU            = 3;
    const POWER          = 4;
    const LOAD_BITSTREAM = 5;
    const LOGOUT         = 6;

    const LOG_TYPE_ICONS = [
        self::AUTH           => 'fa fa-sign-in',
        self::TEMPERATURE    => 'fa fa-thermometer-empty',
        self::MEMORY         => '',
        self::CPU            => 'fa fa-desktop',
        self::POWER          => 'fa fa-power-off',
        self::LOAD_BITSTREAM => '',
        self::LOGOUT         => 'fa fa-sign-out',
    ];

    const LOG_TYPE_ICON_COLOR = [
        self::AUTH           => '',
        self::TEMPERATURE    => 'danger',
        self::MEMORY         => '',
        self::CPU            => '',
        self::POWER          => 'danger',
        self::LOAD_BITSTREAM => '',
        self::LOGOUT         => 'danger',
    ];

    public function getList()
    {
        return [self::AUTH, self::TEMPERATURE, self::MEMORY, self::CPU, self::POWER, self::LOAD_BITSTREAM, self::LOGOUT];
    }
}