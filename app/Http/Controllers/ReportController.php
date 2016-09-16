<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;

class ReportController extends Controller {
    protected $_reportTypes = [
        'deactivated'   => 'Deactivated Monographs'
    ];

    public function listAction() {
        return new ApiPayload($this->_reportTypes);
    }
}
