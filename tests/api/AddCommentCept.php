<?php

$I = new ApiTester($scenario);
$I->wantTo('create a comment via the API');
$I->sendPOST('/atom/1/comment', [
	'atomId' => 1,
	'parentId' => 0,
	'text' => 'This comment was inserted by an automated test.'
]);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
	'id' => 'integer'
], '$.data');