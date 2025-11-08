<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $statuses = [
            'pending',
            'confirmed',
            'scheduled',
            'out_for_pickup',
            'picked_up',
            'at_warehouse',
            'in_transit',
            'out_for_delivery',
            'delivery_attempted',
            'delivered',
            'returned',
            'returned_to_sender',
            'on_hold',
            'failed',
            'cancelled',
        ];

        if ($this->isPostgres()) {
            $this->ensureEnumTypeExists('parcel_deliveries_status_enum', $statuses);
            $this->ensureColumnUsesEnum('parcel_deliveries', 'status', 'parcel_deliveries_status_enum');
            $this->addEnumValues('parcel_deliveries_status_enum', $statuses);
            $this->setEnumDefault('parcel_deliveries', 'status', 'pending');

            $this->ensureEnumTypeExists('parcel_delivery_trackings_status_enum', $statuses);
            $this->ensureColumnUsesEnum('parcel_delivery_trackings', 'status', 'parcel_delivery_trackings_status_enum');
            $this->addEnumValues('parcel_delivery_trackings_status_enum', $statuses);
        } elseif ($this->isMysql()) {
            $this->updateMysqlEnum('parcel_deliveries', 'status', $statuses, 'pending');
            $this->updateMysqlEnum('parcel_delivery_trackings', 'status', $statuses);
        }
    }

    public function down(): void
    {
        $originalStatuses = [
            'pending',
            'picked_up',
            'in_transit',
            'delivered',
            'cancelled',
        ];

        if ($this->isPostgres()) {
            $this->recreateEnum(
                'parcel_deliveries_status_enum',
                'parcel_deliveries',
                'status',
                $originalStatuses,
                'pending'
            );

            $this->recreateEnum(
                'parcel_delivery_trackings_status_enum',
                'parcel_delivery_trackings',
                'status',
                $originalStatuses
            );
        } elseif ($this->isMysql()) {
            $this->updateMysqlEnum('parcel_deliveries', 'status', $originalStatuses, 'pending', true);
            $this->updateMysqlEnum('parcel_delivery_trackings', 'status', $originalStatuses, null, true);
        }
    }

    private function addEnumValues(string $enumType, array $values): void
    {
        foreach ($values as $value) {
            DB::statement(<<<SQL
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1
                        FROM pg_type t
                        JOIN pg_enum e ON t.oid = e.enumtypid
                        WHERE t.typname = '{$enumType}'
                          AND e.enumlabel = '{$value}'
                    ) THEN
                        ALTER TYPE {$enumType} ADD VALUE '{$value}';
                    END IF;
                END
                $$;
            SQL);
        }
    }

    private function recreateEnum(
        string $enumType,
        string $table,
        string $column,
        array $values,
        ?string $default = null
    ): void {
        $enumList = "'" . implode("','", $values) . "'";

        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP DEFAULT");
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE text USING {$column}::text");
        DB::statement("DROP TYPE IF EXISTS {$enumType}");
        DB::statement("CREATE TYPE {$enumType} AS ENUM ({$enumList})");
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE {$enumType} USING {$column}::{$enumType}");

        if ($default !== null) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '{$default}'");
        }
    }

    private function setEnumDefault(string $table, string $column, string $default): void
    {
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '{$default}'");
    }

    private function ensureEnumTypeExists(string $enumType, array $values): void
    {
        $enumList = "'" . implode("','", $values) . "'";

        DB::statement(<<<SQL
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM pg_type
                    WHERE typname = '{$enumType}'
                ) THEN
                    EXECUTE 'CREATE TYPE {$enumType} AS ENUM ({$enumList})';
                END IF;
            END
            $$;
        SQL);
    }

    private function ensureColumnUsesEnum(string $table, string $column, string $enumType): void
    {
        $columnInfo = DB::selectOne(
            "SELECT udt_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
            [$table, $column]
        );

        if ($columnInfo === null || $columnInfo->udt_name === $enumType) {
            return;
        }

        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP DEFAULT");
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE {$enumType} USING {$column}::{$enumType}");
    }

    private function isPostgres(): bool
    {
        return DB::getDriverName() === 'pgsql';
    }

    private function isMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function updateMysqlEnum(string $table, string $column, array $values, ?string $default = null, bool $restrictToValues = false): void
    {
        $database = DB::getDatabaseName();
        $columnInfo = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$database, $table, $column]
        );

        if ($columnInfo === null || stripos($columnInfo->COLUMN_TYPE, 'enum(') !== 0) {
            return;
        }

        $existingValues = $this->parseMysqlEnumValues($columnInfo->COLUMN_TYPE);

        if ($restrictToValues) {
            $this->sanitizeMysqlColumnValues($table, $column, $values, $default);
        }

        $finalValues = $restrictToValues
            ? array_values(array_unique($values))
            : $this->mergeEnumValues($existingValues, $values);

        if ($default !== null && !in_array($default, $finalValues, true)) {
            $finalValues[] = $default;
        }

        $enumDefinition = "ENUM('" . implode("','", $finalValues) . "')";
        $nullClause = $columnInfo->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';

        $defaultClause = '';
        if ($default !== null) {
            $defaultClause = " DEFAULT '{$default}'";
        } elseif ($columnInfo->COLUMN_DEFAULT !== null && in_array($columnInfo->COLUMN_DEFAULT, $finalValues, true)) {
            $defaultClause = " DEFAULT '{$columnInfo->COLUMN_DEFAULT}'";
        } elseif ($columnInfo->IS_NULLABLE === 'YES') {
            $defaultClause = ' DEFAULT NULL';
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$enumDefinition} {$nullClause}{$defaultClause}");
    }

    private function sanitizeMysqlColumnValues(string $table, string $column, array $allowedValues, ?string $replacement): void
    {
        $invalidValues = DB::table($table)
            ->distinct()
            ->whereNotNull($column)
            ->whereNotIn($column, $allowedValues)
            ->pluck($column)
            ->all();

        if (empty($invalidValues)) {
            return;
        }

        if ($replacement !== null) {
            DB::table($table)
                ->whereIn($column, $invalidValues)
                ->update([$column => $replacement]);
        } else {
            DB::table($table)
                ->whereIn($column, $invalidValues)
                ->update([$column => null]);
        }
    }

    private function parseMysqlEnumValues(string $columnType): array
    {
        if (!preg_match("/^enum\\((.*)\\)$/i", $columnType, $matches)) {
            return [];
        }

        $rawValues = str_getcsv($matches[1], ',', "'");

        return array_map('trim', $rawValues);
    }

    private function mergeEnumValues(array $existingValues, array $newValues): array
    {
        $finalValues = $existingValues;

        foreach ($newValues as $value) {
            if (!in_array($value, $finalValues, true)) {
                $finalValues[] = $value;
            }
        }

        return $finalValues;
    }
};
