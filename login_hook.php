<?php
	// Barebones CMS SSO Client integration login hook.
	// (C) 2013 CubicleSoft.  All Rights Reserved.

	if (!defined("ROOT_PATH"))  exit();

	// Load the SSO client.
	if (!file_exists(ROOT_PATH . "/sso_client/config.php"))
	{
		echo "Unable to find the SSO client configuration.  The SSO client must be installed into a subdirectory called 'sso_client' off the main Barebones CMS installation.";
		exit();
	}

	require_once ROOT_PATH . "/sso_client/config.php";
	require_once SSO_CLIENT_ROOT_PATH . "/index.php";

	$g_sso_client = new SSO_Client;
	$g_sso_client->Init(array("sso_impersonate", "sso_remote_id"));

	// Check/initiate login.
	$g_sso_extra = array();
	if (isset($_REQUEST["sso_remote_id"]) && is_string($_REQUEST["sso_remote_id"]))
	{
		$g_sso_extra["sso_provider"] = "sso_remote";
		$g_sso_extra["sso_remote_id"] = $_REQUEST["sso_remote_id"];
	}
	if (!$g_sso_client->LoggedIn())  $g_sso_client->Login("", "", $g_sso_extra);

	// Check for access fields.
	if (!$g_sso_client->UserLoaded())
	{
		if (!$g_sso_client->LoadUserInfo())
		{
			echo "Unable to load user information.";
			exit();
		}
	}

	// Extract information.
	if ($g_sso_client->IsSiteAdmin())
	{
		$g_sso_type = "dev";
		$g_sso_group = "";
	}
	else
	{
		$g_sso_type = $g_sso_client->GetField("bb_cms_type");
		$g_sso_group = $g_sso_client->GetField("bb_cms_group");
		if ($g_sso_group === false)  $g_sso_group = "";
	}

	// Send the browser cookies.
	$g_sso_client->SaveUserInfo();

	// Test permissions for the user.
	if ($g_sso_type !== "dev" && $g_sso_type !== "design" && $g_sso_type !== "content")  $g_sso_client->Login("", "insufficient_permissions");

	// Load user accounts.
	require_once ROOT_PATH . "/accounts.php";
	BB_DeleteExpiredUserSessions();

	// Create a new user if it doesn't exist or has changed types.
	$g_sso_user = "sso_" . $g_sso_client->GetUserID();
	if (isset($bb_accounts["users"][$g_sso_user]) && ($bb_accounts["users"][$g_sso_user]["type"] != $g_sso_type || $bb_accounts["users"][$g_sso_user]["group"] != $g_sso_group))
	{
		BB_DeleteUser($g_sso_user);
	}
	if (!isset($bb_accounts["users"][$g_sso_user]))
	{
		$g_sso_pass = $g_sso_client->GetSecretToken();
		BB_CreateUser($g_sso_type, $g_sso_user, $g_sso_pass, $g_sso_group);
	}

	// Sign the user in.
	require_once ROOT_PATH . "/" . SUPPORT_PATH . "/cookie.php";

	$id = BB_NewUserSession($g_sso_user, (isset($_REQUEST["bbl"]) ? $_REQUEST["bbl"] : ""));
	if ($id === false)  $id = BB_NewUserSession($g_sso_user, "");
	$bb_accounts["sessions"][$id]["expire"] = time() + SSO_COOKIE_TIMEOUT + 60;
	BB_SaveUserAccounts();

	SetCookieFixDomain("bbl", $id, (SSO_COOKIE_TIMEOUT > 0 ? $bb_accounts["sessions"][$id]["expire"] : 0), ROOT_URL . "/", "", USE_HTTPS, true);
	SetCookieFixDomain("bbq", "1", (SSO_COOKIE_TIMEOUT > 0 && !SSO_COOKIE_EXIT_TIMEOUT ? $bb_accounts["sessions"][$id]["expire"] : 0), ROOT_URL . "/", "");

	header("Location: " . BB_GetFullRootURLBase("http"));
	exit();
?>