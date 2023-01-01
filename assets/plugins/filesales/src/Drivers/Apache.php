<?php


namespace Pathologic\FileSales\Drivers;


use Helpers\FS;

class Apache implements DriverInterface
{
    public static function send(string $file)
    {
        $fs = FS::getInstance();
        if ($fs->checkFile($file)) {
            $filename = $fs->takeFileBasename($file);
            while (@ob_end_clean());
            header('X-SendFile: ' . MODX_BASE_PATH . $file);
            header('Content-Type: ' . $fs->takeFileMIME($file));
            header('Content-Disposition: attachment; filename=' . $filename);
            exit;
        }
    }

}