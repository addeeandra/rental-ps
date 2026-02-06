SELECT 'CREATE DATABASE cms'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'cms')\gexec
