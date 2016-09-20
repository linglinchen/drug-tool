<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\Report;

class ReportController extends Controller {
    protected $_reportTypes = [
        'discontinued' => 'Discontinued Monographs',
        'statuses' => 'Status Breakdown',
        'edits' => 'Edits'
    ];

    public function listAction() {
        return new ApiPayload($this->_reportTypes);
    }

    public function discontinuedAction(Request $request) {
        return new ApiPayload(Report::discontinued());
    }

    public function statusesAction(Request $request) {
        return new ApiPayload(Report::statuses());
    }

    public function editsAction(Request $request) {
        return new ApiPayload(Report::edits());
    }
}
