<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\Atom;

class ReportController extends Controller {
    protected $_reportTypes = [
        'discontinued'   => 'Discontinued Monographs'
    ];

    public function listAction() {
        return new ApiPayload($this->_reportTypes);
    }

    public function discontinuedAction(Request $request) {
        return new ApiPayload([
            'totalCount'    => Atom::countMonographs(),
            'discontinued'   => Atom::getDiscontinuedMonographs()
        ]);
    }
}
