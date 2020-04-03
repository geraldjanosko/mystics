<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit65563a645bca19c5968376112c0bc78c
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit65563a645bca19c5968376112c0bc78c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit65563a645bca19c5968376112c0bc78c::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
