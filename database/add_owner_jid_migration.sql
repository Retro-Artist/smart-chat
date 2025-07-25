-- Migration: Add owner_jid column to users table
-- Date: 2025-07-25
-- Description: Add owner_jid column to store WhatsApp JID (e.g., "556198361410@s.whatsapp.net")

ALTER TABLE users ADD COLUMN owner_jid VARCHAR(255) NULL AFTER username;

-- Add index for faster lookups
CREATE INDEX idx_users_owner_jid ON users(owner_jid);

-- Update existing users - this will be set automatically when they connect WhatsApp
-- No data migration needed as owner_jid will be populated on first WhatsApp connection