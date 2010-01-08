-- PlanetLab changes to the drupal(4.7) database 

-- PlanetLab: Enable path and planetlab modules
INSERT INTO system (filename, name, type, description, status, throttle, bootstrap, schema_version) VALUES ('modules/path.module', 'path', 'module', '', 1, 0, 0, 0);
INSERT INTO system (filename, name, type, description, status, throttle, bootstrap, schema_version) VALUES ('modules/planetlab.module', 'planetlab', 'module', '', 1, 0, 0, 0);

-- PlanetLab: Create a default superuser
INSERT INTO users(uid,name,mail) VALUES(1,'drupal','');

-- PlanetLab: Replace default user login block with PlanetLab login block
update blocks set module='planetlab' where module='user' and delta='0';


-- Disallow anonymous users to register
--
-- an already populated database may have the variable 'user_register'
-- set. In that case you can update the value and clear the cache.
--
-- update variable set value='s:1:"0";' where name='user_register';
-- delete from cache;
--
insert into variable (name, value) values ('user_register', 's:1:"0";');

