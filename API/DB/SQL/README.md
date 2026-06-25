# DBScripts

Scripts SQL para inicializar y migrar la base de datos `analiticaDB`.

## Ejecución

Ejecutar en orden numérico ascendente contra MySQL:

```bash
mysql -u analiticaDBUser -p < DB/SQL/001_create_database.sql
mysql -u analiticaDBUser -p analiticaDB < DB/SQL/002_*.sql
```

| Script | Descripción |
|--------|-------------|
| `001_create_database.sql` | Crea la base de datos `analiticaDB` con charset utf8mb4. |
| `002_create_structure.sql` | Crea tablas `users`, `roles`, `permissions`, `user_roles`, `role_permissions` con sus FK. |
