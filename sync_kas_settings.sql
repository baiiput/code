-- Sync kas_settings with actual investor data
-- This script updates the kas_settings table to match the actual values from the investors table

-- Update total_modal_investor
UPDATE kas_settings
SET setting_value = COALESCE((SELECT SUM(total_modal) FROM investors), 0)
WHERE setting_key = 'total_modal_investor';

-- Update modal_tersedia
UPDATE kas_settings
SET setting_value = COALESCE((SELECT SUM(modal_tersedia) FROM investors), 0)
WHERE setting_key = 'modal_tersedia';

-- Update modal_allocated
UPDATE kas_settings
SET setting_value = COALESCE((SELECT SUM(modal_allocated) FROM investors), 0)
WHERE setting_key = 'modal_allocated';

-- Verify the sync
SELECT
    'kas_settings' as source,
    (SELECT setting_value FROM kas_settings WHERE setting_key = 'total_modal_investor') as total_modal,
    (SELECT setting_value FROM kas_settings WHERE setting_key = 'modal_tersedia') as modal_tersedia,
    (SELECT setting_value FROM kas_settings WHERE setting_key = 'modal_allocated') as modal_allocated
UNION ALL
SELECT
    'investors_table' as source,
    COALESCE(SUM(total_modal), 0) as total_modal,
    COALESCE(SUM(modal_tersedia), 0) as modal_tersedia,
    COALESCE(SUM(modal_allocated), 0) as modal_allocated
FROM investors;
