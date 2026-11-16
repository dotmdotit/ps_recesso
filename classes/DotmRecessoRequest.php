<?php
/**
 * Dichiarazione di recesso inviata dal cliente tramite la funzione online (art. 54-bis Codice del Consumo).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DotmRecessoRequest extends ObjectModel
{
    const STATUS_RECEIVED = 'received';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    /** @var int 0 se l'ordine indicato nel form pubblico non è stato trovato */
    public $id_order;
    public $id_customer;
    public $id_shop;
    public $id_lang;
    public $order_reference;
    public $customer_name;
    public $customer_email;
    /** @var string JSON: [{id_order_detail, name, reference, qty}]; id_order_detail 0 e qty 0 per le righe scritte a mano */
    public $items;
    public $whole_order;
    public $reason;
    public $status = self::STATUS_RECEIVED;
    public $ip;
    public $user_agent;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'dotmrecesso_request',
        'primary' => 'id_dotmrecesso_request',
        'fields' => [
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'order_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64],
            'customer_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255, 'required' => true],
            'customer_email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255, 'required' => true],
            'items' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'whole_order' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'reason' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 2000],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32],
            'ip' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 64],
            'user_agent' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public static function getStatuses()
    {
        return [self::STATUS_RECEIVED, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_REJECTED];
    }

    /**
     * @return array righe della tabella, dalla più recente
     */
    public static function getByOrder($idOrder)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'dotmrecesso_request`
            WHERE `id_order` = ' . (int) $idOrder . '
            ORDER BY `date_add` DESC'
        );

        return is_array($rows) ? $rows : [];
    }

    public function getItems()
    {
        $items = json_decode((string) $this->items, true);

        return is_array($items) ? $items : [];
    }

    /**
     * Chiave per rivedere la ricevuta senza login (link nella pagina di conferma).
     */
    public function getReceiptKey()
    {
        return substr(hash('sha256', $this->id . '|' . $this->date_add . '|' . _COOKIE_KEY_), 0, 24);
    }
}
