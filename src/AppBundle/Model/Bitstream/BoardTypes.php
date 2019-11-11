<?php

namespace AppBundle\Model\Bitstream;

final class BoardTypes
{
    const TECNO = 1;
    const SONIC = 2;

    private static $ITEMS = array(
        self::TECNO,
        self::SONIC
    );

    private static $ITEM_NAMES = array(
        self::TECNO => "Tecno77 (Italian)",
        self::SONIC => "Sonic (USA)"
    );

    public static function get($item)
    {
        switch ($item) {
            case self::TECNO:
            case self::SONIC:
                return $item;
        }

        return null;
    }

    public static function getName($item)
    {
        switch ($item) {
            case self::TECNO:
            case self::SONIC:
                return self::$ITEM_NAMES[$item];
        }

        return null;
    }

    /** @return array */
    public static function getList()
    {
        return self::$ITEMS;
    }

    /** @return array */
    public static function getNamesList()
    {
        return self::$ITEM_NAMES;
    }

    public static function getConfig()
    {
        return array(
            self::TECNO => array(
                DeviceTypes::XILINX => array(FileTypes::BITSTREAM, FileTypes::MCS, FileTypes::PRM),
                DeviceTypes::LATTICE_UM => array(FileTypes::BITSTREAM, FileTypes::SEA, FileTypes::SED)
            ),
            self::SONIC => array(
                DeviceTypes::XILINX => array(FileTypes::BITSTREAM, FileTypes::MCS, FileTypes::PRM),
                DeviceTypes::LATTICE_U => array(FileTypes::BITSTREAM, FileTypes::SEA, FileTypes::SED)
            )
        );
    }
}