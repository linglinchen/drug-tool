# Drug Tool

# Prerequisites

- The [Drug Tool development VM](http://wanda.elseviermultimedia.us/Web_Team/Virtual_Machines) if you're installing locally
- [Composer](https://getcomposer.org/download/) (already installed on the VM)

# Deploying to Bedrock

- As **u1geogit**, clone the repo into its destination. This command will clone it directly into your current directory: `git clone https://gitlab.et-scm.com/epd/drug-tool.git .`
- Set up DNS. You might need to update its URL in **settings.js** in the UI.
- Set up the vhost. Point it at the **public** directory.
- Test the vhost.
```
sudo service httpd configtest
```
- Ensure that all of the directories in **storage** and **bootstrap/cache** is writeable by Apache.
- Copy **.env.example** to **.env** -- **.env** should *always live outside of source control*.
- Alter **.env** to match suit the environment.
- Navigate to the directory where the project lives (e.g. **/var/www/drug-tool**)
- Install the Composer packages. You might need to modify this command depending on how you installed Composer. `composer install`
- Run the DB migrations: `php artisan migrate:refresh`
- Import data (see below).
- Restart Apache.
```
sudo service httpd graceful
```

# Deploying to a VM

- Clone the repo into **C:\git\www**. `git clone git@gitlab.et-scm.com:epd/drug-tool.git`
- Add entries to your **hosts** file.
```
127.0.0.1	drugtool.localhost.com
127.0.0.1	api.drugtool.localhost.com
```
- Copy **.env.example** to **.env** -- **.env** should *always live outside of source control*.
- Alter **.env** to match suit the environment.
- Start the VM, and enter a terminal session inside of it.
- Navigate to the directory where the project lives.
```
cd /var/www/drug-tool
```
- Install the Composer packages. You might need to modify this command depending on how you installed Composer. `composer.phar install`
- Run the DB migrations: `php artisan migrate:refresh`
- Seed the DB with junk data for testing purposes `php artisan db:seed` or import real data (see below).
- Restart Apache.
```
sudo service httpd restart
```

# Notes

- Refresh/reseed database by running: `php artisan migrate:refresh --seed`
- See a list of available Artisan commands: `php artisan`
- You can truncate the **users**, **atoms**, and **molecules** tables with these commands.
```
php artisan import:clearusers
php artisan import:clearatoms
php artisan import:clearmolecules
```
- Import users, atoms, or molecules. See their files in **app/Console/Commands** for more information.
```
php artisan import:users
php artisan import:molecules
php artisan import:atoms
```
- Sometimes Laravel will fail to find a class that you just added, even though you did everything correctly. In those cases, try running all of these commands in order. You might need to alter the composer command to fit your setup.
```
php artisan clear-compiled
composer dump-autoload
php artisan optimize
```
- By default, Laravel logs to **storage/logs/laravel.log**

# Third Party Libraries and Frameworks

- [Official Laravel 5.2 Documentation](https://laravel.com/docs/5.2)