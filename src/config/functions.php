<?php

/**
 * [siteinfo description]
 * return some website directories URL
 * 
 * @param  [string] $path [the requested directory]
 * @return [string]
 */
function siteinfo( $path ) {
	switch ( $path ) {
		case 'css_dir':
			echo PATH . '/css';
			break;
		case 'js_dir':
			echo PATH . '/js';
			break;
		case 'components_dir' :
			echo PATH . '/components';
			break;
		case 'base_dir':
			echo PATH;
			break;
	}
}

/**
 * [sitedir description]
 * return the website base URL
 * 
 * @return [string]
 */
function sitedir() {
	return PATH;
}

?>