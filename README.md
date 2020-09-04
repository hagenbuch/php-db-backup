# php-db-backup
Backup script for generational backups of a SQL database

## Filenames

- db-backup.php for the executed script doing all the work
- <YYYY><MM><DD>w<WW>-<domain>-<dbname>.sql.gz in a folder named by the database.
  
Change the script according to your needs, it would have been too messy to make everything configurable.

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

- Make sure the file's sourcecode can't be read by appending an "s" to the filename extension, like gb-backup.phps (feature of the webserver!)
- Also the use of "exec" and shell commands in your webspace might be restricted. Don't be disappointed if it doesn't work.
- Make sure you script on the webserver can not be executed or read by anyone else. I can't give you exact hints how to do it, depends very much on your webhoster.

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
- Bugs: Does not work on Microsoft Windows
