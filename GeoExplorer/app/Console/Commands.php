<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDatabaseCommand extends Command
{
    protected $signature = 'db:create {name? : Nombre opcional de la base de datos}';

    protected $description = 'Crea la base de datos configurada si aún no existe';

    public function handle(): int
    {
        $connectionName = config('database.default');
        $database = $this->argument('name') ?? config("database.connections.{$connectionName}.database");

        if ($connectionName === 'sqlite') {
            return $this->handleSqlite($database);
        }

        return $this->handleMysqlOrPostgres($connectionName, $database);
    }

    private function handleMysqlOrPostgres(string $connection, string $database): int
    {
        $config = config("database.connections.{$connection}");

        // Configurar conexión temporal sin especificar base de datos
        config(['database.connections.setup' => array_merge($config, ['database' => null])]);

        try {
            if ($config['driver'] === 'mysql') {
                $charset = $config['charset'] ?? 'utf8mb4';
                $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

                DB::connection('setup')->statement(
                    "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$collation};"
                );
            } elseif ($config['driver'] === 'pgsql') {
                $exists = DB::connection('setup')
                    ->select('SELECT 1 FROM pg_database WHERE datname = ?', [$database]);

                if (empty($exists)) {
                    DB::connection('setup')->statement("CREATE DATABASE \"{$database}\";");
                }
            }

            $this->info("Base de datos [{$database}] verificada/creada exitosamente.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error al crear la base de datos: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge('setup');
        }
    }

    private function handleSqlite(string $databasePath): int
    {
        if ($databasePath === ':memory:') {
            $this->info('Usando SQLite en memoria.');

            return self::SUCCESS;
        }

        if (! file_exists($databasePath)) {
            touch($databasePath);
            $this->info("Archivo SQLite creado en [{$databasePath}].");
        }

        return self::SUCCESS;
    }
}
