-- PlanetLab changes to the drupal(4.7) database 

-- PlanetLab: Enable path and planetlab modules
INSERT INTO system (filename, name, type, description, status, throttle, bootstrap, schema_version) VALUES ('modules/path.module', 'path', 'module', '', 1, 0, 0, 0);
INSERT INTO system (filename, name, type, description, status, throttle, bootstrap, schema_version) VALUES ('modules/planetlab.module', 'planetlab', 'module', '', 1, 0, 0, 0);

-- PlanetLab: Create a default superuser
INSERT INTO users(uid,name,mail) VALUES(1,'drupal','');

-- PlanetLab: Replace default user login block with PlanetLab login block
update blocks set module='planetlab' where module='user' and delta=0;
