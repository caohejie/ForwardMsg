<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitcdb4cbb3ff2335cf75c9da2790157d61
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitcdb4cbb3ff2335cf75c9da2790157d61::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitcdb4cbb3ff2335cf75c9da2790157d61::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}