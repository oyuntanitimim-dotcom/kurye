Backup taken: Paz 26.04.2026  6:52:16,30
DB: backup\db\platform_db_*.sql
Storage: backup\storage\storage_public_*.zip
Restore DB: mysql -h127.0.0.1 -uroot platform_db < backup\db\platform_db_*.sql
Restore storage: extract zip to storage\app\public
