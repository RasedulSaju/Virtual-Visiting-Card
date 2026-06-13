-- ============================================================
-- Migration 003 — Rename reserved column `key` → `skey`
--
-- Run this ONLY if you already imported migration_001 or
-- migration_002 with the old `key` column name.
-- Skip if starting fresh (schema.sql already uses `skey`).
-- ============================================================

ALTER TABLE `settings`
  CHANGE COLUMN `key` `skey` VARCHAR(100) NOT NULL;
