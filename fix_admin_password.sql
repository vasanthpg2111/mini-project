-- Fix admin password to 'admin123'
-- Run this in phpMyAdmin SQL tab or via mysql command line

UPDATE admins 
SET password_hash = '$2y$10$eqG7fHFiuJrDYSdufkGfMuMB5xvT7CkkLPDvtokVqEiqG2Gg422gG' 
WHERE username = 'admin';

-- Verify it worked:
SELECT username, 
       CASE 
         WHEN password_hash LIKE '$2y$%' THEN 'Hash looks valid'
         ELSE 'Invalid hash format'
       END as hash_status
FROM admins 
WHERE username = 'admin';

