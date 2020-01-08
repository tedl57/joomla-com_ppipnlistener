<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Ppipnlistener
 * @author     Ted Lowe <lists@creativespirits.org>
 * @copyright  2018 Crea
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Ppipnlistener', JPATH_COMPONENT);
JLoader::register('PpipnlistenerController', JPATH_COMPONENT . '/controller.php');

// trlowe 10/18/18 convert this component into a server listener for PayPal IPN messages

// PayPal POSTS IPN variables into listener

$npostvars = 0;
if ( isset( $_POST) )
	$npostvars = count( $_POST );

putLogEntry( "Session started; npostvars = $npostvars" );

$logdetails = true;	// todo: make this a component option to turn on for debugging

if ( $npostvars > 0 )
{
	if ( $logdetails )
		putLogEntry( "_POST = " . var_export( $_POST, TRUE ) );
}
else
	jexit();	// nothing to process - just exit

// PayPal's IPN Simulator sets _POST["test_ipn"], whereas "live" transactions don't;  respond to the sandbox server or live server as appropriate

$sandbox = "";
if ( isset( $_POST["test_ipn"] ) )
{
	putLogEntry( "Testing in sandbox" );
	$sandbox = ".sandbox";
}

// The reply back to PayPal includes an echo of all the POST'ed keys & values with a 'notify validate' command added

$reply = 'cmd=_notify-validate';

foreach( $_POST as $key => $value )
{
	$value = urlencode(stripslashes($value));
	$reply .= "&$key=$value";
}

if ( $logdetails )
	putLogEntry( "reply = $reply" );

// prepare header for IPN POST back to PayPal server

// ipn fix 6/30/2018, for details see: 
// https://stackoverflow.com/questions/37589359/ipn-verification-postback-to-https

$header = "POST /cgi-bin/webscr HTTP/1.1\r\n";	// ipn fix 6/30/2018 (was 1.0)
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($reply) . "\r\n";
// pre-ipn fix $header .= "Host: www$DEBUG.paypal.com:80\r\n";
$header .= "Host: ipnpb$sandbox.paypal.com\r\n"; // ipn fix 6/30/2018
$header .= "Connection: close\r\n\r\n"; // add for ipn fix 6/30/2018

// open connection to PayPal server

// pre-ipn fix was:
// $fp = fsockopen ("www$DEBUG.paypal.com", 80, $errno, $errstr, 30);

$fp = fsockopen( "tls://ipnpb$sandbox.paypal.com", 443, $errno, $errstr, 30); // ipn fix 6/30/2018

// check connection status

if ( !$fp )
{
	// HTTPS ERROR
	// todo: handle this another way?

	putLogEntry( "ERROR: Connection to PayPal server failed!" );

	jexit();
}

// send IPN "post back" reply to PayPal server

fputs( $fp, $header . $reply );	// todo: check return?

// read response from PayPal server, looking for a VERIFIED response

$verified = false;

$response = "";

while( !feof( $fp ) )
{
	$res = fgets( $fp, 1024 );

	$response .= "$res|";	// add pipe signs to delimit received response lines

	if ((strcmp ($res, "VERIFIED"."\r\n" ) == 0)||(strcmp ($res, "VERIFIED" ) == 0)) // todo: not sure why paypal is adding ^M (CR=\r) to all, and paypal did NOT include new lines as of 1/8/2020
	{
		$verified = true;
		putLogEntry( "IPN Verified!" );
	}
}

if ( $logdetails )
		putLogEntry( "IPN Response: $response" );

// close connection with PayPal server

fclose( $fp );

// if the payment was not verified, log an error and quit
if ( ! $verified )
{
		putLogEntry( "ERROR: IPN NOT Verified!" );

		jexit();	// todo: what else to do?
}

// do backend processing of the verified payment
// 1. check the transaction exists
// 2. check the transaction is not already marked paid (or completed or cancelled?) - CAN'T check these.. since IPN's are asynchronous and could be queued/delayed to long after the user has completed or cancelled their payment on PayPal
// 3. todo: check the transaction amount and the paid amount match
// 4. todo: ?check the custom or "items" match?
// 5. if all the check pass, update the date_paid field in the DB (which will cause it to show in Payment Processor)

// todo: implement this backend portion

if ( ! isset( $_POST["invoice"]))
{	
	putLogEntry( "ERROR: No invoice POSTed" );

	jexit();	// todo: what else to do?
}
$id = $_POST["invoice"];
$db = JFactory::getDbo();
$db->setQuery("SELECT * FROM #__cs_payments WHERE id=$id");
$items = $db->loadObjectList();
$nitems = count($items);
//putLogEntry("nitems=$nitems");
if ( $nitems != 1)
{
	putLogEntry( "ERROR: invoice $id not in DB" );
	
	jexit();	// todo: what else to do?	
}
if ($items[0]->date_paid !== null )
{
	putLogEntry( "ERROR: invoice $id is already marked paid on " . $items[0]->date_paid );
	
	jexit();	// todo: what else to do?
}

// update date paid field in the invoice's record in the cs_payments table

$update_object = new stdClass();
$update_object->id = $id;
$update_object->date_paid = date('Y-m-d H:i:s');
$result = $db->updateObject('#__cs_payments', $update_object, 'id');
putLogEntry("SUCCESS: set invoice $id date paid to " . $update_object->date_paid );

jexit();	// listener doesn't need to continue into joomla MVC pattern

// Execute the task.
//$controller = JControllerLegacy::getInstance('Ppipnlistener');
//$controller->execute(JFactory::getApplication()->input->get('task'));
//$controller->redirect();

/////////////////////////////////////////////////////////////

function putLogEntry( $txt )
{
	static $nlogentries = 1;

	// object for adding log entries into the DB

	$session = new stdClass();
	$session->id = 0;	// set primary key for auto-increment on insert
	$session->entry_time = date('Y-m-d H:i:s');

	$session->entry_text = "(" . $nlogentries++ . ") $txt";

	// insert the log entry object into the DB

	$result = JFactory::getDbo()->insertObject('#__cs_ppipnlistener_log',
		$session, 'id');
}
