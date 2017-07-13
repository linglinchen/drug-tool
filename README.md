# Drug Tool

# Prerequisites

- The [Drug Tool development VM](http://wanda.elseviermultimedia.us/Web_Team/Virtual_Machines) if you're installing locally
- [Composer](https://getcomposer.org/download/) (already installed on the VM)

# Deploying to the Bedrock servers (typical)

- Switch to u1geogit: `op u1geogit`
- Run the modernize script: `./modernize.sh`
- Navigate to the directory `cd /var/www/api.drugtool.elseviermultimedia.us` (production) or `cd /var/www/api.drugtool-dev.elseviermultimedia.us` (development)
- Update the code: `git pull`
- Following a successful update, you should see that the project is automatically rebuilt and migrated without errors.
- Update the UI if necessary, and then test the site in your browser.

# Deploying to Bedrock for the first time

- Switch to u1geogit: `op u1geogit`
- Run the modernize script: `./modernize.sh`
- Clone the repo into its destination. This command will clone it directly into your current directory: `git clone https://gitlab.et-scm.com/epd/drug-tool.git .`
- Set up the [Git hooks](githooks).
- Set up DNS. You might need to update the URL in **settings.js** in the UI.
- Set up the vhost. Point it at the **public** directory.
- Test the vhost.
```
sudo service httpd configtest
```

- Ensure that everything in **storage** and **bootstrap/cache** is writeable by Apache.
- Copy **.env.example** to **.env** -- **.env** should *always live outside of source control*.
- Alter **.env** to match suit the environment.
- Navigate to the directory where the project lives (e.g. **/var/www/drug-tool**)
- Install the Composer packages. `composer install`
- Run the DB migrations: `php artisan migrate`
- Import data (see below).
- Restart Apache.
```
sudo service httpd graceful
```

# Deploying to a VM

- Clone the repo into **C:\git\www**. `git clone git@gitlab.et-scm.com:epd/drug-tool.git`
- Set up the [Git hooks](githooks).
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
- Install the Composer packages. `composer install`
- Run the DB migrations: `php artisan migrate`
- Restart Apache.
```
sudo service httpd restart
```
Note: In VM you can optionally run optimize that should work as well versus restarting Apache
```
php artisan optimize
```
# Notes

- **All** modifications to the database structure should be handled with migrations. If you make the change with something like pgAdmin or Heidi, *you're doing it wrong!*
- Most custom code is in the **app** directory.
- Refresh database by running: `php artisan migrate:refresh` *Never do this on production!!!*
- See a list of available Artisan commands: `php artisan`
- You can truncate the various tables with these commands.
```
php artisan import:clear users
php artisan import:clear groups
php artisan import:clear access_control_structure
php artisan import:clear access_controls
php artisan import:clear atoms
php artisan import:clear molecules
php artisan import:clear tasks
php artisan import:clear statuses
```
- Import all the things. See their files in **app/Console/Commands** for each command's expected inputs. Import files belong in **data**, and are gitignored by default.
```
php artisan import:users
php artisan import:groups
php artisan import:aclstructure
php artisan import:acl
php artisan import:atoms
php artisan import:molecules
php artisan import:tasks
php artisan import:statuses
```
- Sometimes Laravel will fail to find a class that you just added, even though you did everything correctly. In those cases, try running all of these commands in order. You might need to alter the composer command to fit your setup.
```
composer install
php artisan clear-compiled
composer dump-autoload
php artisan optimize
```
- By default, Laravel logs to **storage/logs/laravel.log**
- Counting all of the characters in each chapter is a moderately expensive operation, so it has been rolled into an Artisan command. On dev and prod servers, it should be run as a nightly cron job: `php artisan report:estimatePages`

# Third Party Libraries and Frameworks

- [Laravel 5.2](https://laravel.com/docs/5.2)
- [Doctrine DBAL](http://www.doctrine-project.org/projects/dbal.html) - Embiggens (It's a perfectly cromulent word!) the ORM. Great for renaming columns in migrations.
- [Codeception](http://codeception.com/) - For unit testing.

# More documentation

- [Database structure](docs/db.md)
- [Git Hooks](githooks) - Like the name suggests, Git hooks live here. See the [readme](githooks) for instructions.