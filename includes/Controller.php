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
class XTC2OXIDUI_Controller
{
	/*
	 * task to execute
	 */
	private $_task  = NULL;
	
	/*
	 * template helper
	 */
	private $_tmpl = NULL;	

	/*
	 * C'tor
	 */
	public function __construct( $task = 'default' )
	{
		$this->_task	= $this->Filter( $task );	
	}

	/*
	 * D'tor
	 */
	public function __descturct()
	{
	}

	/**
	 * Determinate the task to execute.
	 */
	public function Task()
	{
		return  'Do' . ucfirst( strtolower( $this->_task ) );
	}
	
	/**
	 * Execute
	 *
	 * The Execute method is the only method to call, it is the entry point.
	 * It check's if the given task exists, and is callable, if not the default
	 * task will be called by call_user_method
	 */	
	public function Execute( )
	{
		$method = $this->Task();

		if( !method_exists( $this, $method ) )
		{
			return $this->DoDefault();
		}

		if( !is_callable( array( $this, $method ) ) )
		{
			return $this->DoDefault();
		}

		call_user_func( array( $this, $method ) );
	}	

	/**
	 * Check if we can handle the request
	 *
	 * This method checks if we can handle the given task.
	 *
	 * @return bool return true if the task is handable, otherwise false
	 */
	public function CanHandle()
	{
		$method = $this->Task();

		if( !method_exists( $this, $method ) )
		{
			return false;
		}

		if( !is_callable( array( $this, $method ) ) )
		{
			return false;
		}

		return true;
	}	
	
	private function SetSettings( &$tmpl,  $set )
	{
		$tmpl->Set( '{oxid-xtc-imgdir}' ,  $set['oxid-xtc-imgdir'] );
		$tmpl->Set( '{oxid-install-dir}', $set['oxid-install-dir'] );	
		
		$tmpl->Set( '{oxid-xtc-imgdir}'       ,  $set['oxid-xtc-imgdir'] );
		$tmpl->Set( '{oxid-xtc-shoptype}'     ,  $set['oxid-xtc-shoptype'] );
		$tmpl->Set( '{oxid-xtc-db}'           ,  $set['oxid-xtc-db'] );		
		$tmpl->Set( '{oxid-install-dir}'      ,  $set['oxid-install-dir'] );
		$tmpl->Set( '{oxid-xtc-dbcleanup}'    ,  $set['oxid-xtc-dbcleanup'] );			
	} 
	
	public function DoDefault()
	{
		$tmpl = new XTC2OXIDUI_Template( XTC2OXIDUI_BASEDIR . '/tmpl/step1.html' );
		$tmpl->Set( '{STATUS}', '<b>STEP 1/2:</b>' );		
		$this->SetSettings( $tmpl , $this->GetSettingsFromRequest() );		
		echo $tmpl->Render();
	}
	
	public function DoValidate()
	{
		$tmpl = new XTC2OXIDUI_Template( XTC2OXIDUI_BASEDIR . '/tmpl/step1.html' );
		$set    = $this->GetSettingsFromRequest();
		$this->SetSettings( $tmpl , $set );
		$tmpl->Set( '{STATUS}' , '' );
				
		$errors = array();
		
		$imgdir = $this->Filter( $set['oxid-xtc-imgdir'] );
		
		if( false == is_dir( $imgdir )  )
		{
			array_push( $errors, 'Invalid XTC Image Directory: ' . $imgdir );			
		}	
		
		$oxiddir = $this->Filter( $set['oxid-install-dir'] );
		
		if( false == is_dir( $oxiddir )  )
		{
			array_push( $errors, 'Invalid OXID Directory: ' . $oxiddir );			
		}else{		
		
		
		$this->InitOxid( $oxiddir );
		global $myConfig;
		$myConfig = oxConfig::getInstance();
		$user=oxSession::getVar('usr');

		if( strpos( $user,'admin' ) === false )
		{
			array_push( $errors, 'You have to be logged in as an administrator in the shop front-end in order to use the importer!' );	
		}		
		
		try {			
			$oxDB = oxdb::getDb( true );	

			$sql = 'SELECT COUNT(*) FROM ' . $set['oxid-xtc-db'] . '.products';
			$oxDB->Execute( $sql );
        	$error=$oxDB->ErrorMsg();
        	
       		if( $error )
        	{
        		array_push( $errors, 'Invalid XTC Database: ' . $set['oxid-xtc-db']  );
        	}
			
			
		}catch( oxConnectionException $e )
		{
			array_push( $errors, $e->getMessage() );				
		}		
		
		}
		
		if( count( $errors ) > 0 )
		{
			$html = '<b>Error!</b><ul>';
			
			foreach( $errors as $e )
			{
				$html .= '<li>' . $e . '</li>';
			}
			
			$html .= '</ul>';
		
			$tmpl->Set( '{STATUS}', $html );			
		}else{			
			
			$step2 = new XTC2OXIDUI_Template( XTC2OXIDUI_BASEDIR . '/tmpl/step2.html' );
			$tmpl->Set( '{STATUS}', $step2->Render() );			
			$_SESSION['xtc2oxidui'] = $set;
		}
		
		echo $tmpl->Render();
	}
	
