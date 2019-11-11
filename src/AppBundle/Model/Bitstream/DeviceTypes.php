<?php

namespace AppBundle\Model\Bitstream;

final class DeviceTypes
{
    const XILINX = 1;
    const LATTICE_U = 2;
    const LATTICE_UM = 3;

    private static $ITEMS = array(
        self::XILINX,
        self::LATTICE_U,
        self::LATTICE_UM
    );

    private static $ITEM_NAMES = array(
        self::XILINX => "Xilinx (xc7a75tfgg676-2)",
        self::LATTICE_U => "Lattice (LFE5U-85F-8BG381I)",
        self::LATTICE_UM => "Lattice (LFE5UM-85F-8BG381C)"
    );

    public static function get($item)
    {
        switch ($item) {
            case self::XILINX:
            case self::LATTICE_U:
            case self::LATTICE_UM:
                return $item;
        }

        return null;
    }

    public static function getName($item)
    {
        switch ($item) {
            case self::XILINX:
            case self::LATTICE_U:
            case self::LATTICE_UM:
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

}