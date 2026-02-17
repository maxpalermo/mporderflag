<?php

use Doctrine\ORM\QueryBuilder;
use MpSoft\MpOrderFlag\Helpers\GetActionUrl;

/*
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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'mporderflag/vendor/autoload.php';

use MpSoft\MpOrderFlag\Install\InstallTab;
use MpSoft\MpOrderFlag\Install\TableGenerator;
use MpSoft\MpOrderFlag\Models\ModelMpOrderFlag;
use MpSoft\MpOrderFlag\Models\ModelMpOrderFlagItem;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\HtmlColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Search\Filters;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class MpOrderFlag extends Module
{
    protected $adminClassName;
    protected $id_lang;
    protected $id_shop;
    protected $id_employee;
    protected $db;

    public function __construct()
    {
        $this->name = 'mporderflag';
        $this->tab = 'administration';
        $this->version = '1.3.10';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->adminClassName = 'AdminMpOrderFlag';
        $this->displayName = $this->l('MP Stato ordine');
        $this->description = $this->l('Visualizza un icona a seconda lo stato in corso');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->context = Context::getContext();
        $this->smarty = $this->context->smarty;
        $this->id_lang = (int) $this->context->language->id;
        $this->id_shop = (int) $this->context->shop->id;
        if (isset($this->context->employee)) {
            $this->id_employee = (int) $this->context->employee->id;
        } else {
            $this->id_employee = 0;
        }
        $this->db = Db::getInstance();
    }

    public function install()
    {
        $tableInstaller = new TableGenerator($this);

        if (Shop::isFeatureActive()) {
            Shop::setContext(ShopCore::CONTEXT_ALL);
        }

        return parent::install() &&
            $this->registerHook([
                'actionAdminControllerSetMedia',
                'actionOrderGridDefinitionModifier',
                'actionOrderGridQueryBuilderModifier',
                'actionOrderGridDataModifier',
                'displayAdminOrderTop',
            ]) &&
            (new InstallTab($this))->install('AdminParentOrders', 'Mp Status Ordini', $this->adminClassName, 'verified') &&
            $tableInstaller->createTable(MpSoft\MpOrderFlag\Models\ModelMpOrderFlag::$definition) &&
            $tableInstaller->createTable(MpSoft\MpOrderFlag\Models\ModelMpOrderFlagItem::$definition);
    }

    public function uninstall()
    {
        return parent::uninstall() && (new InstallTab($this))->uninstall($this->adminClassName);
    }

    public function hookDisplayAdminOrderTop($params)
    {
        $MpOrderFlagAdminControllerUrl = $this->context->link->getAdminLink($this->adminClassName);
        $MpOrderFlagCurrentFlagId = ModelMpOrderFlag::getCurrentFlagId($params['id_order']);

        $smarty = $this->context->smarty->createTemplate('module:mporderflag/views/templates/admin/mporderflag.tpl');
        $smarty->assign([
            'MpOrderFlagIdOrder' => $params['id_order'],
            'MpOrderFlagAdminControllerUrl' => $MpOrderFlagAdminControllerUrl,
            'MpOrderFlagCurrentFlagId' => $MpOrderFlagCurrentFlagId,
        ]);

        return $smarty->fetch();
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        $controller = Tools::getValue('controller');
        if (preg_match('/^AdminOrder/i', $controller) && Tools::getValue('id_order')) {
            $jsPath = $this->getLocalPath() . 'views/js';
            $this->context->controller->addJS([
                $jsPath . '/Admin/OrderFlag.js',
            ]);
        }
    }

    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        /** @var GridDefinitionInterface $definition */
        $definition = $params['definition'];

        // Aggiungi la colonna id_order_flag come DataColumn
        $definition
            ->getColumns()
            ->addBefore(
                'reference',
                (new HtmlColumn('order_flag_item'))
                    ->setName($this->trans('Status', [], 'Modules.MpOrderFlag.Admin'))
                    ->setOptions([
                        'field' => 'order_flag_item',
                        'sortable' => true,
                        'alignment' => 'center',
                        'clickable' => true,
                        'attr' => [
                            'width' => '48px',
                        ]
                    ])
            );

        $list = ModelMpOrderFlagItem::getList();
        if (!$list) {
            $list = [];
        }

        $options = [];
        foreach ($list as $item) {
            $options[] = [
                'id' => $item['id_order_flag_item'],
                'name' => $item['name'],
                'color' => $item['color'],
                'icon' => $item['icon'],
            ];
        }

        // Prepara le scelte per la select dei flag
        $choices = [];
        foreach ($options as $item) {
            $choices[$item['name']] = $item['id'];
        }

        // Aggiungi il filtro come select (ChoiceType)
        $definition->getFilters()->add(
            (new Filter('order_flag_item', ChoiceType::class))
                ->setAssociatedColumn('order_flag_item')
                ->setTypeOptions([
                    'choices' => $choices,
                    'placeholder' => 'Tutti',
                    'required' => false,
                ])
        );
    }

    public function hookActionOrderGridDataModifier(array $params)
    {
        $FLAG_DATA = [
            1 => [
                'id' => '1',
                'name' => 'OK',
                'icon' => 'verified',
                'color' => '#70b580',
            ],
            2 => [
                'id' => '2',
                'name' => 'ATTENZIONE',
                'icon' => 'warning',
                'color' => '#e9bd0c',
            ],
            3 => [
                'id' => '3',
                'name' => 'ERRORE',
                'icon' => 'error',
                'color' => '#f54c3e',
            ],
            4 => [
                'id' => '4',
                'name' => 'VERIFICA PAGAMENTO',
                'icon' => 'payment',
                'color' => '#25b9d7',
            ],
        ];
        $data = &$params['data'];  // Riferimento ai dati
        $records = $data->getRecords()->all();  // Ottieni tutti i record

        $modifiedRecords = [];
        foreach ($records as $record) {
            $idFlag = (int) $record['id_order_flag'];
            $flag = $FLAG_DATA[$idFlag] ?? $FLAG_DATA[1];

            $span = '
                <span style="width: 48px; display:block; background:%s;color:#fff;padding:2px 8px;border-radius:4px;" title="%s">
                    <i class="material-icons">%s</i>
                </span>';

            $item = sprintf(
                $span,
                htmlspecialchars($flag['color']),
                htmlspecialchars($flag['name']),
                htmlspecialchars($flag['icon'])
            );

            $record['order_flag_item'] = $item;
            $modifiedRecords[] = $record;
        }

        // Ricrea la collection con i dati modificati
        $recordCollection = new PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection($modifiedRecords);
        $data = new PrestaShop\PrestaShop\Core\Grid\Data\GridData(
            $recordCollection,
            $data->getRecordsTotal(),
            $data->getQuery()
        );

        // Assegna i dati modificati al parametro (passato per riferimento)
        $params['data'] = $data;
    }

    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $params['search_query_builder'];
        /** @var Filters $searchCriteria */
        $searchCriteria = $params['search_criteria'];

        // Aggiungi la colonna id_order_flag alla query
        $OrderFlagTable = _DB_PREFIX_ . ModelMpOrderFlag::$definition['table'];

        $queryBuilder->addSelect("'' as order_flag_item, flag.id_order_flag");

        $queryBuilder->leftJoin('o', $OrderFlagTable, 'flag', 'o.id_order = flag.id_order');

        // Gestisci il filtro per id_order_flag_item
        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ('order_flag_item' == $filterName && '' !== $filterValue && null !== $filterValue) {
                $queryBuilder->andWhere('flag.id_order_flag = :order_flag_item');
                $queryBuilder->setParameter('order_flag_item', $filterValue);

                // Modifica anche la query di conteggio
                $countQueryBuilder = $params['count_query_builder'] ?? null;
                // Applica lo stesso filtro alla query di conteggio
                if ($countQueryBuilder !== null) {
                    $countQueryBuilder->leftJoin('o', $OrderFlagTable, 'flag', 'o.id_order = flag.id_order');
                    $countQueryBuilder->setParameter('order_flag_item', $filterValue);
                }
            }
        }
    }
}
