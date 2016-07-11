<?php

namespace App;

class FuzzyRank {
	public static function rank($candidates, $q) {
		$q = self::_prepareString($q);
		$candidates = self::_prepareCandidates($candidates);
		$scores = self::_scoreCandidates($candidates, $q);
		asort($scores);

		return $scores;
	}

	protected static function _scoreCandidates($candidates, $q) {
		$scores = array();
		foreach($candidates as $id => $candidate) {
			$scores[$id] = self::_scoreCandidate($candidate, $q);
		}

		return $scores;
	}

	protected static function _scoreCandidate($candidate, $q) {
		$magic = 42;
		$delta = abs(sizeof($q) - sizeof($candidate));
		$score = array();
		foreach($q as $qKey => $qTerm) {
			$bestScore = $magic;
			foreach($candidate as $cTerm) {
				$tmpScore = levenshtein($qTerm, $cTerm, 1, 1, 1);
				if($tmpScore) {
					if(strpos($cTerm, $qTerm) === 0) {		//bonus for being at the beginning, esp. in the first word
						$tmpScore -= $magic / ($qKey ? 5 : 2);
					}
					else if(strpos($cTerm, $qTerm) === strlen($cTerm) - strlen($qTerm)) {		//smaller bonus for being at the end
						$tmpScore -= $magic / 8;
					}
				}
				else {
					$tmpScore -= $magic;		//boost exact matches
				}
				$bestScore = $tmpScore < $bestScore ? $tmpScore : $bestScore;
			}
			$score[] = $bestScore;
		}

		return array_sum($score) / ($delta + 1);
	}

	protected static function _prepareCandidates($candidates) {
		foreach($candidates as $id => $candidate) {
			$candidates[$id] = self::_prepareString($candidate);
		}

		return $candidates;
	}

	protected static function _prepareString($q) {
		$q = strtolower($q);
		$q = preg_replace('/[\W]/', ' ', $q);
		$q = trim($q);
		$q = preg_split('/\s+/', $q);

		return $q;
	}
}