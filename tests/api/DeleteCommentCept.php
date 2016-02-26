<?php

$I = new ApiTester($scenario);
$I->wantTo('delete a comment via the API');

//create a comment to work on
$I->sendPOST('/atom/1/comment', [
	'atomId' => 1,
	'parentId' => 0,
	'text' => 'This comment was inserted by an automated test.'
]);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
	'id' => 'integer'
], '$');
$id = $I->grabDataFromResponseByJsonPath('$.id')[0];

//now delete it
$I->sendDELETE('/atom/1/comment/' . $id);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
	'deleted_at' => 'string'
]);