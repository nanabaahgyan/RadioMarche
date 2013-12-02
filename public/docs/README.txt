
********************************************************************
*		RADIOMARCHE (DEVELOPMENT VERSION) + VOICE PLATFORM   	   *
********************************************************************

					Steps for Setting Up
========================================================
Step 1. Check out the RadioMarche folder from the repository(svn).
Step 2. Configure your environment (see below).
Step 3. Configure Platforms (see below).



********************************************************************
*				CONFIGURING YOUR ENVIONMENT					 	   *
********************************************************************

1. Virtual Hosting Environment set-up
========================================================

Set up a Virtual Hosting Environment. An example is below (for Apache).

<VirtualHost *:80>
   DocumentRoot "/home/username/radiomarche/public/" # point the document root of your server 
   													 # to public folder. with this set-up (this
   													 # configuration), the public folder 
   													 # is under the radiomarche folder
   ServerName servername							 

   <Directory "/home/username/radiomarche/public/">
       Options All
       AllowOverride All
       Order allow,deny
       Allow from all
   </Directory>
	
	ErrorLog ${APACHE_LOG_DIR}/radiomarcher-error.log

	# Possible values include: debug, info, notice, warn, error, crit,
	# alert, emerg.
	LogLevel warn

	CustomLog ${APACHE_LOG_DIR}/radiomarche-access.log combined
</VirtualHost>

2. Apache Server and PHP Modules Needed
=================================================

Environment modules needed include:

--> Apache Server modules required:

	** mod_rewrite (for the use of .htaccess)
	** mod_php5	(PHP module)
	
--> PHP modules required:
	
	** curl (for accessing resources via http calls)
	** gd	(image creation library)
	** json	(javascript object notation)
	** msql 
	** pdo_mysql	(pdo for mysql)
	
3. Create Database
===================================================

	** Find create_tables.sql in folder




********************************************************************
* 						CONFIGURE PLATFORMS					 	   *
********************************************************************

1. Setting up radiomarche
===================================================

1. Find the application config file in /application/configs/application.ini
2. Edit the sections where appropriate:
	
	** DB settings (your database settings)
	** voice.platform.base.url (note: without the ending '/')

3. Once up and running register with url (virtual-host-name)/account/register. 
   (This is also just clicking on REGISTER tab).
4. The action above (3) will create entry in the database users table.
5. Under user_type field of users table, change the entry 'guest' to 'ngo'. That
   makes you and ngo user who can create and publish communique on the voice platform.
6. You now login with username and password under (virtual-host-name)/account/login
   (or by just clicking on LOGIN tab).
