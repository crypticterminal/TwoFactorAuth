<?php
/**
 * TwoFactorAuth login file - This script authenticates the user provided
 * his username/passwords as saved in the user database, and an OTP token
 * generated by the Google Authenticator app, based on a shared secret.
 *
 * @author Arno0x0x - https://twitter.com/Arno0x0x
 * @license GPLv3 - licence available here: http://www.gnu.org/copyleft/gpl.html
 * @link https://github.com/Arno0x/
 */
 
//------------------------------------------------------
// Include config file
require_once("../config.php");

// Allow included script to be included from this script
define('INCLUSION_ENABLED',true);

//-----------------------------------------------------
// Sending no-cache headers
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', false );
header( 'Pragma: no-cache' );

//------------------------------------------------------
// If any form variable is missing, just display the login page
if (!isset($_POST["username"]) || !isset($_POST["password"]) || !isset($_POST["token"])) {
	require_once("loginForm.php");
}
else {
    //------------------------------------------------------
    // Retrieve and store form parameters
    $username = htmlspecialchars($_POST["username"]);
    $password = $_POST["password"];
    $token = $_POST["token"];
    
    //-----------------------------------------------------
    // Import database manager library
    require_once(DBMANAGER_LIB);
    try {
    	// Create the DB manager object
    	$dbManager = new DBManager(USER_SQL_DATABASE_FILE);
    	
    	// Retrieve the password hash and stored Google Auth secret for this user
	    if (!($result = $dbManager->getPasswordHashAndGauthSecret($username))) {
	    	$error = "[ERROR] Unknown user";
	    } else {
	    	// Import the GoogleAuth library and create a GoogleAuth object
		    require_once(GAUTH_LIB);
		    $gauth = new GoogleAuthenticator();
	    	
	    	// Checking password hash and token
	    	if (($result['PASSWORDHASH'] !== hash("sha256",$password)) || !($gauth->verifyCode($result['GAUTHSECRET'],$token))) {
	   			$error = "[ERROR] Authentication failed";
	       	} else {
	       		$isAdmin = $dbManager->getAdminStatus($username);
	       	}
	    }
	    
	    $dbManager->close();
	    	
    	//--------------------------------------------------
	    // Login successful - let's proceed
	    if (!isset($error)) {
	        //--------------------------------------------------
	        // Creating a session to persist the authentication
	        session_name(SESSION_NAME);
	        session_cache_limiter('private_no_expire');
	        
	        // Session parameters :
	        // - Timelife of of the whole browser session
	        // - Valid for all path on the domain, for this FQDN only
	        // - Ensure Cookies are not available to Javascript
	        // - Cookies are sent on https only
	        $domain = ($_SERVER['HTTP_HOST'] !== 'localhost') ? $_SERVER['SERVER_NAME'] : false;
	        session_set_cookie_params (0, "/", $domain, true, true);
	    
	        // Create a session
	        session_start();
	        
	        $_SESSION["authenticated"] = true;
	        $_SESSION["username"] = $username;
	        $_SESSION["isAdmin"] = ($isAdmin === 1)? true: false;
	        
	        //--------------------------------------------------
	        // Checking which URL we should redirect the user to
	        if (isset($_POST["from"])) {
	        	$from = urldecode($_POST["from"]);
	            $redirectTo = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on")? "https://" : "http://").$_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$from;
	        }
	        else {
	            $redirectTo = AUTH_SUCCEED_REDIRECT_URL;
	        }
	        header("Location: ".$redirectTo,true,302);
		}
    	else {
    	    http_response_code(403);
        	require_once("loginForm.php");   
    	}
    } catch (Exception $e) {
    	$error = "[ERROR] Cannot open user database file";
    	require_once("loginForm.php");
    }
}
?>