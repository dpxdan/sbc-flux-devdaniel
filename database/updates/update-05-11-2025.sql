SET FOREIGN_KEY_CHECKS=0;

DELETE from `system` WHERE `name` = 'default_language' and `value` = 'Portuguese';

SET FOREIGN_KEY_CHECKS=1;
