<?php
/**
 * Recesso online — funzione "Recedere dal contratto qui" (art. 54-bis Codice del Consumo,
 * d.lgs. 209/2025, recepimento Direttiva UE 2023/2673).
 *
 * @author dotm.it
 * @license MIT (vedi LICENSE)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/DotmRecessoRequest.php';

class Dotmrecesso extends Module
{
    const CFG_DAYS = 'DOTMRECESSO_DAYS';
    const CFG_GRACE = 'DOTMRECESSO_SHIPPING_GRACE';
    const CFG_NOTIFY = 'DOTMRECESSO_NOTIFY_EMAIL';
    const CFG_EXCLUDED = 'DOTMRECESSO_EXCLUDED_STATES';
    const CFG_COLOR = 'DOTMRECESSO_COLOR';

    const DEFAULT_COLOR = '#2b2b2b';

    /** dichiarazioni dal form pubblico accettate per IP in un'ora */
    const PUBLIC_MAX_PER_HOUR = 5;

    /** @var array<string, array> */
    protected $strings = [];

    public function __construct()
    {
        $this->name = 'dotmrecesso';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'dotm.it';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.7.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = 'Recesso online (art. 54-bis)';
        $this->description = 'Pulsante "Recedere dal contratto qui" nell\'area clienti, nel dettaglio ordine e in una pagina pubblica con modulo di recesso, con ricevuta via email al cliente e notifica al negozio.';
        $this->confirmUninstall = 'Disinstallando il modulo verranno cancellate anche le dichiarazioni di recesso registrate. Continuare?';
    }

    /* ------------------------------------------------------------------
     * Installazione
     * ------------------------------------------------------------------ */

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->installTab()
            && Configuration::updateValue(self::CFG_DAYS, 14)
            && Configuration::updateValue(self::CFG_GRACE, 10)
            && Configuration::updateValue(self::CFG_NOTIFY, (string) Configuration::get('PS_SHOP_EMAIL'))
            && Configuration::updateValue(self::CFG_COLOR, '')
            && Configuration::updateValue(self::CFG_EXCLUDED, implode(',', array_filter([
                (int) Configuration::get('PS_OS_CANCELED'),
                (int) Configuration::get('PS_OS_REFUND'),
                (int) Configuration::get('PS_OS_ERROR'),
            ])))
            && $this->registerHook([
                'moduleRoutes',
                'displayCustomerAccount',
                'displayOrderDetail',
                'displayAdminOrderSide',
                'actionFrontControllerSetMedia',
            ]);
    }

    public function uninstall()
    {
        foreach ([self::CFG_DAYS, self::CFG_GRACE, self::CFG_NOTIFY, self::CFG_EXCLUDED, self::CFG_COLOR] as $key) {
            Configuration::deleteByName($key);
        }

        return $this->uninstallTab()
            && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'dotmrecesso_request`')
            && parent::uninstall();
    }

    protected function installDb()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'dotmrecesso_request` (
                `id_dotmrecesso_request` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_order` INT UNSIGNED NOT NULL,
                `id_customer` INT UNSIGNED NOT NULL DEFAULT 0,
                `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
                `id_lang` INT UNSIGNED NOT NULL DEFAULT 1,
                `order_reference` VARCHAR(64) NOT NULL DEFAULT \'\',
                `customer_name` VARCHAR(255) NOT NULL,
                `customer_email` VARCHAR(255) NOT NULL,
                `items` TEXT NULL,
                `whole_order` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `reason` TEXT NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT \'received\',
                `ip` VARCHAR(64) NOT NULL DEFAULT \'\',
                `user_agent` VARCHAR(255) NOT NULL DEFAULT \'\',
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_dotmrecesso_request`),
                KEY `id_order` (`id_order`),
                KEY `id_customer` (`id_customer`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        );
    }

    protected function installTab()
    {
        if (Tab::getIdFromClassName('AdminDotmRecesso')) {
            return true;
        }
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminDotmRecesso';
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
        $tab->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = $lang['iso_code'] === 'it' ? 'Recessi online' : 'Online withdrawals';
        }

        return (bool) $tab->add();
    }

    protected function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminDotmRecesso');
        if (!$idTab) {
            return true;
        }
        $tab = new Tab($idTab);

        return (bool) $tab->delete();
    }

    /* ------------------------------------------------------------------
     * Testi (lang/<iso>.php; per le lingue senza file: inglese, poi italiano)
     * ------------------------------------------------------------------ */

    public function getStrings($idLang = null)
    {
        $iso = $idLang ? Language::getIsoById((int) $idLang) : $this->context->language->iso_code;
        $iso = preg_replace('/[^a-z]/', '', strtolower((string) $iso));
        if (!isset($this->strings[$iso])) {
            $strings = include __DIR__ . '/lang/it.php';
            if ($iso !== 'it') {
                $file = __DIR__ . '/lang/' . $iso . '.php';
                foreach (array_unique([__DIR__ . '/lang/en.php', is_file($file) ? $file : __DIR__ . '/lang/en.php']) as $path) {
                    $local = include $path;
                    $strings = array_merge($strings, is_array($local) ? $local : []);
                }
            }
            $this->strings[$iso] = $strings;
        }

        return $this->strings[$iso];
    }

    public function t($key, array $params = [], $idLang = null)
    {
        $strings = $this->getStrings($idLang);
        $text = isset($strings[$key]) ? $strings[$key] : $key;
        if ($params) {
            $replace = [];
            foreach ($params as $k => $v) {
                $replace['{' . $k . '}'] = $v;
            }
            $text = strtr($text, $replace);
        }

        return $text;
    }

    /* ------------------------------------------------------------------
     * Regole del recesso
     * ------------------------------------------------------------------ */

    public function getDays()
    {
        $days = (int) Configuration::get(self::CFG_DAYS);

        return $days > 0 ? $days : 14;
    }

    public function getExcludedStates()
    {
        return array_filter(array_map('intval', explode(',', (string) Configuration::get(self::CFG_EXCLUDED))));
    }

    /**
     * Termine ultimo per il recesso (Y-m-d H:i:s) o null se l'ordine non è ancora stato spedito:
     * - dalla data di passaggio allo stato "Consegnato" + giorni di recesso;
     * - se manca la consegna ma l'ordine è spedito: data di spedizione + giorni + margine di trasporto.
     */
    public function getDeadline(Order $order)
    {
        $days = $this->getDays();
        $db = Db::getInstance();

        $delivered = $db->getValue(
            'SELECT MIN(`date_add`) FROM `' . _DB_PREFIX_ . 'order_history`
            WHERE `id_order` = ' . (int) $order->id . ' AND `id_order_state` = ' . (int) Configuration::get('PS_OS_DELIVERED')
        );
        if ($delivered) {
            return date('Y-m-d 23:59:59', strtotime($delivered . ' +' . $days . ' days'));
        }

        $shipped = $db->getValue(
            'SELECT MIN(oh.`date_add`) FROM `' . _DB_PREFIX_ . 'order_history` oh
            INNER JOIN `' . _DB_PREFIX_ . 'order_state` os ON os.`id_order_state` = oh.`id_order_state`
            WHERE oh.`id_order` = ' . (int) $order->id . ' AND os.`shipped` = 1'
        );
        if ($shipped) {
            $grace = max(0, (int) Configuration::get(self::CFG_GRACE));

            return date('Y-m-d 23:59:59', strtotime($shipped . ' +' . ($days + $grace) . ' days'));
        }

        return null;
    }

    /**
     * Quantità già oggetto di recesso per riga d'ordine (escluse le richieste respinte).
     */
    protected function getWithdrawnQuantities(array $requests)
    {
        $qty = [];
        foreach ($requests as $row) {
            if ($row['status'] === DotmRecessoRequest::STATUS_REJECTED) {
                continue;
            }
            $items = json_decode((string) $row['items'], true);
            foreach (is_array($items) ? $items : [] as $item) {
                // le righe scritte a mano nel form pubblico non sono collegate all'ordine
                $id = isset($item['id_order_detail']) ? (int) $item['id_order_detail'] : 0;
                if (!$id) {
                    continue;
                }
                $qty[$id] = (isset($qty[$id]) ? $qty[$id] : 0) + (int) $item['qty'];
            }
        }

        return $qty;
    }

    /**
     * Stato del recesso per un ordine.
     *
     * @return array{eligible: bool, code: string, deadline: ?string, items: array, requests: array}
     */
    public function getWithdrawalInfo(Order $order)
    {
        $requests = DotmRecessoRequest::getByOrder($order->id);
        $withdrawn = $this->getWithdrawnQuantities($requests);

        $items = [];
        foreach ($order->getProductsDetail() as $p) {
            $id = (int) $p['id_order_detail'];
            $remaining = (int) $p['product_quantity'] - (isset($withdrawn[$id]) ? $withdrawn[$id] : 0);
            if ($remaining > 0) {
                $items[] = [
                    'id_order_detail' => $id,
                    'name' => $p['product_name'],
                    'reference' => $p['product_reference'],
                    'ordered' => (int) $p['product_quantity'],
                    'qty' => $remaining,
                ];
            }
        }

        $info = [
            'eligible' => false,
            'code' => '',
            'deadline' => $this->getDeadline($order),
            'items' => $items,
            'requests' => $requests,
        ];

        if (in_array((int) $order->getCurrentState(), $this->getExcludedStates(), true)) {
            $info['code'] = 'excluded';
        } elseif (!$items) {
            $info['code'] = 'done';
        } elseif ($info['deadline'] && strtotime($info['deadline']) < time()) {
            $info['code'] = 'expired';
        } else {
            $info['eligible'] = true;
            $info['code'] = $info['deadline'] ? 'open_until' : 'open';
        }

        return $info;
    }

    /**
     * Testo breve dello stato, per elenchi e box.
     */
    public function getInfoLabel(array $info)
    {
        $days = $this->getDays();
        switch ($info['code']) {
            case 'open_until':
                return $this->t('deadline_until', ['date' => Tools::displayDate($info['deadline'])]);
            case 'open':
                return $this->t('deadline_open', ['days' => $days]);
            case 'expired':
                return $this->t('state_expired', ['date' => Tools::displayDate($info['deadline'])]);
            case 'done':
                return $this->t('state_done');
            default:
                return $this->t('state_excluded');
        }
    }

    public function getFormUrl(array $params = [])
    {
        return $this->context->link->getModuleLink($this->name, 'recesso', $params, true);
    }

    /**
     * Colore dei pulsanti (configurazione), validato come #rrggbb.
     */
    public function getColor()
    {
        $color = strtolower(trim((string) Configuration::get(self::CFG_COLOR)));

        return preg_match('/^#[0-9a-f]{6}$/', $color) ? $color : '';
    }

    /**
     * Variabili CSS per il colore configurato, da mettere nell'attributo style dei contenitori.
     */
    public function getCssVars()
    {
        $color = $this->getColor();
        if ($color === '') {
            return '';
        }
        $dark = '#';
        foreach (str_split(substr($color, 1), 2) as $hex) {
            $dark .= sprintf('%02x', (int) round(hexdec($hex) * 0.75));
        }

        return '--dmr-accent:' . $color . ';--dmr-accent-dark:' . $dark . ';';
    }

    /**
     * Ordine indicato nel form pubblico: riferimento (o numero) che corrisponde all'email del cliente.
     *
     * @return Order|null
     */
    public function findOrder($number, $email)
    {
        $number = strtoupper(ltrim(trim((string) $number), '#'));
        $email = strtolower(trim((string) $email));
        if ($number === '' || !Validate::isEmail($email)) {
            return null;
        }

        $candidates = [];
        if (Validate::isReference($number)) {
            foreach (Order::getByReference($number) as $order) {
                $candidates[] = $order;
            }
        }
        if (!$candidates && ctype_digit($number)) {
            $candidates[] = new Order((int) $number);
        }

        foreach ($candidates as $order) {
            if (!Validate::isLoadedObject($order)) {
                continue;
            }
            $customer = new Customer((int) $order->id_customer);
            if (Validate::isLoadedObject($customer) && strtolower($customer->email) === $email) {
                return $order;
            }
        }

        return null;
    }

    /**
     * Limite anti-abuso del form pubblico: dichiarazioni inviate dallo stesso IP nell'ultima ora.
     */
    public function isPublicRateLimited()
    {
        $count = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'dotmrecesso_request`
            WHERE `ip` = \'' . pSQL(Tools::substr((string) Tools::getRemoteAddr(), 0, 64)) . '\'
            AND `date_add` > \'' . pSQL(date('Y-m-d H:i:s', time() - 3600)) . '\''
        );

        return $count >= self::PUBLIC_MAX_PER_HOUR;
    }

    /* ------------------------------------------------------------------
     * Registrazione della dichiarazione
     * ------------------------------------------------------------------ */

    /**
     * @param array $selected id_order_detail => quantità
     *
     * @return DotmRecessoRequest|false
     */
    public function createRequest(Order $order, Customer $customer, $name, $email, array $selected, $reason, array $info)
    {
        $items = [];
        $whole = true;
        foreach ($info['items'] as $item) {
            $qty = isset($selected[$item['id_order_detail']]) ? (int) $selected[$item['id_order_detail']] : 0;
            if ($qty < $item['qty']) {
                $whole = false;
            }
            if ($qty > 0) {
                $items[] = [
                    'id_order_detail' => (int) $item['id_order_detail'],
                    'name' => $item['name'],
                    'reference' => $item['reference'],
                    'qty' => $qty,
                ];
            }
        }
        if (!$items) {
            return false;
        }

        return $this->saveRequest($order, (int) $customer->id, $order->reference, $name, $email, $items, $whole && empty($info['requests']), $reason);
    }

    /**
     * Dichiarazione dal form pubblico: viene sempre registrata e confermata, anche se l'ordine
     * non viene trovato (la verifica spetta al negozio).
     *
     * @param array $items [{name, reference, qty}] scritti dal cliente (solo recesso parziale)
     *
     * @return DotmRecessoRequest|false
     */
    public function createPublicRequest($number, $firstname, $lastname, $email, $whole, array $items, $info)
    {
        $order = $this->findOrder($number, $email);
        $orderItems = [];
        if ($order) {
            $orderInfo = $this->getWithdrawalInfo($order);
            if ($whole) {
                foreach ($orderInfo['items'] as $item) {
                    $orderItems[] = [
                        'id_order_detail' => (int) $item['id_order_detail'],
                        'name' => $item['name'],
                        'reference' => $item['reference'],
                        'qty' => (int) $item['qty'],
                    ];
                }
            }
        }

        $rows = [];
        foreach ($whole ? [] : $items as $item) {
            $rows[] = [
                'id_order_detail' => 0,
                'name' => Tools::substr($item['name'], 0, 255),
                'reference' => Tools::substr($item['reference'], 0, 64),
                'qty' => max(0, (int) $item['qty']),
            ];
        }

        return $this->saveRequest(
            $order,
            $order ? (int) $order->id_customer : (int) $this->context->customer->id,
            $order ? $order->reference : Tools::substr(strtoupper(ltrim(trim((string) $number), '#')), 0, 64),
            trim($firstname . ' ' . $lastname),
            $email,
            $whole ? $orderItems : $rows,
            (bool) $whole,
            $info
        );
    }

    /**
     * @param Order|null $order
     *
     * @return DotmRecessoRequest|false
     */
    protected function saveRequest($order, $idCustomer, $reference, $name, $email, array $items, $whole, $reason)
    {
        $request = new DotmRecessoRequest();
        $request->id_order = $order ? (int) $order->id : 0;
        $request->id_customer = (int) $idCustomer;
        $request->id_shop = $order ? (int) $order->id_shop : (int) $this->context->shop->id;
        $request->id_lang = (int) $this->context->language->id;
        $request->order_reference = $reference;
        $request->customer_name = Tools::substr($name, 0, 255);
        $request->customer_email = $email;
        $request->items = json_encode($items, JSON_UNESCAPED_UNICODE);
        $request->whole_order = (bool) $whole;
        $request->reason = Tools::substr(strip_tags($reason), 0, 2000);
        $request->status = DotmRecessoRequest::STATUS_RECEIVED;
        $request->ip = Tools::substr((string) Tools::getRemoteAddr(), 0, 64);
        $request->user_agent = Tools::substr(isset($_SERVER['HTTP_USER_AGENT']) ? strip_tags($_SERVER['HTTP_USER_AGENT']) : '', 0, 255);

        if (!$request->add()) {
            return false;
        }

        if ($order) {
            $this->addOrderMessage($order, $request);
        }
        $this->sendMails($order, $request);

        return $request;
    }

    /**
     * Testo di un prodotto: "2 × Nome (codice)", senza quantità se non indicata.
     */
    public function formatItem(array $item)
    {
        $name = trim($item['name'] . ($item['reference'] ? ' (' . $item['reference'] . ')' : ''));

        return ((int) $item['qty'] > 0 ? (int) $item['qty'] . ' × ' : '') . $name;
    }

    public function formatItems(DotmRecessoRequest $request, $html, $idLang = null)
    {
        $lines = [];
        foreach ($request->getItems() as $item) {
            $text = $this->formatItem($item);
            $lines[] = $html ? '<li>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</li>' : '- ' . $text;
        }
        $whole = $request->whole_order ? $this->t('whole_order', [], $idLang) : '';
        if ($html) {
            return ($whole !== '' ? '<strong>' . htmlspecialchars($whole, ENT_QUOTES, 'UTF-8') . '</strong>' : '')
                . ($lines ? '<ul style="margin:0;padding-left:18px;">' . implode('', $lines) . '</ul>' : '');
        }

        return implode("\n", array_filter(array_merge([$whole], $lines), 'strlen'));
    }

    protected function addOrderMessage(Order $order, DotmRecessoRequest $request)
    {
        $items = [];
        foreach ($request->getItems() as $item) {
            $items[] = $this->formatItem($item);
        }
        $text = $this->t('order_message', [
            'id' => $request->id,
            'date' => Tools::displayDate($request->date_add, true),
            'items' => implode(', ', $items),
        ], (int) Configuration::get('PS_LANG_DEFAULT'));

        $message = new Message();
        $message->id_order = (int) $order->id;
        $message->id_customer = (int) $order->id_customer;
        $message->message = Tools::substr(strip_tags($text), 0, 1600);
        $message->private = 1;
        try {
            $message->add();
        } catch (Exception $e) {
            PrestaShopLogger::addLog('dotmrecesso: messaggio ordine non salvato - ' . $e->getMessage(), 2, null, 'Order', (int) $order->id);
        }
    }

    /**
     * Lingua per le email: quella del cliente se il modulo ha i template, altrimenti inglese o italiano.
     */
    protected function getMailLang($idLang)
    {
        $candidates = [(int) $idLang, (int) Language::getIdByIso('en'), (int) Language::getIdByIso('it')];
        foreach ($candidates as $id) {
            $iso = $id ? Language::getIsoById($id) : '';
            if ($iso && is_file($this->getLocalPath() . 'mails/' . $iso . '/dotmrecesso_customer.html')) {
                return $id;
            }
        }

        return (int) $idLang;
    }

    /**
     * @param Order|null $order
     */
    protected function sendMails($order, DotmRecessoRequest $request)
    {
        $mailDir = $this->getLocalPath() . 'mails/';
        $shopId = $order ? (int) $order->id_shop : (int) $request->id_shop;
        $orderId = $order ? (int) $order->id : null;

        $send = function ($idLang, $template, $subjectKey, $to, $toName, $replyTo = null, $replyToName = null) use ($order, $request, $mailDir, $shopId) {
            $reason = trim((string) $request->reason);
            $reference = $request->order_reference;
            $orderLine = $order
                ? $this->t('order_line', ['reference' => $reference, 'date' => Tools::displayDate($order->date_add)], $idLang)
                : $reference;
            if ($order) {
                $check = $this->t('check_found', ['state' => $this->getInfoLabel($this->getWithdrawalInfo($order))], $idLang);
            } else {
                $check = $this->t('check_not_found', [], $idLang);
            }
            $vars = [
                '{customer_name}' => $request->customer_name,
                '{customer_name_html}' => htmlspecialchars($request->customer_name, ENT_QUOTES, 'UTF-8'),
                '{customer_email}' => $request->customer_email,
                '{order_reference}' => $reference,
                '{order_line}' => $orderLine,
                '{order_line_html}' => htmlspecialchars($orderLine, ENT_QUOTES, 'UTF-8'),
                '{order_check}' => $check,
                '{order_check_html}' => htmlspecialchars($check, ENT_QUOTES, 'UTF-8'),
                '{request_id}' => $request->id,
                '{submitted_at}' => Tools::displayDate($request->date_add, true),
                '{items_html}' => $this->formatItems($request, true, $idLang),
                '{items_txt}' => $this->formatItems($request, false, $idLang),
                '{reason}' => $reason !== '' ? $reason : '—',
                '{reason_html}' => $reason !== '' ? nl2br(htmlspecialchars($reason, ENT_QUOTES, 'UTF-8')) : '—',
                '{ip}' => $request->ip,
                '{dmr_color}' => $this->getColor() ?: self::DEFAULT_COLOR,
            ];

            return Mail::Send(
                $idLang,
                $template,
                $this->t($subjectKey, ['reference' => $reference], $idLang),
                $vars,
                $to,
                $toName,
                null,
                null,
                null,
                null,
                $mailDir,
                false,
                $shopId,
                null,
                $replyTo,
                $replyToName
            );
        };

        if (!$send($this->getMailLang($request->id_lang), 'dotmrecesso_customer', 'mail_subject_customer', $request->customer_email, $request->customer_name)) {
            PrestaShopLogger::addLog('dotmrecesso: ricevuta al cliente non inviata (richiesta ' . (int) $request->id . ')', 3, null, 'Order', $orderId);
        }

        $idLangAdmin = $this->getMailLang((int) Configuration::get('PS_LANG_DEFAULT'));
        $recipients = array_filter(array_map('trim', explode(',', (string) Configuration::get(self::CFG_NOTIFY))));
        if (!$recipients) {
            $recipients = [(string) Configuration::get('PS_SHOP_EMAIL', null, null, $shopId)];
        }
        foreach ($recipients as $to) {
            if (!Validate::isEmail($to)) {
                continue;
            }
            if (!$send($idLangAdmin, 'dotmrecesso_admin', 'mail_subject_admin', $to, null, $request->customer_email, $request->customer_name)) {
                PrestaShopLogger::addLog('dotmrecesso: notifica al negozio non inviata a ' . $to . ' (richiesta ' . (int) $request->id . ')', 3, null, 'Order', $orderId);
            }
        }
    }

    /* ------------------------------------------------------------------
     * Hook
     * ------------------------------------------------------------------ */

    public function hookModuleRoutes()
    {
        return [
            'module-dotmrecesso-recesso' => [
                'controller' => 'recesso',
                'rule' => 'recesso',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ],
            ],
        ];
    }

    public function hookActionFrontControllerSetMedia()
    {
        $page = isset($this->context->controller->php_self) ? $this->context->controller->php_self : '';
        if (in_array($page, ['order-detail', 'guest-tracking'], true)) {
            $this->context->controller->registerStylesheet('dotmrecesso-front', 'modules/' . $this->name . '/views/css/front.css', ['media' => 'all', 'priority' => 200]);
        }
    }

    public function hookDisplayCustomerAccount()
    {
        $this->context->smarty->assign([
            'dmr_url' => $this->getFormUrl(),
            'dmr_label' => $this->t('account_link'),
        ]);

        return $this->fetch('module:dotmrecesso/views/templates/hook/account-link.tpl');
    }

    public function hookDisplayOrderDetail($params)
    {
        if (empty($params['order']) || !Validate::isLoadedObject($params['order'])) {
            return '';
        }
        /** @var Order $order */
        $order = $params['order'];
        $info = $this->getWithdrawalInfo($order);
        if (!$info['eligible'] && !$info['requests']) {
            return '';
        }

        $requests = [];
        foreach ($info['requests'] as $row) {
            $requests[] = [
                'id' => (int) $row['id_dotmrecesso_request'],
                'date' => Tools::displayDate($row['date_add'], true),
                'status' => $this->t('status_' . $row['status']),
            ];
        }

        $this->context->smarty->assign([
            'dmr' => [
                't' => $this->getStrings(),
                'eligible' => $info['eligible'],
                'text' => $info['code'] === 'open_until'
                    ? $this->t('box_eligible', ['date' => Tools::displayDate($info['deadline'])])
                    : $this->t('box_eligible_open', ['days' => $this->getDays()]),
                'url' => $this->getFormUrl(['id_order' => (int) $order->id, 'ref' => $order->reference]),
                'requests' => $requests,
                'css_vars' => $this->getCssVars(),
            ],
        ]);

        return $this->fetch('module:dotmrecesso/views/templates/hook/order-detail.tpl');
    }

    public function hookDisplayAdminOrderSide($params)
    {
        $idOrder = isset($params['id_order']) ? (int) $params['id_order'] : 0;
        if (!$idOrder) {
            return '';
        }
        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }
        $info = $this->getWithdrawalInfo($order);
        $requests = [];
        foreach ($info['requests'] as $row) {
            $requests[] = [
                'id' => (int) $row['id_dotmrecesso_request'],
                'date' => Tools::displayDate($row['date_add'], true),
                'status' => $this->t('status_' . $row['status']),
                'link' => $this->context->link->getAdminLink('AdminDotmRecesso', true, [], [
                    'id_dotmrecesso_request' => (int) $row['id_dotmrecesso_request'],
                    'viewdotmrecesso_request' => 1,
                ]),
            ];
        }

        $this->context->smarty->assign([
            'dmr_admin' => [
                'requests' => $requests,
                'label' => $this->getInfoLabel($info),
                'eligible' => $info['eligible'],
            ],
        ]);

        return $this->display(__FILE__, 'views/templates/admin/order-side.tpl');
    }

    /* ------------------------------------------------------------------
     * Configurazione
     * ------------------------------------------------------------------ */

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitDotmrecesso')) {
            $days = (int) Tools::getValue(self::CFG_DAYS);
            $grace = (int) Tools::getValue(self::CFG_GRACE);
            $emails = array_filter(array_map('trim', explode(',', (string) Tools::getValue(self::CFG_NOTIFY))));
            $invalid = array_filter($emails, function ($e) {
                return !Validate::isEmail($e);
            });
            $excluded = array_map('intval', (array) Tools::getValue(self::CFG_EXCLUDED, []));
            $color = strtolower(trim((string) Tools::getValue(self::CFG_COLOR)));

            if ($days < 14) {
                $output .= $this->displayError('Il periodo di recesso non può essere inferiore a 14 giorni.');
            } elseif ($invalid) {
                $output .= $this->displayError('Indirizzi email non validi: ' . htmlspecialchars(implode(', ', $invalid)));
            } elseif ($color !== '' && !preg_match('/^#[0-9a-f]{6}$/', $color)) {
                $output .= $this->displayError('Colore non valido: usa il formato #rrggbb oppure lascia vuoto.');
            } else {
                Configuration::updateValue(self::CFG_DAYS, $days);
                Configuration::updateValue(self::CFG_GRACE, max(0, $grace));
                Configuration::updateValue(self::CFG_NOTIFY, implode(',', $emails));
                Configuration::updateValue(self::CFG_EXCLUDED, implode(',', array_filter($excluded)));
                Configuration::updateValue(self::CFG_COLOR, $color);
                $output .= $this->displayConfirmation('Impostazioni salvate.');
            }
        }

        $url = $this->getFormUrl();
        $output .= '<div class="alert alert-info">'
            . '<p><strong>Pagina pubblica del recesso</strong> (modulo di recesso per chiunque, ordini dei clienti registrati se hanno fatto l\'accesso): <a href="' . htmlspecialchars($url) . '" target="_blank">' . htmlspecialchars($url) . '</a></p>'
            . '<p>Collegala nel footer e cita la funzione nelle condizioni di vendita (art. 49, c. 1, lett. h Codice del Consumo). '
            . 'Le richieste ricevute sono in <a href="' . htmlspecialchars($this->context->link->getAdminLink('AdminDotmRecesso')) . '">Ordini → Recessi online</a>.</p>'
            . '</div>';

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $states = OrderState::getOrderStates((int) $this->context->language->id);

        $form = [
            'form' => [
                'legend' => ['title' => 'Impostazioni recesso', 'icon' => 'icon-cogs'],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => 'Giorni per il recesso',
                        'name' => self::CFG_DAYS,
                        'class' => 'fixed-width-sm',
                        'desc' => 'Dalla data in cui l\'ordine passa allo stato "Consegnato". Minimo di legge: 14.',
                    ],
                    [
                        'type' => 'text',
                        'label' => 'Margine di trasporto (giorni)',
                        'name' => self::CFG_GRACE,
                        'class' => 'fixed-width-sm',
                        'desc' => 'Se l\'ordine è spedito ma non risulta "Consegnato", il termine parte dalla spedizione più questi giorni. Prima della spedizione il recesso è sempre possibile.',
                    ],
                    [
                        'type' => 'text',
                        'label' => 'Email per le notifiche',
                        'name' => self::CFG_NOTIFY,
                        'desc' => 'Uno o più indirizzi separati da virgola. Se vuoto, si usa l\'email del negozio.',
                    ],
                    [
                        'type' => 'color',
                        'label' => 'Colore pulsanti',
                        'name' => self::CFG_COLOR,
                        'desc' => 'Formato #rrggbb, ad esempio il colore principale del tema. Se vuoto: ' . self::DEFAULT_COLOR . '. Usato anche nelle email.',
                    ],
                    [
                        'type' => 'select',
                        'label' => 'Stati esclusi',
                        'name' => self::CFG_EXCLUDED . '[]',
                        'multiple' => true,
                        'size' => 10,
                        'options' => ['query' => $states, 'id' => 'id_order_state', 'name' => 'name'],
                        'desc' => 'Ordini in questi stati non mostrano il pulsante (es. annullato, rimborsato, errore di pagamento). Ctrl/Cmd per selezioni multiple.',
                    ],
                ],
                'submit' => ['title' => 'Salva'],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->submit_action = 'submitDotmrecesso';
        $helper->fields_value = [
            self::CFG_DAYS => $this->getDays(),
            self::CFG_GRACE => (int) Configuration::get(self::CFG_GRACE),
            self::CFG_NOTIFY => (string) Configuration::get(self::CFG_NOTIFY),
            self::CFG_EXCLUDED . '[]' => $this->getExcludedStates(),
            self::CFG_COLOR => $this->getColor(),
        ];

        return $helper->generateForm([$form]);
    }
}
