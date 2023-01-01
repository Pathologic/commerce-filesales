<?php


namespace Pathologic\FileSales\Drivers;


use Helpers\FS;

class Readfile implements DriverInterface
{
    public static function send(string $file) {
        $fs = FS::getInstance();
        if ($fs->checkFile($file)) {
            $filename = $fs->takeFileBasename($file);
            while (@ob_end_clean());
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $fs->takeFileMIME($file));
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $fs->fileSize($file));
            readfile(MODX_BASE_PATH . $file);
            exit;
        }
    }
}