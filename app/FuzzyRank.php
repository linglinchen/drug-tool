<?php

namespace App;

/**
 * Ranks search results based on their relevance to the search query.
 */
class FuzzyRank {
	/**
	 * Ranks search results based on their relevance to the search query.
	 *
	 * @param string[] $candidates The candidate strings to be ranked as key / value pairs (e.g. id => alphaTitle)
	 * @param string $q The search query
	 *
	 * @return double[] The ranked candidates
	 */
	public static function rank($candidates, $q) {
		$q = self::_prepareString($q);
		$candidates = self::_prepareCandidates($candidates);
		$scores = self::_scoreCandidates($candidates, $q);
		asort($scores);

		return $scores;
	}

	/**
	 * Score candidate strings on their relevance to the search query.
	 *
	 * @param array[] $candidates The prepared candidate strings
	 * @param string $q The prepared query
	 *
	 * @return double[] The scored candidates
	 */
	protected static function _scoreCandidates($candidates, $q) {
		$scores = array();
		foreach($candidates as $id => $candidate) {
			$scores[$id] = self::_scoreCandidate($candidate, $q);
		}

		return $scores;
	}

	/**
	 * Score a candidate string on its relevance to the search query.
	 *
	 * @param string[] $candidate The prepared candidate string
	 * @param string $q The prepared query
	 *
	 * @return double The candidate's score
	 */
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

	/**
	 * Prepare candidates for scoring and ranking.
	 *
	 * @param string[] $candidates The candidate strings to be ranked as key / value pairs (e.g. id => alphaTitle)
	 * @param string $q The search query
	 *
	 * @return array[] The ranked candidates
	 */
	protected static function _prepareCandidates($candidates) {
		foreach($candidates as $id => $candidate) {
			$candidates[$id] = self::_prepareString($candidate);
		}

		return $candidates;
	}

	/**
	 * Remove non-word characters, trim, and explode a string.
	 *
	 * @param string $input The string to prepare
	 *
	 * @return string[] The prepared string
	 */
	protected static function _prepareString($input) {
		$output = strtolower($input);
		$output = preg_replace('/[\W]/', ' ', $output);
		$output = trim($output);
		$output = preg_split('/\s+/', $output);

		return $output;
	}
}