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

        $this->addEnumValues('parcel_deliveries_status_enum', $statuses);
        $this->setEnumDefault('parcel_deliveries', 'status', 'pending');

        $this->addEnumValues('parcel_delivery_trackings_status_enum', $statuses);
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
};
