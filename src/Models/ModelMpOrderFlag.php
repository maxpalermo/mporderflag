<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpOrderFlag\Models;

use Db;
use DbQuery;
use ObjectModel;

class ModelMpOrderFlag extends ObjectModel
{
    public $id_order;
    public $id_order_flag;
    public $id_employee;
    public $date_add;
    public $date_upd;
    protected $module;

    public static $definition = [
        'table' => 'order_flag',
        'primary' => 'id_order',
        'multilang' => false,
        'multishop' => false,
        'fields' => [
            'id_order_flag' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
            ],
            'id_employee' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => false,
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'datetime' => true,
                'required' => false,
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'datetime' => true,
                'required' => false,
            ],
        ],
    ];

    public static function getCurrentFlagId($id_order)
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql
            ->select('id_order_flag')
            ->from(self::$definition['table'])
            ->where('id_order = ' . (int) $id_order);

        return (int) $db->getValue($sql);
    }
}
