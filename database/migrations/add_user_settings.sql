ALTER TABLE users 
ADD COLUMN settings JSON DEFAULT '{"theme": "light", "language": "en"}';
