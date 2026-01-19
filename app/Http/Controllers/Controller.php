<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Traits\HandlesErrors;
use App\Traits\HandlesAuthorization;
use App\Traits\HandlesLogging;

use Illuminate\Http\Request;
use PDF; // Import the PDF facade

/**
 * Base Controller for all application controllers
 *
 * Provides common functionality through traits:
 * - HandlesErrors: Standardized error handling
 * - HandlesAuthorization: Authorization and permission checks
 * - HandlesLogging: Standardized logging
 * - AuthorizesRequests: Laravel authorization
 * - ValidatesRequests: Laravel validation
 *
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, HandlesErrors, HandlesAuthorization, HandlesLogging;
}
