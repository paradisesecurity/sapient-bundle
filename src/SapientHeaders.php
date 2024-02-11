<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle;

use ParagonIE\Sapient\Sapient;

interface SapientHeaders
{
    public const HEADER_SERVER_IDENTIFIER = 'Sapient-Server-Key-ID';

    public const HEADER_CLIENT_IDENTIFIER = 'Sapient-Client-Key-ID';

    public const HEADER_STATE = 'Sapient-State';

    public const HEADER_SIGNATURE_NAME = Sapient::HEADER_SIGNATURE_NAME;

    public const HEADER_AUTH_NAME = Sapient::HEADER_AUTH_NAME;
}
