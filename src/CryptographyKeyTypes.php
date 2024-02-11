<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle;

use ParagonIE\Sapient\CryptographyKeys\SealingPublicKey;
use ParagonIE\Sapient\CryptographyKeys\SealingSecretKey;
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;
use ParagonIE\Sapient\CryptographyKeys\SigningSecretKey;
use ParagonIE\Sapient\CryptographyKeys\SharedAuthenticationKey;
use ParagonIE\Sapient\CryptographyKeys\SharedEncryptionKey;

interface CryptographyKeyTypes
{
    public const SEALING = 'seal';

    public const SIGNING = 'sign';

    public const ENCRYPTING = 'encrypt';

    public const AUTHENTICATING = 'authenticate';

    public const KEY_PUBLIC_SEALING = SealingPublicKey::class;

    public const KEY_PRIVATE_SEALING = SealingSecretKey::class;

    public const KEY_PUBLIC_SIGNING = SigningPublicKey::class;

    public const KEY_PRIVATE_SIGNING = SigningSecretKey::class;

    public const KEY_SHARED_ENCRYPTION = SharedEncryptionKey::class;

    public const KEY_SHARED_AUTH = SharedAuthenticationKey::class;

    public const PUBLIC_CRYPTOGRAPHY_KEYS = [
        [
            'modifier' => 'public',
            'type' => self::SEALING,
            'class' => self::KEY_PUBLIC_SEALING,
        ],
        [
            'modifier' => 'public',
            'type' => self::SIGNING,
            'class' => self::KEY_PUBLIC_SIGNING,
        ],
    ];

    public const PRIVATE_CRYPTOGRAPHY_KEYS = [
        [
            'modifier' => 'private',
            'type' => self::SEALING,
            'class' => self::KEY_PRIVATE_SEALING,
        ],
        [
            'modifier' => 'private',
            'type' => self::SIGNING,
            'class' => self::KEY_PRIVATE_SIGNING,
        ],
    ];

    public const SHARED_CRYPTOGRAPHY_KEYS = [
        [
            'modifier' => 'shared',
            'type' => self::ENCRYPTING,
            'class' => self::KEY_SHARED_ENCRYPTION,
        ],
        [
            'modifier' => 'shared',
            'type' => self::AUTHENTICATING,
            'class' => self::KEY_SHARED_AUTH,
        ],
    ];
}