	private function InitOxid( $oxiddir )
	{				
		//now check oxid framework required settings
		global $sOxidConfigDir;
		$sOxidConfigDir = $oxiddir;		
		
		define('IS_ADMIN_FUNCTION',true);		
		require_once( XTC2OXIDUI_BASEDIR   . '/_functions.inc.php' );
		@include_once($oxiddir  . '/_version_define.php' );
		require_once( $oxiddir   . '/core/oxfunctions.php' );
		require_once( $oxiddir   . '/core/adodblite/adodb.inc.php' );			
	}
	
	public function DoMigrate()
	{
		set_time_limit(0);
		$set = $_SESSION['xtc2oxidui'];			
		$oxiddir = $set['oxid-install-dir'];
		
		$this->InitOxid( $oxiddir );	
		global $myConfig;
		$myConfig = oxConfig::getInstance();
		
		$xtc = (bool)( $set['oxid-xtc-shoptype'] == 'xtc' );
		
		global $iLangCount; //some magic value from xtc2oxid.php
		$iLangCount = 4;
		
		global $sOcmDb;
		$sOcmDb = $set['oxid-xtc-db'];
		
		 global $sOscImageDir;
		 $sOscImageDir = $set['oxid-xtc-imgdir'];
		 
		 global $sXtcLangId;
		 
		 global $payment_types;
		 
		 $payment_types=array(
  			'banktransfer'=>'oxiddebitnote',
  			'cash'=>'oxcash',
  			'cc'=>'oxidcreditcard',
  			'cod'=>'oxidcashondel',
  			'eustandardtransfer'=>'oxidinvoice',
  			'invoice'=>'oxidinvoice',
  			'ipayment'=>'oxidcreditcard',
  			'moneyorder'=>'oxidinvoice',
  			'paypal'=>'oxpaypal',
  			'uos_kreditkarte_modul'=>'oxidcreditcard',
  			'uos_lastschrift_at_modul'=>'oxiddebitnote',
  			'uos_lastschrift_de_modul'=>'oxiddebitnote',
  			'uos_vorkasse_modul'=>'oxidpayadvance',
		);  
		
		if( true == $xtc )
		{
			$oIHandler = new XtImportHandler( $myConfig->getBaseShopId() );		
		}else{
			$oIHandler = new ImportHandler( $myConfig->getBaseShopId() );		
		}

		if( true == $set['oxid-xtc-dbcleanup'] )
		{
			
		}
		
		$oIHandler->setLanguages();
		
		$html = '';

		//db cleanup
		if( true == $this->GetSettingFromRequest( 'oxid-xtc-dbcleanup', true ) )
		{
			$html .= '<b>Cleanup Database:</b> done<br/>';
			$oIHandler->cleanUpBeforeImport();			
		}
		
		//customers
		if( true == $this->GetSettingFromRequest( 'oxid-import-customers', true ) )
		{
			$html .= '<b>Migrate Customers:</b> done<br/>';
			$html .= $this->MigrateCustomers( $oIHandler );
			$html .= '<br/>';	
		}	

		//manu
		if( true == $this->GetSettingFromRequest( 'oxid-import-manufacturers', true ) )
		{
			$html .= '<b>Migrate Manufacturers:</b> done<br/>';
			$html .= $this->MigrateManufacturers( $oIHandler );
			$html .= '<br/>';
		}			

		if( true == $this->GetSettingFromRequest( 'oxid-import-categories', true ) )
		{
			$html .= '<b>Migrate Categories:</b> done<br/>';
			$manu = $this->MigrateCategories( $oIHandler );
			$html .= '<br/>';
		}

		if( true == $this->GetSettingFromRequest( 'oxid-import-products', true ) )
		{
			$html .= '<b>Migrate Products:</b> done<br/>';
			$manu = $this->MigrateProducts( $oIHandler );
			$html .= '<br/>';
			
			if( true == $this->GetSettingFromRequest( 'oxid-import-products-reviews', true ) )
			{
				$html .= '<b>Migrate Products Review:</b> done<br/>';
				$manu = $this->MigrateProductsReviews( $oIHandler );
				$html .= '<br/>';
			}
						
			if( true == $this->GetSettingFromRequest( 'oxid-import-products-variants', true ) )
			{
				$html .= '<b>Migrate Products Variants:</b> done<br/>';
				$manu = $this->MigrateProductsVariants( $oIHandler );
				$html .= '<br/>';
			}			

			if( true == $this->GetSettingFromRequest( 'oxid-import-products-extended', true ) )
			{
				$html .= '<b>Migrate Products Extended Info:</b> done<br/>';
				$manu = $this->MigrateProductsExtendedInfo( $oIHandler );
				$html .= '<br/>';
			}									
		}
		
		if( true == $this->GetSettingFromRequest( 'oxid-import-orders', true ) )
		{
			$html .= '<b>Migrate Orders:</b> done<br/>';
			$manu = $this->MigrateOrders( $oIHandler );
			$html .= '<br/>';
		}		

		if( true == $this->GetSettingFromRequest( 'oxid-import-images', true ) )
		{
			$html .= '<b>Migrate Images:</b> done<br/>';
			$manu = $this->MigrateImages( $oIHandler );
			$html .= '<br/>';
		}

		$tmpl = new XTC2OXIDUI_Template( XTC2OXIDUI_BASEDIR . '/tmpl/step4.html' );		
		$tmpl->Set( '{RESULT}', $html );
		echo $tmpl->Render();			
	}
	
