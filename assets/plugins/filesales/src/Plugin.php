<?php


namespace Pathologic\FileSales;


use Helpers\FS;

class Plugin
{
    protected $modx;
    protected $params = [];
    protected $model;

    public function __construct(\DocumentParser $modx, array $params = [])
    {
        $this->modx = $modx;
        $this->params = $this->prepareParams($params);
        $this->model = new Model($modx);
        $event = $modx->event->name;
        if (method_exists($this, $event)) {
            $this->$event();
        }
    }

    protected function prepareParams(array $params = []): array
    {
        $params['driver'] = $params['driver'] ?? 'Pathologic\\FileSales\\Drivers\\Readfile';
        if (!class_exists($params['driver']) || !is_a($params['driver'],
                'Pathologic\\FileSales\\Drivers\\DriverInterface')) {
            $params['driver'] = 'Pathologic\\FileSales\\Drivers\\Readfile';
        }
        $params['paidstatusid'] = (int) $params['paidstatusid'] ?? 0;
        $params['filetvid'] = (int) $params['filetvid'] ?? 0;
        $params['lifetime'] = $params['lifetime'] ?? 24;
        $params['fstemplates'] = $params['fstemplates'] ?? 'assets/plugins/filesales/tpl/';
        $params['fssubject'] = $params['fssubject'] ?? 'Ваши файлы';

        return $params;
    }

    public function OnBeforeCartItemAdding()
    {
        $id = $this->params['item']['id'];
        $q = $this->modx->db->query("SELECT `value` FROM {$this->modx->getFullTableName('site_tmplvar_contentvalues')} WHERE `tmplvarid`={$this->params['filetvid']} AND `contentid`={$id}");
        if ($this->modx->db->getValue($q)) {
            $this->params['item']['count'] = 1;
        }
    }

    public function OnBeforeCartItemUpdating()
    {
        $this->OnBeforeCartItemAdding();
    }

    public function OnBeforeOrderHistoryUpdate()
    {
        if (!$this->params['paidstatusid'] || !$this->params['filetvid'] || $this->params['paidstatusid'] != $this->params['status_id']) {
            return;
        }
        $processor = ci()->commerce->loadProcessor();
        $_cart = $processor->getCart()->getItems();
        $ids = $opids = $files = [];
        foreach ($_cart as $item) {
            $ids[] = $item['id'];
            $opids[] = $item['order_row_id'];
        }
        if ($opids) {
            $opids = implode(',', $opids);
            $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('file_sales')} WHERE `order_product_id` IN ({$opids})");
        }
        if (!empty($ids)) {
            $tv = $this->params['filetvid'];
            $ids = implode(',', $ids);
            $q = $this->modx->db->query("SELECT `contentid`, `value` FROM {$this->modx->getFullTableName('site_tmplvar_contentvalues')} WHERE `tmplvarid`={$tv} AND `contentid` IN ({$ids})");
            while ($row = $this->modx->db->getRow($q)) {
                $files[$row['contentid']] = ['file' => $row['value']];
            }
        }
        if (empty($files)) {
            return;
        }
        foreach ($_cart as $row => $item) {
            $id = $item['id'];
            if (isset($files[$id])) {
                $url = $this->makeFileUrl($item['order_row_id'], $files[$id]['file']);
                if ($url === false) {
                    unset($files[$id]);
                } else {
                    $files[$id]['name'] = $item['name'];
                    $files[$id]['url'] = $url;
                }
            }
        }
        $this->params['order']['fields']['files'] = $files;
        $processor->updateOrder($this->params['order_id'], [
            'values' => $this->params['order']
        ]);
        $tpl = ci()->tpl;
        $templatesPath = $tpl->getTemplatePath();
        $templateExtension = $tpl->getTemplateExtension();
        $tpl->setTemplatePath($this->params['fstemplates'])->setTemplateExtension('tpl');
        $filelist = '';
        foreach ($files as $file) {
            $filelist .= $tpl->parseChunk('@FILE:file', $file);
        }
        $report = $tpl->parseChunk('@FILE:report', [
            'order'    => $this->params['order'],
            'filelist' => $filelist
        ], true);
        if (!empty($report)) {
            $mailer = new \Helpers\Mailer($this->modx, [
                'to'      => $this->params['order']['email'],
                'subject' => $this->params['fssubject'],
            ]);
            $mailer->send($report);
        }
        $tpl->setTemplatePath($templatesPath)->setTemplateExtension($templateExtension);
    }

    /**
     * @param  int  $product_id
     * @param  string  $file
     * @return false|string
     * @throws \Exception
     */
    protected function makeFileUrl(int $order_product_id, string $file)
    {
        $out = false;
        if ($order_product_id && FS::getInstance()->checkFile($file)) {
            $this->model->create([
                'order_product_id' => $order_product_id,
                'file'             => $file
            ]);
            if ($this->model->save()) {
                $id = $this->model->getID();
                $hash = $this->model->get('hash');
                $out = MODX_SITE_URL . 'files/' . $id . '/' . $hash;
            }
        }

        return $out;
    }

    public function OnPageNotFound()
    {
        if (!empty($_REQUEST['q']) && is_scalar($_REQUEST['q']) && false !== preg_match_all('/^files\/([\d]+)\/([a-f0-9]{32})\/?$/',
                $_REQUEST['q'], $matches)) {
            if (!empty($matches[1][0]) && !empty($matches[2][0])) {
                $id = (int) $matches[1][0];
                $hash = $this->modx->db->escape($matches[2][0]);
                $time = date('Y-m-d H:i:s', time() - 3600 * $this->params['lifetime']);
                $this->modx->db->query("DELETE FROM {$this->modx->getFullTableName('file_sales')} WHERE `createdon` < '{$time}'");
                $q = $this->modx->db->query("SELECT `file` FROM {$this->modx->getFullTableName('file_sales')} WHERE `id` = {$id} AND `hash` = '{$hash}'");
                if ($file = $this->modx->db->getValue($q)) {
                    $driver = $this->params['driver'];
                    $driver::send($file);
                }
            }
        }
    }

    public function OnManagerBeforeOrderRender()
    {
        if (!empty($this->params['order']['fields']['files'])) {
            $files = $this->params['order']['fields']['files'];
            $this->params['columns']['title']['content'] = function ($data) use ($files) {
                $url = $this->modx->makeUrl($data['id']);
                $edited = $data['original_title'] !== $data['pagetitle'] ? '<i class="fa fa-edit"></i>&nbsp;' : '';
                $out = '<a href="' . $url . '" target="_blank">' . $edited . htmlentities($data['pagetitle']) . '</a>';
                if (isset($files[$data['id']])) {
                    $out .= '<br><br>Ссылка на файл:<br>' . $files[$data['id']]['url'];
                }

                return $out;
            };
        }
    }

    public function OnPluginFormSave()
    {
        $id = $this->params['id'];
        $q = $this->modx->db->query("SELECT `name` FROM {$this->modx->getFullTableName('site_plugins')} WHERE `name`='FileSales' AND `id`={$id}");
        if ($this->modx->db->getValue($q)) {
            $this->model->createTable();
        }
    }
}
