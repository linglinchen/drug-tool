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
        'edits' => 'Edits',
        'openAssignments' => 'Open Assignments'
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
        $validStepSizes = ['day', 'week'];
        $timezoneOffset = $request->input('timezoneOffset');
        $stepSize = $request->input('stepSize');
        $startTime = $request->input('startTime');
        $endTime = $request->input('endTime');

        return new ApiPayload(Report::edits($stepSize, $timezoneOffset, $startTime, $endTime));
    }

    public function openAssignmentsAction(Request $request) {
        $validStepSizes = ['day', 'week'];
        $timezoneOffset = $request->input('timezoneOffset');
        $stepSize = $request->input('stepSize');
        $startTime = $request->input('startTime');
        $endTime = $request->input('endTime');

        return new ApiPayload(Report::openAssignments($stepSize, $timezoneOffset, $startTime, $endTime));
    }
}
