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

namespace MpSoft\MpOrderFlag\Sql;

class ExecSql
{
    private $filename;
    private $db;

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->db = \Db::getInstance();
    }

    public function exec()
    {
        $basepath = __DIR__;
        if (!preg_match('/^' . preg_quote($basepath, '/') . '/', $this->filename)) {
            $this->filename = $basepath . '/' . $this->filename;
        }

        if (!file_exists($this->filename)) {
            throw new \Exception('File ' . $this->filename . ' not found');
        }

        $sql = file_get_contents($this->filename);
        $query = str_replace(['{$pfx}', '{$engine}'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);

        return $this->db->execute($query);
    }
}
