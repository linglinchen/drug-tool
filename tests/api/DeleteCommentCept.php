<?php

$I = new ApiTester($scenario);
$I->wantTo('delete a comment via the API');
$I->amHttpAuthenticated('test@domain.com', 'test');

//find an atom
$I->sendGET('/1/molecule/a');
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
	'entityId' => 'string'
], '$.payload.atoms[0]');
$entityId = $I->grabDataFromResponseByJsonPath('$.payload.atoms[0].entityId')[0];

//create a comment to work on
$I->sendPOST('/1/atom/' . $entityId . '/comment', [
	'atom_entity_id' => 1,
	'parent_id' => 0,
	'text' => 'This comment was inserted by an automated test.'
]);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
	'id' => 'integer'
], '$.payload[0]');
$id = $I->grabDataFromResponseByJsonPath('$.payload[0].id')[0];

//now delete it
$I->sendDELETE('/1/atom/' . $entityId . '/comment/' . $id);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseMatchesJsonType([
	'deletedAt' => 'string'
], '$.payload');