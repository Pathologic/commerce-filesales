<?php


namespace Pathologic\FileSales\Drivers;


interface DriverInterface
{
    public static function send(string $file);
}