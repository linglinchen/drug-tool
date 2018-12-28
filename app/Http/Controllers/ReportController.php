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

    public function menuAction($productId) {
        return new ApiPayload(Report::reportMenu($productId));
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

    public static function newFiguresAction($productId) {
        ini_set('memory_limit', '1280M');
        return new ApiPayload(Report::newFigures($productId));

    }

    public function commentsAction($productId, Request $request) {
        $timezoneOffset = $request->input('timezoneOffset');
        $startTime = $request->input('startTime');
        $endTime = $request->input('endTime');
        $queriesOnly = (bool)$request->input('queriesOnly');
        $generateCsv = (bool)$request->input('generateCsv') && $queriesOnly;
        $queryType = $request->input('queryType');
        $moleculeCode = $request->input('moleculeCode');
        $domainCode = $request->input('domainCode');

        $comments = Report::comments($productId, $timezoneOffset, $startTime, $endTime, $queriesOnly, $queryType, $moleculeCode, $domainCode);

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

     public function reviewerStatsAction($productId, Request $request) {
        try {
            $stats = Report::reviewerStats($productId);
        }
        catch(Exception $e) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'No reviewer statistics were found.');
        }

        return new ApiPayload($stats);
    }

    public function suggestedImageStatsAction($productId, Request $request) {
        try {
            $filters = $request->input('filters');
            $stats = Report::suggestedImageStats($productId, $filters);
        }
        catch(Exception $e) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'No suggested image statistics were found.');
        }

        return new ApiPayload($stats);
    }

    public function legacyImageStatsAction($productId, Request $request) {
        try {
            $stats = Report::legacyImageStats($productId);
        }
        catch(Exception $e) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'No legacy image statistics were found.');
        }

        return new ApiPayload($stats);
    }
}