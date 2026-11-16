<?php
/**
 * Pagina "Recesso dal contratto": elenco ordini e dichiarazione per ordine (clienti registrati),
 * modulo di recesso pubblico (chiunque altro), pulsante "Conferma recesso" e ricevuta.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DotmrecessoRecessoModuleFrontController extends ModuleFrontController
{
    /** righe prodotto mostrate nel modulo pubblico senza JavaScript */
    const PUBLIC_ROWS = 3;

    /** @var Dotmrecesso */
    public $module;

    public $ssl = true;

    /** @var string list|public|form|notallowed|done */
    protected $dmrStep = 'list';

    protected $dmrErrors = [];

    /** @var Order|null */
    protected $dmrOrder;

    /** @var Customer|null */
    protected $dmrCustomer;

    /** @var array|null */
    protected $dmrInfo;

    /** @var DotmRecessoRequest|null */
    protected $dmrRequest;

    protected $dmrValues = [];

    public function setMedia()
    {
        parent::setMedia();
        $path = 'modules/' . $this->module->name . '/views/';
        $this->registerStylesheet('dotmrecesso-front', $path . 'css/front.css', ['media' => 'all', 'priority' => 200]);
        $this->registerJavascript('dotmrecesso-front', $path . 'js/front.js', ['position' => 'bottom', 'priority' => 200]);
    }

    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->module->t('meta_title');
        $page['meta']['robots'] = 'noindex';

        return $page;
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        if ($this->context->customer->isLogged()) {
            $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        }
        $breadcrumb['links'][] = [
            'title' => $this->module->t('page_title'),
            'url' => $this->module->getFormUrl(),
        ];

        return $breadcrumb;
    }

    public function postProcess()
    {
        // ricevuta (dopo il redirect)
        if (Tools::getValue('done')) {
            $request = new DotmRecessoRequest((int) Tools::getValue('done'));
            if (Validate::isLoadedObject($request) && hash_equals($request->getReceiptKey(), (string) Tools::getValue('key'))) {
                $this->dmrRequest = $request;
                $this->dmrStep = 'done';

                return;
            }
        }

        if (!$this->isRegisteredCustomer()) {
            $this->initPublicValues();
            $this->dmrStep = 'public';
            if (Tools::isSubmit('dmr_public')) {
                $this->processPublic();
            }

            return;
        }

        // individuazione dell'ordine (clienti registrati)
        $idOrder = (int) Tools::getValue('id_order');
        if ($idOrder) {
            $order = new Order($idOrder);
            if (Validate::isLoadedObject($order) && (int) $order->id_customer === (int) $this->context->customer->id) {
                $this->dmrOrder = $order;
            }
        }

        if (!$this->dmrOrder) {
            $this->dmrStep = 'list';

            return;
        }

        $this->dmrCustomer = new Customer((int) $this->dmrOrder->id_customer);
        $this->dmrInfo = $this->module->getWithdrawalInfo($this->dmrOrder);
        $this->dmrStep = $this->dmrInfo['eligible'] ? 'form' : 'notallowed';

        $this->dmrValues = [
            'name' => trim($this->dmrCustomer->firstname . ' ' . $this->dmrCustomer->lastname),
            'reason' => '',
            'items' => [],
        ];
        foreach ($this->dmrInfo['items'] as $item) {
            $this->dmrValues['items'][$item['id_order_detail']] = ['checked' => true, 'qty' => $item['qty']];
        }

        if ($this->dmrStep === 'form' && Tools::isSubmit('dmr_confirm')) {
            $this->processConfirm();
        }
    }

    protected function isRegisteredCustomer()
    {
        return $this->context->customer->isLogged() && !$this->context->customer->is_guest;
    }

    protected function initPublicValues()
    {
        $customer = $this->context->customer;
        $logged = $customer->isLogged();
        $this->dmrValues = [
            'firstname' => $logged ? $customer->firstname : '',
            'lastname' => $logged ? $customer->lastname : '',
            // dal box nel dettaglio ordine (anche "Segui il tuo ordine" degli ospiti)
            'order' => Tools::substr(trim(strip_tags((string) Tools::getValue('ref'))), 0, 64),
            'email' => $logged ? $customer->email : '',
            'scope' => '',
            'rows' => array_fill(0, self::PUBLIC_ROWS, ['name' => '', 'reference' => '', 'qty' => '']),
            'info' => '',
        ];
    }

    protected function processPublic()
    {
        $text = function ($field, $max) {
            return Tools::substr(trim(strip_tags((string) Tools::getValue($field))), 0, $max);
        };

        $values = [
            'firstname' => $text('dmr_firstname', 100),
            'lastname' => $text('dmr_lastname', 100),
            'order' => $text('dmr_order', 64),
            'email' => strtolower($text('dmr_email', 255)),
            'scope' => in_array(Tools::getValue('dmr_scope'), ['whole', 'partial'], true) ? Tools::getValue('dmr_scope') : '',
            'rows' => [],
            'info' => $text('dmr_info', 2000),
        ];
        $names = (array) Tools::getValue('dmr_item_name', []);
        $refs = (array) Tools::getValue('dmr_item_ref', []);
        $qtys = (array) Tools::getValue('dmr_item_qty', []);
        $items = [];
        foreach (array_slice(array_keys($names + $refs + $qtys), 0, 50) as $i) {
            $row = [
                'name' => isset($names[$i]) ? Tools::substr(trim(strip_tags((string) $names[$i])), 0, 255) : '',
                'reference' => isset($refs[$i]) ? Tools::substr(trim(strip_tags((string) $refs[$i])), 0, 64) : '',
                'qty' => isset($qtys[$i]) && (int) $qtys[$i] > 0 ? (int) $qtys[$i] : '',
            ];
            $values['rows'][] = $row;
            if ($row['name'] !== '' || $row['reference'] !== '') {
                $items[] = ['name' => $row['name'], 'reference' => $row['reference'], 'qty' => (int) $row['qty']];
            }
        }
        while (count($values['rows']) < self::PUBLIC_ROWS) {
            $values['rows'][] = ['name' => '', 'reference' => '', 'qty' => ''];
        }
        $this->dmrValues = $values;

        // campo trappola, invisibile alle persone
        if (Tools::getValue('dmr_website') !== false && Tools::getValue('dmr_website') !== '') {
            $this->dmrErrors[] = $this->module->t('err_save');

            return;
        }
        if (!$this->isTokenValid()) {
            $this->dmrErrors[] = $this->module->t('err_token');

            return;
        }

        if ($values['firstname'] === '' || !Validate::isGenericName($values['firstname'])) {
            $this->dmrErrors[] = $this->module->t('err_firstname');
        }
        if ($values['lastname'] === '' || !Validate::isGenericName($values['lastname'])) {
            $this->dmrErrors[] = $this->module->t('err_lastname');
        }
        if ($values['order'] === '' || !Validate::isGenericName($values['order'])) {
            $this->dmrErrors[] = $this->module->t('err_order');
        }
        if (!Validate::isEmail($values['email'])) {
            $this->dmrErrors[] = $this->module->t('err_email');
        }
        if ($values['scope'] === '') {
            $this->dmrErrors[] = $this->module->t('err_scope');
        } elseif ($values['scope'] === 'partial' && !$items) {
            $this->dmrErrors[] = $this->module->t('err_items');
        }
        if ($this->dmrErrors) {
            return;
        }
        if ($this->module->isPublicRateLimited()) {
            $this->dmrErrors[] = $this->module->t('err_rate');

            return;
        }

        $request = $this->module->createPublicRequest(
            $values['order'],
            $values['firstname'],
            $values['lastname'],
            $values['email'],
            $values['scope'] === 'whole',
            $items,
            $values['info']
        );
        if (!$request) {
            $this->dmrErrors[] = $this->module->t('err_save');

            return;
        }

        Tools::redirect($this->module->getFormUrl(['done' => (int) $request->id, 'key' => $request->getReceiptKey()]));
    }

    protected function processConfirm()
    {
        if (!$this->isTokenValid()) {
            $this->dmrErrors[] = $this->module->t('err_token');

            return;
        }

        $postedItems = (array) Tools::getValue('dmr_items', []);
        $postedQty = (array) Tools::getValue('dmr_qty', []);
        $selected = [];
        $this->dmrValues['items'] = [];
        foreach ($this->dmrInfo['items'] as $item) {
            $id = (int) $item['id_order_detail'];
            $checked = isset($postedItems[$id]);
            $qty = isset($postedQty[$id]) ? (int) $postedQty[$id] : 0;
            $this->dmrValues['items'][$id] = ['checked' => $checked, 'qty' => $qty > 0 ? $qty : $item['qty']];
            if (!$checked) {
                continue;
            }
            if ($qty < 1 || $qty > $item['qty']) {
                $this->dmrErrors[] = $this->module->t('err_qty');

                return;
            }
            $selected[$id] = $qty;
        }
        if (!$selected) {
            $this->dmrErrors[] = $this->module->t('err_no_items');

            return;
        }

        // nome: per i clienti registrati è già noto e non va richiesto di nuovo
        $name = $this->dmrValues['name'];

        $reason = trim(strip_tags((string) Tools::getValue('dmr_reason')));
        $this->dmrValues['reason'] = $reason;

        $request = $this->module->createRequest(
            $this->dmrOrder,
            $this->dmrCustomer,
            $name,
            $this->dmrCustomer->email,
            $selected,
            $reason,
            $this->dmrInfo
        );
        if (!$request) {
            $this->dmrErrors[] = $this->module->t('err_save');

            return;
        }

        Tools::redirect($this->module->getFormUrl(['done' => (int) $request->id, 'key' => $request->getReceiptKey()]));
    }

    public function initContent()
    {
        parent::initContent();

        $m = $this->module;
        $isRegistered = $this->isRegisteredCustomer();
        $vars = [
            't' => $m->getStrings(),
            'step' => $this->dmrStep,
            'errors' => $this->dmrErrors,
            'is_registered' => $isRegistered,
            'days' => $m->getDays(),
            'intro' => $m->t('intro', ['days' => $m->getDays()]),
            'url' => $m->getFormUrl(),
            'url_login' => $this->context->link->getPageLink('authentication', true, null, ['back' => $m->getFormUrl()]),
            'token' => Tools::getToken(false),
            'public_login' => $m->t('public_login', [
                'url' => htmlspecialchars($this->context->link->getPageLink('authentication', true, null, ['back' => $m->getFormUrl()]), ENT_QUOTES, 'UTF-8'),
            ]),
            'values' => $this->dmrValues,
            'css_vars' => $m->getCssVars(),
        ];

        if ($this->dmrStep === 'list') {
            $vars['orders'] = $this->getCustomerOrders();
        }

        if ($this->dmrOrder && in_array($this->dmrStep, ['form', 'notallowed'], true)) {
            $items = $this->dmrInfo['items'];
            foreach ($items as &$item) {
                $item['of'] = $m->t('of_qty', ['n' => $item['qty']]);
            }
            unset($item);
            $vars['order'] = [
                'title' => $m->t('order_label', ['reference' => $this->dmrOrder->reference]),
                'id' => (int) $this->dmrOrder->id,
                'reference' => $this->dmrOrder->reference,
                'date' => Tools::displayDate($this->dmrOrder->date_add),
                'statement' => $m->t('form_statement', [
                    'reference' => $this->dmrOrder->reference,
                    'date' => Tools::displayDate($this->dmrOrder->date_add),
                ]),
                'email' => $this->dmrCustomer->email,
                'items' => $items,
                'status_label' => $m->getInfoLabel($this->dmrInfo),
                'deadline' => $this->dmrInfo['code'] === 'open_until'
                    ? $m->t('form_deadline', ['date' => Tools::displayDate($this->dmrInfo['deadline'])])
                    : $m->t('box_eligible_open', ['days' => $m->getDays()]),
            ];
        }

        if ($this->dmrStep === 'done' && $this->dmrRequest) {
            $order = new Order((int) $this->dmrRequest->id_order);
            $vars['receipt'] = [
                'id' => (int) $this->dmrRequest->id,
                'datetime' => Tools::displayDate($this->dmrRequest->date_add, true),
                'reference' => $this->dmrRequest->order_reference,
                'order_date' => Validate::isLoadedObject($order) ? Tools::displayDate($order->date_add) : '',
                'name' => $this->dmrRequest->customer_name,
                'email' => $this->dmrRequest->customer_email,
                'items' => array_map([$m, 'formatItem'], $this->dmrRequest->getItems()),
                'whole_order' => (bool) $this->dmrRequest->whole_order,
                'reason' => $this->dmrRequest->reason,
                'text' => $m->t('done_text', ['email' => $this->dmrRequest->customer_email]),
            ];
        }

        $this->context->smarty->assign([
            'dmr' => $vars,
            'dmr_layout' => $isRegistered ? 'customer/page.tpl' : 'page.tpl',
        ]);

        $this->setTemplate('module:dotmrecesso/views/templates/front/recesso.tpl');
    }

    protected function getCustomerOrders()
    {
        $orders = [];
        foreach (Order::getCustomerOrders((int) $this->context->customer->id) as $row) {
            $order = new Order((int) $row['id_order']);
            if (!Validate::isLoadedObject($order)) {
                continue;
            }
            $info = $this->module->getWithdrawalInfo($order);
            $last = $info['requests'] ? $info['requests'][0] : null;
            $orders[] = [
                'id' => (int) $order->id,
                'reference' => $order->reference,
                'title' => $this->module->t('order_label', ['reference' => $order->reference]),
                'placed' => $this->module->t('order_placed', ['date' => Tools::displayDate($order->date_add)]),
                'total' => Tools::getContextLocale($this->context)->formatPrice(
                    $order->total_paid,
                    Currency::getIsoCodeById((int) $order->id_currency)
                ),
                'eligible' => $info['eligible'],
                'label' => $this->module->getInfoLabel($info),
                'requested' => $last ? $this->module->t('state_requested', ['date' => Tools::displayDate($last['date_add'], true)]) : '',
                'url' => $this->module->getFormUrl(['id_order' => (int) $order->id]),
            ];
        }

        return $orders;
    }
}
