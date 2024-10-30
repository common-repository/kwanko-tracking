<?php

/**
 * The kwanko related code.
 *
 * @link	https://www.kwanko.com
 * @since	1.0.0
 *
 * @package	 	Kwanko_Tracking
 * @subpackage  Kwanko_Tracking/includes/kwanko
 */

/**
 * Class used to decode the elements included in a given mclic value.
 *
 * @package	 	Kwanko_Tracking
 * @subpackage  Kwanko_Tracking/includes/kwanko
 * @author	  	Kwanko <support@kwanko.com>
 */
class Kwanko_Tracking_Mclic_Decoder {

	/**
	 * Decode mclic string.
	 *
	 * @since   1.0.0
	 *
	 * @var  string $mclic
	 * @return  array|false  false in case of error
	 */
	public static function decode($mclic) {

		$decoded = array('m' => null, 'p' => null, 's' => null, 'o' => null, 'n' => null);
		$pos = 1;
		$c = ord(strtoupper(substr($mclic, 0, 1)));

		if ($c > 64 && $c < 91) {
			$decoded['m'] = chr($c);
			$pos++;
		}

		$l = substr($mclic, $pos - 1, 1);
		if ( ! is_numeric($l) ) {
			return false;
		}
		$l = (int) $l;
		$h = substr($mclic, $pos, $l);
		if ( ! ctype_xdigit($h) ) {
			return false;
		}
		$decoded['p'] = hexdec($h);
		$pos += $l;

		$l = substr($mclic, $pos, 1);
		if ( ! is_numeric($l) ) {
			return false;
		}
		$l = (int) $l;
		$h = substr($mclic, $pos + 1, $l);
		if ( ! ctype_xdigit($h) ) {
			return false;
		}
		$decoded['s'] = hexdec($h);
		$pos += $l + 1;

		$l = substr($mclic, $pos, 1);
		if ( ! is_numeric($l) ) {
			return false;
		}
		$l = (int) $l;
		$h = substr($mclic, $pos + 1, $l);
		if ( ! ctype_xdigit($h) ) {
			return false;
		}
		$decoded['o'] = hexdec($h);
		$pos += $l + 1;

		if ( strlen($mclic) < $pos ) {
			return false;
		} elseif ( strlen($mclic) > $pos ) {
			$decoded['n'] = substr($mclic, $pos, strlen($mclic));
		}

		return $decoded;

	}

	/**
	 * Change the m parameter of the given mclic to 'G'.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $mclic
	 * @return  string
	 */
	public static function to_g_mclic($mclic) {

		return self::change_mclic_m_value($mclic, 'G');

	}

	/**
	 * Change the m parameter of the given mclic to 'N'.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $mclic
	 * @return  string
	 */
	public static function to_n_mclic($mclic) {

		return self::change_mclic_m_value($mclic, 'N');

	}

	/**
	 * Change the m parameter of the given mclic.
	 *
	 * @since   1.0.0
	 *
	 * @param   string  $mclic
	 * @param   string  $m
	 * @return  string
	 */
	protected static function change_mclic_m_value($mclic, $m) {

		$params = self::decode($mclic);

		if ( $params['m'] === null ) {
			return $m . $mclic;
		}

		return $m . substr($mclic, 1, strlen($mclic));

	}
}
