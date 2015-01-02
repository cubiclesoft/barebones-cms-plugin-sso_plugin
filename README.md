Barebones CMS SSO Server/Client Integration plugin
==================================================

Installs a plugin and a login hook that overrides the Barebones CMS login system with the Barebones SSO server/client software.  Before installing, make sure the SSO client has been installed to a directory called 'sso_client' off the main Barebones CMS installation.

http://barebonescms.com/documentation/sso/

License
-------

Same as Barebones CMS.  MIT or LGPL (your choice).

Before Installing
-----------------

Go over to the SSO server admin and set up the following field mapping for a new API key (all fields are optional):

  * 'bb_cms_type' with a value of 'dev', 'design', or 'content'.
  * 'bb_cms_group' for 'content' account types.

Install the SSO Client to a directory called 'sso_client' off the root Barebones CMS directory.  Make sure the "SSO Client Accepts Site Admin" option is to your liking.  SSO Site Admins automatically get a 'dev' account.

Automated Extension Installation
--------------------------------

Once the SSO server and client are installed, use the built-in Barebones CMS extension installer to install the extension.

Manual Extension Installation
-----------------------------

Copy login_hook.php to the directory where login.php resides.

Copy the 'sso' subdirectory and all of its contents to /plugins/

Manual Uninstall
----------------

Delete the login_hook.php and the 'sso' subdirectory from /plugins/
