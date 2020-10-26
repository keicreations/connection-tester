# connection-tester

composer install
echo DATABASE_URL=mysql://user:pass@127.0.0.1:3306/db_name > .env.local
bin/console test:mysql [pool-size] [delay-in-seconds]
