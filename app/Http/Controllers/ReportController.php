<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\Report;

class ReportController extends Controller {
    public function listAction() {
        return new ApiPayload(Report::$reportTypes);
    }

    public function discontinuedAction() {
        return new ApiPayload(Report::discontinued());
    }

    public function statusesAction() {
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

    public static function brokenLinksAction() {
        return new ApiPayload(Report::links());
    }

    public function queriesAction(Request $request) {
        $timezoneOffset = $request->input('timezoneOffset');
        $startTime = $request->input('startTime');
        $endTime = $request->input('endTime');

        $queries = Report::queries($timezoneOffset, $startTime, $endTime);

        return new ApiPayload($queries);
    }
}