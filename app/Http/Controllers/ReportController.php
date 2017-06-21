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

    public function discontinuedAction($productId) {
        return new ApiPayload(Report::discontinued($productId));
    }

    public function statusesAction($productId) {
        return new ApiPayload(Report::statuses($productId));
    }

    public function editsAction($productId, Request $request) {
        $validStepSizes = ['day', 'week'];
        $timezoneOffset = $request->input('timezoneOffset');
        $stepSize = $request->input('stepSize');
        $startTime = $request->input('startTime');
        $endTime = $request->input('endTime');

        return new ApiPayload(Report::edits($productId, $stepSize, $timezoneOffset, $startTime, $endTime));
    }

    public function openAssignmentsAction($productId, Request $request) {
        $validStepSizes = ['day', 'week'];
        $timezoneOffset = $request->input('timezoneOffset');
        $stepSize = $request->input('stepSize');
        $startTime = $request->input('startTime');
        $endTime = $request->input('endTime');

        return new ApiPayload(Report::openAssignments($productId, $stepSize, $timezoneOffset, $startTime, $endTime));
    }

    public static function brokenLinksAction($productId) {
        return new ApiPayload(Report::links($productId));
    }

    public function commentsAction($productId, Request $request) {
        $timezoneOffset = $request->input('timezoneOffset');
        $startTime = $request->input('startTime');
        $endTime = $request->input('endTime');
        $queriesOnly = (bool)$request->input('queriesOnly');
        $generateCsv = (bool)$request->input('generateCsv') && $queriesOnly;
        $queryType = $request->input('queryType');

        $comments = Report::comments($productId, $timezoneOffset, $startTime, $endTime, $queriesOnly, $queryType);

        if($generateCsv) {
            if($comments) {
                return Report::buildQueriesCSV($comments, $queryType);
            }
            else {
                return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'No queries were found in the specified time range.');
            }
        }
        else {
            return new ApiPayload($comments);
        }
    }

    public function moleculeStatsAction($productId, Request $request) {
        try {
            $stats = Report::moleculeStats($productId);
        }
        catch(Exception $e) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'No molecule statistics were found.');
        }

        return new ApiPayload($stats);
    }

    public function domainStatsAction($productId, Request $request) {
        try {
            $stats = Report::domainStats($productId);
        }
        catch(Exception $e) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'No domain statistics were found.');
        }

        return new ApiPayload($stats);
    }
}