	private function MigrateCustomers( $io )
	{
		ob_start();
		$io->importCustomers();
		return ob_get_clean();
	}
	
	private function MigrateManufacturers( $io )
	{
		ob_start();
		$io->importManufacturers();
		return ob_get_clean();
	}	
	
	private function MigrateCategories( $io )
	{
		ob_start();
		$io->importCategories();
		$io->rebuildCategoryTree();
		return ob_get_clean();
	}	
	
	private function MigrateProducts( $io )
	{	
		ob_start();
		$io->importProducts();
		$io->importProduct2Categories();
		return ob_get_clean();
	}

	private function MigrateProductsReviews( $io )
	{		
		ob_start();
		$io->importReviews();
		return ob_get_clean();
	}

	private function MigrateProductsVariants( $io )
	{
		ob_start();
		$io->importVariants();
		return ob_get_clean();
	}	

	private function MigrateProductsExtendedInfo( $io )
	{
		ob_start();
		$io->importExtended();
		return ob_get_clean();
	}		
	
	private function MigrateOrders( $io )
	{
		ob_start();
		$io->importOrders();
		return ob_get_clean();
	}
	
	private function MigrateImages( $io )
	{
		ob_start();
		$io->handleManufacturerImages();
		$io->handleCategoryImages();
		$io->handleProductImages();
		return ob_get_clean();
	}	

	public function DoMigratesettings()
	{
		$tmpl = new XTC2OXIDUI_Template( XTC2OXIDUI_BASEDIR . '/tmpl/step3.html' );		
		$tmpl->Set( '{STATUS}', '<b>Step 2/2</b>' );
		echo $tmpl->Render();		
	}
	
	private function GetSettingsFromRequest()
	{
		$set = array();
				
		$set['oxid-xtc-imgdir']     = $this->GetSettingFromRequest( 'oxid-xtc-imgdir', '/var/www/xtc/images/' );	
		$set['oxid-xtc-shoptype']   = $this->GetSettingFromRequest( 'oxid-xtc-shoptype', 'xtc' );	
		$set['oxid-xtc-db']         = $this->GetSettingFromRequest( 'oxid-xtc-db', 'xtc database name' );
		$set['oxid-install-dir']    = $this->GetSettingFromRequest( 'oxid-install-dir', '/var/www/oxid/' );
		$set['oxid-xtc-dbcleanup']  = (bool)$this->GetSettingFromRequest( 'oxid-xtc-dbcleanup', true );
				
		return $set;	
	}
	
	private function GetSettingFromRequest( $name, $default )
	{
		$val = $default;
		
		if( isset( $_POST[$name] ) )
		{
			$val = $this->Filter( $_POST[$name] );
		}
				
		return $val;
	}
	
	private function Filter( $val )
	{
		return htmlentities( strip_tags( $val ) , ENT_QUOTES ); 
	}


} //end class
?>