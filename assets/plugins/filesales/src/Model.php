<?php


namespace Pathologic\FileSales;


class Model extends \autoTable
{
    protected $table = 'file_sales';
    protected $default_field = [
        'file'             => '',
        'order_product_id' => 0,
        'hash'             => '',
        'createdon'        => ''
    ];

    /**
     * @param  false  $fire_events
     * @param  false  $clearCache
     * @return bool|void|null
     * @throws \Exception
     */
    public function save($fire_events = false, $clearCache = false)
    {
        $this->set('createdon', date('Y-m-d H:i:s', $this->getTime(time())));
        $this->set('hash', $this->makeHash());
        if (!$this->get('order_product_id')) {
            return false;
        } else {
            return parent::save();
        }
    }

    /**
     * @param  int  $length
     * @return string
     * @throws \Exception
     */
    protected function makeHash(int $length = 32): string
    {
        if (function_exists('random_bytes')) {
            $result = bin2hex(random_bytes($length / 2));
        } else {
            if (function_exists('openssl_random_pseudo_bytes')) {
                $result = bin2hex(openssl_random_pseudo_bytes($length / 2));
            } else {
                $result = md5(rand() . rand() . rand());
            }
        }

        return substr($result, 0, $length);
    }

    public function createTable()
    {
        $this->query("
            CREATE TABLE IF NOT EXISTS {$this->makeTable($this->table)} (
            `id` int(11) PRIMARY KEY AUTO_INCREMENT,
            `order_product_id` INT(10) UNSIGNED NOT NULL,
            `file` varchar(255) NOT NULL DEFAULT '',
            `hash` varchar(32) NOT NULL DEFAULT '',
            `createdon` TIMESTAMP, 
            KEY (`id`, `hash`),
            CONSTRAINT `filesales_ibfk_1`
            FOREIGN KEY (`order_product_id`) 
            REFERENCES {$this->makeTable('commerce_order_products')} (`id`) 
            ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8;
        ");
    }
}