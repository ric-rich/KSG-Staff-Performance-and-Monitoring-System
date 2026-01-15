ALTER TABLE `users`
ADD COLUMN `is_team_member` TINYINT(1) NOT NULL DEFAULT 0 AFTER `profile_picture`;