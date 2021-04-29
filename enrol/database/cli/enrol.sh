#!/bin/bash
SECONDS=0
export INFORMIXDIR=/opt/informix
export PATH=$INFORMIXDIR/bin:$PATH
export INFORMIXSERVER=carsitcp
export INFORMIXSQLHOSTS=$INFORMIXDIR/etc/sqlhosts
export LD_LIBRARY_PATH=$INFORMIXDIR/lib:$INFORMIXDIR/lib/cli:$INFORMIXDIR/lib/esql:$LD_LIBRARY_PATH\
	                export CLIENT_LOCALE=en_us.819
export DB_LOCALE=en_us.819
export ODBCINI=/etc/odbc.ini

env |sort > /home/rrusso/env.txt
# php /var/www/html/enrol/database/cli/sync.php -v
php /var/www/html/admin/cli/scheduled_task.php --execute=\\enrol_database\\task\\sync_enrolments

duration=$SECONDS
echo "Enrollment took $(($duration / 60)) minutes and $(($duration % 60)) seconds to complete."
