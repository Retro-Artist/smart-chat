-- Add token column to whatsapp_instances table
-- This stores the unique Evolution API instance token used for webhook authentication

ALTER TABLE `whatsapp_instances` 
ADD COLUMN `token` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Evolution API instance token for webhook authentication' 
AFTER `instance_id`;

-- Add index for faster token lookups during webhook validation
CREATE INDEX `idx_whatsapp_instances_token` ON `whatsapp_instances` (`token`);

-- Add index for instance name lookups (used by webhook handler)
CREATE INDEX `idx_whatsapp_instances_name` ON `whatsapp_instances` (`instance_name`);