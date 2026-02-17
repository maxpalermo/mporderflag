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

class ModelMpOrderFlagItem extends ObjectModel
{
    public $id_order_flag;
    public $name;
    public $icon;
    public $color;
    public $date_add;
    public $date_upd;
    protected $module;

    public static $definition = [
        'table' => 'order_flag_item',
        'primary' => 'id_order_flag_item',
        'multilang' => false,
        'multishop' => false,
        'fields' => [
            'name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'size' => 64,
                'required' => true,
            ],
            'icon' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'size' => 64,
                'required' => true,
            ],
            'color' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'size' => 7,
                'required' => true,
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'datetime' => true,
                'required' => true,
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate',
                'datetime' => true,
                'required' => false,
            ],
        ],
    ];

    public static function getList()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();

        $sql
            ->select('*')
            ->from(ModelMpOrderFlagItem::$definition['table'])
            ->orderBy(ModelMpOrderFlagItem::$definition['primary']);
        $rows = $db->executeS($sql);
        if (!$rows) {
            $rows = [];
        }

        return $rows;
    }
}
