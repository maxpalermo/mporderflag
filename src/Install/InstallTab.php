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

namespace MpSoft\MpOrderFlag\Install;

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class InstallTab
{
    private $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function install(
        $parent,
        $name,
        $adminClassName,
        $icon = '',
        $position = null,
        $route = '',
        $wording = '',
        $domain = ''
    ) {
        $id_tab_parent = 0;
        if (!is_numeric($parent)) {
            $id_tab_parent = (int) SymfonyContainer::getInstance()
                ->get('prestashop.core.admin.tab.repository')
                ->findOneIdByClassName($parent);
        } else {
            $id_tab_parent = (int) $parent;
        }

        if ($position === null) {
            $position = (int) \Tab::getNewLastPosition($id_tab_parent);
        }

        $tab = new \Tab();
        $tab->id_parent = $id_tab_parent;
        $tab->position = (int) $position;
        $tab->module = $this->module->name;
        $tab->class_name = $adminClassName;
        $tab->route_name = $route;
        $tab->active = 1;
        $tab->enabled = 1;
        $tab->icon = $icon;
        $tab->wording = $wording;
        $tab->wording_domain = $domain;

        // Multilang fields
        $tab->name = [];
        if (is_array($name)) {
            $tab->name = $name;
        } else {
            foreach (\Language::getLanguages() as $lang) {
                $id_lang = (int) $lang['id_lang'];
                $tab->name[$id_lang] = $name;
            }
        }

        return $tab->add();
    }

    public function uninstall($class)
    {
        $id_tab = (int) SymfonyContainer::getInstance()
            ->get('prestashop.core.admin.tab.repository')
            ->findOneIdByClassName($class);

        if ($id_tab) {
            $tab = new \Tab((int) $id_tab);

            return $tab->delete();
        }

        return true;
    }
}
