<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7b90d4611be57f39a921adc653948404
{
    public static $files = array (
        'ad155f8f1cf0d418fe49e248db8c661b' => __DIR__ . '/..' . '/react/promise/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'React\\Promise\\' => 14,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Stream\\' => 18,
            'GuzzleHttp\\Ring\\' => 16,
            'GuzzleHttp\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'React\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/promise/src',
        ),
        'GuzzleHttp\\Stream\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/streams/src',
        ),
        'GuzzleHttp\\Ring\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/ringphp/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Raven_' => 
            array (
                0 => __DIR__ . '/..' . '/sentry/sentry/lib',
            ),
        ),
    );

    public static $classMap = array (
        'CappasityClient' => __DIR__ . '/../..' . '/library/cappasity/Client.php',
        'CappasityManagerAbstractManager' => __DIR__ . '/../..' . '/library/cappasity/Manager/AbstractManager.php',
        'CappasityManagerAccount' => __DIR__ . '/../..' . '/library/cappasity/Manager/Account.php',
        'CappasityManagerDatabase' => __DIR__ . '/../..' . '/library/cappasity/Manager/Database.php',
        'CappasityManagerFile' => __DIR__ . '/../..' . '/library/cappasity/Manager/File.php',
        'CappasityManagerPlayer' => __DIR__ . '/../..' . '/library/cappasity/Manager/Player.php',
        'CappasityManagerPlayerExceptionsValidation' => __DIR__ . '/../..' . '/library/cappasity/Manager/Player/Exceptions/Validation.php',
        'CappasityManagerSync' => __DIR__ . '/../..' . '/library/cappasity/Manager/Sync.php',
        'CappasityModelAccount' => __DIR__ . '/../..' . '/library/cappasity/Model/Account.php',
        'CappasityModelFile' => __DIR__ . '/../..' . '/library/cappasity/Model/File.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7b90d4611be57f39a921adc653948404::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7b90d4611be57f39a921adc653948404::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit7b90d4611be57f39a921adc653948404::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit7b90d4611be57f39a921adc653948404::$classMap;

        }, null, ClassLoader::class);
    }
}
