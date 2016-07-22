<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Molecule;

use App\ApiError;
use App\ApiPayload;

class MoleculeController extends Controller
{
    public function listAction() {
        return new ApiPayload(Molecule::all());
    }
}
