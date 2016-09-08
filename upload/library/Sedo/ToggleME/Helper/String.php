<?php
class Sedo_ToggleME_Helper_String
{
	public static function sanitize($string = '')
	{
		return filter_var ($string, FILTER_SANITIZE_STRING);
	}
}