INSERT INTO author (id,username, password, email, bio, favourite_section) VALUES (101,'jim','********','jim@ibatis.apache.org','','NEWS');
INSERT INTO author (id,username, password, email, bio, favourite_section) VALUES (102,'sally','********','sally@ibatis.apache.org',null,'VIDEOS');

INSERT INTO blog (id,author_id,title) VALUES (1,101,'Jim Business');
INSERT INTO blog (id,author_id,title) VALUES (2,102,'Bally Slog');

INSERT INTO post (id,blog_id,author_id,created_on,section,subject,body,draft) VALUES (1,1,101,'2007-12-05-00.00.00','NEWS','Corn nuts','I think if I never smelled another corn nut it would be too soon...',1);
INSERT INTO post (id,blog_id,author_id,created_on,section,subject,body,draft) VALUES (2,1,101,'2008-01-12-00.00.00','VIDEOS','Paul Hogan on Toy Dogs','That''s not a dog.  THAT''s a dog!',0);
INSERT INTO post (id,blog_id,author_id,created_on,section,subject,body,draft) VALUES (3,2,102,'2007-12-05-00.00.00','PODCASTS','Monster Trucks','I think monster trucks are great...',1);
INSERT INTO post (id,blog_id,author_id,created_on,section,subject,body,draft) VALUES (4,2,102,'2008-01-12-00.00.00','IMAGES','Tea Parties','A tea party is no place to hold a business meeting...',0);

INSERT INTO post (id,blog_id,author_id,created_on,section,subject,body,draft) VALUES (5,null,101,'2008-01-12-00.00.00','IMAGES','An orphaned post','this post is orphaned',0);

INSERT INTO tag (id,name) VALUES (1,'funny');
INSERT INTO tag (id,name) VALUES (2,'cool');
INSERT INTO tag (id,name) VALUES (3,'food');

INSERT INTO post_tag (post_id,tag_id) VALUES (1,1);
INSERT INTO post_tag (post_id,tag_id) VALUES (1,2);
INSERT INTO post_tag (post_id,tag_id) VALUES (1,3);
INSERT INTO post_tag (post_id,tag_id) VALUES (2,1);
INSERT INTO post_tag (post_id,tag_id) VALUES (4,3);

INSERT INTO comment (id,post_id,name,comment) VALUES (1,1,'troll','I disagree and think...');
INSERT INTO comment (id,post_id,name,comment) VALUES (2,1,'anonymous','I agree and think troll is an...');
INSERT INTO comment (id,post_id,name,comment) VALUES (3,3,'rider','I prefer motorcycles to monster trucks...');

INSERT INTO node (id, parent_id) VALUES (1,null);
INSERT INTO node (id, parent_id) VALUES (2,1); 
INSERT INTO node (id, parent_id) VALUES (3,1);
INSERT INTO node (id, parent_id) VALUES (4,2);
INSERT INTO node (id, parent_id) VALUES (5,2);
INSERT INTO node (id, parent_id) VALUES (6,3);
INSERT INTO node (id, parent_id) VALUES (7,3);