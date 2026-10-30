<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The production migration is MySQL/MariaDB-specific (ALTER ... MODIFY
        // and a BEFORE INSERT trigger). SQLite is only used by the automated
        // test suite; Organization::booted already supplies a slug there.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement(
            'UPDATE `organizations` SET `slug` = CONCAT(LOWER(REPLACE(`name`, " ", "-")), "-", LPAD(FLOOR(RAND() * 100000), 5, "0"))'
            . ' WHERE `slug` IS NULL OR `slug` = "";'
        );

        DB::statement('ALTER TABLE `organizations` MODIFY `slug` VARCHAR(255) NOT NULL;');

        DB::statement('DROP TRIGGER IF EXISTS `organizations_before_insert_slug`;');
        DB::unprepared(<<<'SQL'
CREATE TRIGGER `organizations_before_insert_slug`
BEFORE INSERT ON `organizations`
FOR EACH ROW
BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        SET NEW.slug = CONCAT(LOWER(REPLACE(NEW.name, ' ', '-')), '-', LPAD(FLOOR(RAND() * 100000), 5, '0'));
    END IF;
END;
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS `organizations_before_insert_slug`;');
        DB::statement('ALTER TABLE `organizations` MODIFY `slug` VARCHAR(255) NOT NULL;');
    }
};
