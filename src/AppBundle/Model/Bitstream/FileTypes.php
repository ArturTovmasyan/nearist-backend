<?php

namespace AppBundle\Model\Bitstream;

final class FileTypes
{
    const BITSTREAM = 1;
    const MCS = 2;
    const PRM = 3;
    const SEA = 4;
    const SED = 5;

    private static $ITEMS = array(
        self::BITSTREAM,
        self::MCS,
        self::PRM,
        self::SEA,
        self::SED
    );

    private static $ITEM_NAMES = array(
        self::BITSTREAM => "Bitstream",
        self::MCS => "MCS",
        self::PRM => "PRM",
        self::SEA => "SEA",
        self::SED => "SED"
    );

    public static function get($item)
    {
        switch ($item) {
            case self::BITSTREAM:
            case self::MCS:
            case self::PRM:
            case self::SEA:
            case self::SED:
                return $item;
        }

        return null;
    }

    public static function getName($item)
    {
        switch ($item) {
            case self::BITSTREAM:
            case self::MCS:
            case self::PRM:
            case self::SEA:
            case self::SED:
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