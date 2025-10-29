-- Create compatibility views in world databases
USE travian_testworld;
DROP VIEW IF EXISTS villages;
CREATE VIEW villages AS SELECT * FROM vdata;
DROP VIEW IF EXISTS alliances;
CREATE VIEW alliances AS SELECT * FROM alidata;
DROP VIEW IF EXISTS marketplace;
CREATE VIEW marketplace AS SELECT * FROM market;

USE travian_demo;
DROP VIEW IF EXISTS villages;
CREATE VIEW villages AS SELECT * FROM vdata;
DROP VIEW IF EXISTS alliances;
CREATE VIEW alliances AS SELECT * FROM alidata;
DROP VIEW IF EXISTS marketplace;
CREATE VIEW marketplace AS SELECT * FROM market;
