# Drug Tool

# Prerequisites

- The [Drug Tool development VM](http://wanda.elseviermultimedia.us/Web_Team/Virtual_Machines) if you're installing locally
- [Composer](https://getcomposer.org/download/)

# Deployment

- As **u1geogit**, clone the repo into its destination. This command will clone it directly into your current directory: `git clone https://gitlab.et-scm.com/epd/drug-tool.git .`
- Set up DNS. You might need to update its URL in **settings.js** in the UI.
- Set up the vhost. Point it at the **public** directory.
- Ensure that all of the directories in **storage** and **bootstrap/cache** is writeable by Apache.
- Copy **.env.example** to **.env** -- **.env** should *always live outside of source control*.
- Alter **.env** to match suit the environment.
- Update the Composer packages. You might need to modify this command depending on how you installed Composer. `composer update`

# Notes

- Refresh/reseed database by running: `php artisan migrate:refresh --seed`
- See a list of available Artisan commands: `php artisan`
- You can truncate the **users** and **atoms** tables with these commands:
```
php artisan import:clearusers
php artisan import:clearatoms
```
- Import users or atoms. See their files in **app/Console/Commands** for more information.
```
php artisan import:users
php artisan import:atoms
```

# Helpful Links

- [Official Laravel 5.2 Documentation](https://laravel.com/docs/5.2)