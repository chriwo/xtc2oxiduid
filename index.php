<?php
/**
* XTC2OXIDUI - Simple html ui for xtc2oxid
*
* http://www.joomlaconsulting.de
*
* All rights reserved. 
*
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* XTC2OXIDUI! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
**/ 
session_start();
define( 'XTC2OXIDUI_BASEDIR', dirname( __FILE__ ) );

require_once XTC2OXIDUI_BASEDIR . '/includes/Template.php';
require_once XTC2OXIDUI_BASEDIR . '/includes/Controller.php';

$task = 'default'; 

if( $_SERVER['REQUEST_METHOD'] == 'POST' )
{
	$task = $_POST['task'];
}

$controller = new XTC2OXIDUI_Controller( $task );

if( false == $controller->CanHandle() )
{
	echo 'Unable to handle task: ' . $controller->Task();
	exit;
}

$controller->Execute();
?>