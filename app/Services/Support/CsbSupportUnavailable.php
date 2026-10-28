<?php

namespace App\Services\Support;

use RuntimeException;

/** Le site CSB ne répond pas (ou le support n'est pas configuré) : message affiché à l'utilisateur. */
class CsbSupportUnavailable extends RuntimeException
{
}
