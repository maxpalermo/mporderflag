<?php

use Doctrine\ORM\QueryBuilder;
use MpSoft\MpOrderFlag\Helpers\GetActionUrl;

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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'mporderflag/vendor/autoload.php';
require_once _PS_MODULE_DIR_ . 'mporderflag/models/autoload.php';

use MpSoft\MpOrderFlag\Install\TableGenerator;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\HtmlColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Search\Filters;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
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
        $this->version = '1.2.0';
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

        // Registra i controller Symfony
        if (!$this->registerSymfonyRoutes()) {
            return false;
        }

        return parent::install()
            && $this->registerHook([
                'actionModuleRegisterHookAfter',
                'actionAdminControllerSetMedia',
                'actionOrderGridDefinitionModifier',
                'actionOrderGridQueryBuilderModifier',
                'displayAdminOrder',
                'displayAdminOrderTop',
            ])
            && $tableInstaller->createTable(ModelMpOrderFlag::$definition)
            && $tableInstaller->createTable(ModelMpOrderFlagItem::$definition);
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Carica i servizi Symfony del modulo
     *
     * @param array $params
     */
    public function hookActionModuleRegisterHookAfter($params)
    {
        // Verifica che il parametro sia un array e che module_name sia definito
        if (!is_array($params) || empty($params) || !isset($params['module_name'])) {
            return;
        }

        // Verifica che il modulo chiamato sia questo modulo
        if ($this->name !== $params['module_name']) {
            return;
        }

        try {
            // Verifica che il service_container sia disponibile
            if (!method_exists($this, 'get') || !$this->get('service_container')) {
                return;
            }

            // Verifica che il file services.yml esista
            $servicesPath = $this->getLocalPath() . 'config/services.yml';
            if (!file_exists($servicesPath)) {
                return;
            }

            // Carica i servizi dal file services.yml
            $containerBuilder = $this->get('service_container');
            $loader = new YamlFileLoader($containerBuilder, new FileLocator($this->getLocalPath() . 'config/'));
            $loader->load('services.yml');
        } catch (\Exception $e) {
            // Gestisci l'errore solo se il controller è disponibile
            if (isset($this->context) && isset($this->context->controller)) {
                $this->context->controller->errors[] = $this->l('Errore durante il caricamento dei servizi Symfony: ') . $e->getMessage();
            }
        }
    }

    public function hookDisplayAdminOrderTop($params)
    {
        $getActionUrl = new GetActionUrl($this->name, $this->adminClassName);
        $getFlagAction = $getActionUrl->getActionUrl('getFlag', ['id_order' => $params['id_order']]);
        $updateFlagAction = $getActionUrl->getActionUrl('updateFlag', ['id_order' => $params['id_order']]);
        $orderFlag = new \ModelMpOrderFlag($params['id_order']);
        if (Validate::isLoadedObject($orderFlag)) {
            $flag = new ModelMpOrderFlagItem($orderFlag->id_order_flag);
            $currentFlag = json_encode($flag->getFields());
        } else {
            $currentFlag = json_encode([]);
        }

        $options = json_encode($this->getComboFlagOptions());

        $script = <<<JS
        <script type="text/javascript">
            const getFlagAction = "{$getFlagAction}";
            const updateFlagAction = "{$updateFlagAction}";
            const orderFlagOptions = {$options};
            const currentFlag = {$currentFlag};
            const event = new CustomEvent('updateOrderFlag', { detail: { idOrder: "{$params['id_order']}" } });
            document.dispatchEvent(event);
        </script>
        JS;

        return $script;
    }

    public function hookDisplayAdminOrder($params)
    {
        // nothing
    }

    protected function getComboFlagOptions()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();

        $sql->select('*')
            ->from(ModelMpOrderFlagItem::$definition['table'])
            ->orderBy(ModelMpOrderFlagItem::$definition['primary']);
        $rows = $db->executeS($sql);
        if (!$rows) {
            $rows = [];
        }

        $options[] = [
            'id' => 0,
            'name' => '--',
            'color' => '#303030',
            'icon' => 'help',
        ];

        foreach ($rows as $row) {
            $options[] = [
                'id' => $row['id_order_flag_item'],
                'name' => $row['name'],
                'color' => $row['color'],
                'icon' => $row['icon'],
            ];
        }

        return $options;
    }

    public function hookActionAdminControllerSetMedia($params)
    {
        $controller = Tools::getValue('controller');
        if (preg_match('/^AdminOrder/i', $controller)) {
            $jsPath = $this->getLocalPath() . 'views/js';
            $cssPath = $this->getLocalPath() . 'views/css';
            $this->context->controller->addCSS([
                $jsPath . '/tippy/scale.css',
                $cssPath . '/select2.min.css',
            ], 'all', 9999);
            $this->context->controller->addJS([
                $jsPath . '/swal2/sweetalert2.all.min.js',
                $jsPath . '/tippy/popper-core2.js',
                $jsPath . '/tippy/tippy.js',
                $jsPath . '/select2.min.js',
                $jsPath . '/Admin/displayAdminOrder.js',
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
            ->addAfter(
                'id_order',
                (new HtmlColumn('order_flag_item'))
                    ->setName($this->trans('Status', [], 'Modules.MpOrderFlag.Admin'))
                    ->setOptions([
                        'field' => 'order_flag_item',
                        'sortable' => true,
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

    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $params['search_query_builder'];
        /** @var Filters $searchCriteria */
        $searchCriteria = $params['search_criteria'];

        // Aggiungi la colonna id_order_flag alla query
        $OrderFlagTable = _DB_PREFIX_ . ModelMpOrderFlag::$definition['table'];
        $OrderFlagTableItem = _DB_PREFIX_ . ModelMpOrderFlagItem::$definition['table'];

        $queryBuilder->addSelect(
            "CONCAT(
                '<span style=\"display:inline-flex;align-items:center;background:', of_item.color, ';color:#fff;padding:2px 8px;border-radius:4px;\" title=\"', of_item.name, '\">',
                '<i class=\"material-icons\">', of_item.icon, '</i>',
                '</span>'
            ) AS order_flag_item",
            'of_item.id_order_flag_item'
        );

        $queryBuilder->leftJoin('o', $OrderFlagTable, 'oflag', 'o.id_order = oflag.id_order');
        $queryBuilder->leftJoin('oflag', $OrderFlagTableItem, 'of_item', 'oflag.id_order_flag = of_item.id_order_flag_item');

        // Gestisci il filtro per id_order_flag_item
        foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
            if ($filterName == 'order_flag_item' && $filterValue !== '' && $filterValue !== null) {
                $queryBuilder->andWhere('of_item.id_order_flag_item = :order_flag_item');
                $queryBuilder->setParameter('order_flag_item', $filterValue);
            }
        }
    }

    /**
     * Registra le rotte Symfony per il modulo
     *
     * @return bool
     */
    private function registerSymfonyRoutes()
    {
        try {
            // In PrestaShop 8.x, i moduli possono registrare le rotte tramite il file routes.yml
            // che viene caricato automaticamente dal sistema
            // Non è necessario registrare manualmente le rotte

            // Verifica che il file routes.yml esista
            $routesPath = $this->getLocalPath() . 'config/routes.yml';
            if (!file_exists($routesPath)) {
                throw new \Exception('File routes.yml non trovato: ' . $routesPath);
            }

            return true;
        } catch (\Exception $e) {
            if (isset($this->context->controller)) {
                $this->context->controller->errors[] = $this->l('Errore durante la registrazione delle rotte Symfony: ') . $e->getMessage();
            }

            return false;
        }
    }

    /**
     * Restituisce le rotte del modulo per PrestaShop
     * Questo metodo è chiamato automaticamente da PrestaShop per caricare le rotte del modulo
     *
     * @return array
     */
    public function getRoutes()
    {
        return [
            'admin_module_routes' => [
                [
                    'route' => 'admin_mporderflag_index',
                    'path' => '/mporderflag',
                    'methods' => ['GET'],
                    'controller' => 'MpOrderFlagController::indexAction',
                ],
                /*
                [
                    'route' => 'admin_mporderflag_view',
                    'path' => '/mporderflag/{id_product}',
                    'methods' => ['GET'],
                    'controller' => 'MpOrderFlagController::viewAction',
                    'defaults' => [
                        '_legacy_controller' => 'AdminMpOrderFlag',
                    ],
                    'requirements' => [
                        'id_product' => '\d+',
                    ],
                ],
                [
                    'route' => 'admin_mpwacart_requests_send_message',
                    'path' => '/mpwacart/requests/send-message',
                    'methods' => ['POST'],
                    'controller' => 'WaCartRequestsController::sendMessageAction',
                    'defaults' => [
                        '_legacy_controller' => 'AdminWaCartRequests',
                    ],
                ],
                [
                    'route' => 'admin_mpwacart_requests_update_status',
                    'path' => '/mpwacart/requests/update-status',
                    'methods' => ['POST'],
                    'controller' => 'WaCartRequestsController::updateStatusAction',
                    'defaults' => [
                        '_legacy_controller' => 'AdminWaCartRequests',
                    ],
                ],
                [
                    'route' => 'admin_mpwacart_requests_delete',
                    'path' => '/mpwacart/requests/{requestId}/delete',
                    'methods' => ['POST'],
                    'controller' => 'WaCartRequestsController::deleteAction',
                    'defaults' => [
                        '_legacy_controller' => 'AdminWaCartRequests',
                    ],
                    'requirements' => [
                        'requestId' => '\d+',
                    ],
                ],
                [
                    'route' => 'admin_mpwacart_requests_bulk_delete',
                    'path' => '/mpwacart/requests/bulk-delete',
                    'methods' => ['POST'],
                    'controller' => 'WaCartRequestsController::bulkDeleteAction',
                    'defaults' => [
                        '_legacy_controller' => 'AdminWaCartRequests',
                    ],
                ],
                */
            ],
        ];
    }

    /**
     * Rimuove le rotte Symfony per il modulo
     *
     * @return bool
     */
    private function unregisterSymfonyRoutes()
    {
        // In PrestaShop, le rotte vengono rimosse automaticamente quando il modulo viene disinstallato
        return true;
    }
}
