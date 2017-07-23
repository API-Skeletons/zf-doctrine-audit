<?php
return array(
    'modules' => array(
	'Zend\Router',
        'ZF\Doctrine\Audit',
        'ZFTest\Doctrine\Audit',
        'DoctrineModule',
        'DoctrineORMModule',
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            '../../../config/autoload/{,*.}{global,local}.php',
            'autoload/{,*.}{global,local}.php',
        ),
        'module_paths' => array(
            'module',
            'vendor',
        ),
    ),
);
