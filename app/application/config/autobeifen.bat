rem *******************************Code Start*****************************
@echo off

set "Ymd=%date:~,4%%date:~5,2%%date:~8,2%"
D:\XAMPP\mysql\bin\mysqldump --opt -u root --password=mas123 osm > D:\db_backup\osm_%Ymd%.sql

@echo on
rem *******************************Code End*****************************

