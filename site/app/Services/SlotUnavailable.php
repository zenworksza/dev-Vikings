<?php

namespace App\Services;

use RuntimeException;

/** The chosen time cannot be booked (taken, closed, or out of range). */
class SlotUnavailable extends RuntimeException {}
