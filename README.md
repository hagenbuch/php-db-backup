# php-db-backup
Backup script for generational backups of a SQL database

## Filenames

```
db-backup.php
```

for the executed script doing all the work

```
<YYYY><MM><DD>w<WW>-<domain>-<dbname>.sql.gz 
```
in a folder named by the database. The folder must be **outside** of the web root otherwise an attacker can guess the filename!
  
Change the script according to your demand, it would have been too messy to make everything configurable.

If you don't understand it, you would most likely not understand the security implications too so please don't use it then.

## Version

20161019

## Purpose

Make a daily backup of a database whenever called with the right token

- Keep daily backups for a certain number of days
- Keep weekly backups for a number of weeks
- Keep monthly backups for a number of months

## Usage

Call from a cronjob (like wget http://domain.tld/db-backup.php?secrettoken=xyz ) or included from / appended to another script

## Security

- The folder of the created database files must be **outside** of the web root otherwise an attacker can guess the filename!
- Make sure the file's sourcecode can't be read by appending an "s" to the filename extension, like db-backup.phps (feature of the webserver!)
- Also the use of "exec" and shell commands in your webspace might be restricted. Don't be disappointed if it doesn't work.
- Make sure you script on the webserver can not be executed or read by anyone else. I can't give you exact hints how to do it, depends very much on your webhoster.

## Room for improvement

- Could be adapted to use a public webroot folder even for the database files but then, the webserver would have to be configured accordingly (.htaccess for Pacahe)
- I might accept pull requests!

## Rant

Yeah, it's a dirty hack, it's not object-oriented, has not even a function :-)

## License

MIT License

## Author

Andreas Delleske

## Dependencies 

- SQL (MySQL, MariaDB)
- PHP > 5.6 on Apache webserver
- Permission to use the exec() function
- Permission to use commands mysqldump, gzip in the shell 
- Webserver is permitted to write to the filesystem
- Works on file structure of all-inkl.com

## Bugs

Does not work on Microsoft Windows
