<?php

/**
	Function to help debug: outputs var_dumps of all arguments.
**/

if ( ! function_exists( 'ddd' ) )
{
	function ddd()
	{
		$r = array();
		$args = func_get_args();
		foreach( $args as $arg )
			$r[] = trim( htmlspecialchars( var_export( $arg, true ) ), "'" );
		echo implode( ' ', $r );
		echo "\n";
	}
}

require_once( __DIR__ . '/../autoload/vendor/autoload.php' );
