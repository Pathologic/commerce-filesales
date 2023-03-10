<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'Pathologic\\FileSales\\Drivers\\Apache' => '/src/Drivers/Apache.php',
                'Pathologic\\FileSales\\Drivers\\DriverInterface' => '/src/Drivers/DriverInterface.php',
                'Pathologic\\FileSales\\Drivers\\Nginx' => '/src/Drivers/Nginx.php',
                'Pathologic\\FileSales\\Drivers\\Readfile' => '/src/Drivers/Readfile.php',
                'Pathologic\\FileSales\\Model' => '/src/Model.php',
                'Pathologic\\FileSales\\Plugin' => '/src/Plugin.php'
            );
        }
        if (isset($classes[$class])) {
            require __DIR__ . $classes[$class];
        }
    },
    true,
    false
);
// @codeCoverageIgnoreEnd
