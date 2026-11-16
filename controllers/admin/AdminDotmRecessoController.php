<?php
/**
 * Ordini → Recessi online: elenco delle dichiarazioni ricevute, dettaglio e cambio stato.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'dotmrecesso/classes/DotmRecessoRequest.php';

class AdminDotmRecessoController extends ModuleAdminController
{
    /** @var Dotmrecesso */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'dotmrecesso_request';
        $this->className = 'DotmRecessoRequest';
        $this->identifier = 'id_dotmrecesso_request';
        $this->lang = false;
        $this->allow_export = true;
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
        $this->_select = 'IF(a.`id_order` > 0, 1, 0) AS `order_found`';

        parent::__construct();

        $this->addRowAction('view');

        $this->fields_list = [
            'id_dotmrecesso_request' => ['title' => 'N.', 'align' => 'center', 'class' => 'fixed-width-xs'],
            'date_add' => ['title' => 'Data e ora invio', 'type' => 'datetime'],
            'order_reference' => ['title' => 'Ordine'],
            'customer_name' => ['title' => 'Cliente'],
            'customer_email' => ['title' => 'Email'],
            'order_found' => [
                'title' => 'Ordine trovato',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'havingFilter' => true,
            ],
            'whole_order' => ['title' => 'Intero ordine', 'type' => 'bool', 'align' => 'center', 'class' => 'fixed-width-sm'],
            'status' => [
                'title' => 'Stato',
                'type' => 'select',
                'list' => $this->getStatusList(),
                'filter_key' => 'a!status',
                'callback' => 'renderStatus',
            ],
        ];
    }

    protected function getStatusList()
    {
        $list = [];
        foreach (DotmRecessoRequest::getStatuses() as $status) {
            $list[$status] = $this->module->t('status_' . $status, [], (int) Configuration::get('PS_LANG_DEFAULT'));
        }

        return $list;
    }

    public function renderStatus($value)
    {
        $classes = [
            DotmRecessoRequest::STATUS_RECEIVED => 'warning',
            DotmRecessoRequest::STATUS_PROCESSING => 'info',
            DotmRecessoRequest::STATUS_COMPLETED => 'success',
            DotmRecessoRequest::STATUS_REJECTED => 'danger',
        ];
        $list = $this->getStatusList();
        $label = isset($list[$value]) ? $list[$value] : $value;
        $class = isset($classes[$value]) ? $classes[$value] : 'default';

        return '<span class="label label-' . $class . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    public function initToolbar()
    {
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn['config'] = [
            'href' => $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->module->name]),
            'desc' => 'Impostazioni',
            'icon' => 'process-icon-cogs',
        ];
        parent::initPageHeaderToolbar();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitDmrStatus')) {
            $request = new DotmRecessoRequest((int) Tools::getValue('id_dotmrecesso_request'));
            $status = (string) Tools::getValue('dmr_status');
            if (!Validate::isLoadedObject($request) || !in_array($status, DotmRecessoRequest::getStatuses(), true)) {
                $this->errors[] = 'Richiesta o stato non valido.';
            } else {
                $request->status = $status;
                if ($request->update()) {
                    $this->confirmations[] = 'Stato aggiornato.';
                } else {
                    $this->errors[] = 'Impossibile aggiornare lo stato.';
                }
            }
        }

        return parent::postProcess();
    }

    public function renderView()
    {
        /** @var DotmRecessoRequest $request */
        $request = $this->loadObject();
        if (!Validate::isLoadedObject($request)) {
            return '';
        }

        $order = new Order((int) $request->id_order);
        $this->context->smarty->assign([
            'dmr_request' => [
                'id' => (int) $request->id,
                'datetime' => Tools::displayDate($request->date_add, true),
                'updated' => Tools::displayDate($request->date_upd, true),
                'reference' => $request->order_reference,
                'order_date' => Validate::isLoadedObject($order) ? Tools::displayDate($order->date_add) : '',
                'order_link' => Validate::isLoadedObject($order)
                    ? $this->context->link->getAdminLink('AdminOrders', true, ['orderId' => (int) $order->id, 'route' => 'admin_orders_view'], ['id_order' => (int) $order->id, 'vieworder' => 1])
                    : '',
                'name' => $request->customer_name,
                'email' => $request->customer_email,
                'items' => array_map([$this->module, 'formatItem'], $request->getItems()),
                'whole_order' => (bool) $request->whole_order,
                'reason' => $request->reason,
                'status' => $request->status,
                'ip' => $request->ip,
                'user_agent' => $request->user_agent,
            ],
            'dmr_statuses' => $this->getStatusList(),
            'dmr_form_action' => self::$currentIndex . '&token=' . $this->token . '&id_dotmrecesso_request=' . (int) $request->id . '&viewdotmrecesso_request',
            'dmr_back' => self::$currentIndex . '&token=' . $this->token,
        ]);

        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/view.tpl');
    }
}
