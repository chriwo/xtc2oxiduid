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
class XTC2OXIDUI_Template
{
	/*
	 * html snippet url
	 */
	private $_html  = NULL;

	/*
	 *  html snippets given from client
	 */
	private $_snippets  = NULL;

	/*
	 * C'tor
	 */
	public function __construct( $file )
	{
		$this->_html	= file_get_contents( $file );
		$this->_snippets = array();
	}

	/*
	 * D'tor
	 */
	public function __descturct()
	{
	}

	/*
	 * Save content to cache
	 */
	public function Set( $key, $content )
	{
		$this->_snippets[$key] = $content;
	}

	/*
	 * Render all snippets into the html template and give it back
	 */
	public function Render()
	{
		$html = $this->_html;

		foreach( $this->_snippets as $key => $content )
		{
			$html = str_replace( $key, $content, $html );
		}

		return $html;
	}

} //end class
?>