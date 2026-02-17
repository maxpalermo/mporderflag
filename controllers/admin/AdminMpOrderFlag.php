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

use MpSoft\MpOrderFlag\Models\ModelMpOrderFlag;

class AdminMpOrderFlagController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'orders';
        $this->className = 'Order';
        $this->identifier = 'id_order';
        $this->lang = false;
        $this->explicitSelect = true;
        $this->allow_export = false;

        $this->context = Context::getContext();

        parent::__construct();

        $this->display = 'list';
        $this->toolbar_title = $this->module->l('Order Flags');
        $this->meta_title = $this->toolbar_title;

        $this->fields_list = [
            'id_order' => [
                'title' => $this->module->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'date_add' => [
                'title' => $this->module->l('Data'),
                'type' => 'date',
                'orderby' => true,
            ],
        ];

        $this->_defaultOrderBy = 'id_order';
        $this->_defaultOrderWay = 'DESC';
    }

    private function response($data, $httpCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($httpCode);

        exit(json_encode($data));
    }

    public function ajaxProcessUpdateOrderFlag()
    {
        $flag_id = Tools::getValue('flag_id');
        $old_flag_id = Tools::getValue('old_flag_id');
        $id_order = Tools::getValue('id_order');
        $name = Tools::getValue('name');
        $icon = Tools::getValue('icon');
        $color = Tools::getValue('color');
        $timestamp = Tools::getValue('timestamp');
        $error = '';

        // Recupera il record attuale o ne crea uno nuovo
        $record = new ModelMpOrderFlag($id_order);
        $exists = \Validate::isLoadedObject($record);

        // Imposta i valori necessari
        $record->id = (int) $id_order;
        $record->id_employee = (int) $this->context->employee->id;
        $record->id_order_flag = (int) $flag_id;
        $record->date_add = date('Y-m-d H:i:s');
        $record->date_upd = date('Y-m-d H:i:s');

        try {
            if ($exists) {
                $res = $record->update();
            } else {
                $record->force_id = true;
                $record->id = $id_order;
                $res = $record->add();
            }
            if (!$res) {
                $error = \Db::getInstance()->getMsgError();
            }
        } catch (\Throwable $th) {
            $res = false;
            $error = $th->getMessage();
        }

        $this->response([
            'success' => (bool) $res,
            'error' => $error,
        ]);
    }
}
