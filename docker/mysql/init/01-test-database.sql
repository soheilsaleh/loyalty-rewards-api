-- Runs once, the first time the MySQL data volume is initialised.
-- Creates the database used by the automated test suite.
CREATE DATABASE IF NOT EXISTS loyalty_test;

GRANT ALL PRIVILEGES ON loyalty_test.* TO 'loyalty'@'%';

FLUSH PRIVILEGES;
