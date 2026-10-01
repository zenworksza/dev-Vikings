<?php

namespace App\Services;

use RuntimeException;

/** The portal could not be reached (or refused us) and there is no cached copy to fall back on. */
class PortalUnavailable extends RuntimeException {}
