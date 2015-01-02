<?php
	// Barebones CMS SSO Client integration plugin.
	// (C) 2013 CubicleSoft.  All Rights Reserved.

	if (!defined("BB_FILE"))  exit();

	if (!file_exists(ROOT_PATH . "/sso_client/config.php"))
	{
		echo htmlspecialchars(BB_Translate("Unable to find the SSO client configuration.  The SSO client must be installed into a subdirectory called 'sso_client' off the main Barebones CMS installation."));
		exit();
	}

	require_once ROOT_PATH . "/sso_client/config.php";
	require_once SSO_CLIENT_ROOT_PATH . "/index.php";

	$g_sso_client = new SSO_Client;
	$g_sso_client->Init(array("sso_impersonate", "sso_remote_id"));

	// Checks the SSO client login.
	function SSO_Client_CheckSession()
	{
		global $g_sso_client, $bb_accounts;

		if (!$g_sso_client->LoggedIn())
		{
			BB_RunPluginAction("access_denied");

			echo htmlspecialchars(BB_Translate("Session has expired."));
			exit();
		}

		if ($g_sso_client->UserLoaded())
		{
			// Send the browser cookies.
			$g_sso_client->SaveUserInfo();

			// Update 'bbq' and 'bbl' expiration.
			if (isset($bb_accounts["sessions"][$_REQUEST["bbl"]]))
			{
				require_once ROOT_PATH . "/" . SUPPORT_PATH . "/cookie.php";

				$bb_accounts["sessions"][$_REQUEST["bbl"]]["expire"] = time() + SSO_COOKIE_TIMEOUT + 60;
				BB_SaveUserAccounts();

				SetCookieFixDomain("bbl", $_REQUEST["bbl"], (SSO_COOKIE_TIMEOUT > 0 ? $bb_accounts["sessions"][$_REQUEST["bbl"]]["expire"] : 0), ROOT_URL . "/", "", USE_HTTPS, true);
				SetCookieFixDomain("bbq", "1", (SSO_COOKIE_TIMEOUT > 0 && !SSO_COOKIE_EXIT_TIMEOUT ? $bb_accounts["sessions"][$_REQUEST["bbl"]]["expire"] : 0), ROOT_URL . "/", "");
			}
		}
	}

	BB_AddPluginAction("accounts_loaded", "SSO_Client_CheckSession");

	// Disable account management.
	function SSO_Client_DisableAccountChanges()
	{
		BB_PropertyFormError("The ability to create, edit, and delete user accounts through Barebones CMS has been disabled by a plugin.");
	}

	BB_AddPluginAction("pre_bb_main_edit_site_opt_profile_submit", "SSO_Client_DisableAccountChanges");
	BB_AddPluginAction("pre_bb_main_edit_site_opt_create_account_submit", "SSO_Client_DisableAccountChanges");
	BB_AddPluginAction("pre_bb_main_edit_site_opt_delete_account_submit", "SSO_Client_DisableAccountChanges");

	// Handle SSO client logout.
	function SSO_Client_Logout()
	{
		global $g_sso_client;

		$g_sso_client->Logout();
	}

	BB_AddPluginAction("pre_bb_main_edit_site_opt_logout", "SSO_Client_Logout");
?>