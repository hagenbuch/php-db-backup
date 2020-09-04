# php-db-backup
Backup script for generational backups of a SQL database

# Name: db-backup.php
# Version: 20161019
# Purpose

Make a daily backup of a database whenever called with the right token
- Keep daily backups for a certain number of days
- Keep weekly backups for a number of weeks
- Keep monthly backups for a number of months

# Usage: 

Called from a cronjob (like wget http://domain.tld/db-backup.php?secrettoken=xyz ) or included from / appended to another script

# License

MIT
# Author: 

Andreas Delleske

# Depends: 

- SQL (MySQL, MariaDB)
- PHP > 5.6 on Apache webserver
- Permission to use the exec() function
- Permission to use commands mysqldump, gzip in the shell 
- Webserver is permitted to write to the filesystem
- Works on file structure of all-inkl.com
- Bugs: Does not work on Microsoft Windows
