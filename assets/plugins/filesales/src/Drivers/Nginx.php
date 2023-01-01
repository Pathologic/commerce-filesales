<?php


namespace Pathologic\FileSales\Drivers;


use Helpers\FS;

class Nginx implements DriverInterface
{
    public static function send(string $file)
    {
        $fs = FS::getInstance();
        if ($fs->checkFile($file)) {
            $filename = $fs->takeFileBasename($file);
            while (@ob_end_clean());
            header('X-Accel-Redirect: ' . MODX_BASE_PATH . $file);
            header('Content-Type: ' . $fs->takeFileMIME($file));
            header('Content-Disposition: attachment; filename=' . $filename);
            exit;
        }
    }
}