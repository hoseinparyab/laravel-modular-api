<?php
namespace Modules\Base\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Modules\Base\Http\Controllers\BaseController;

class ApiController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
