# Scalability

I received a request from a PHP developer ("padawan") who wanted to learn more about developing scalable applications and what are best practices for bringing your applications from bare metal to the next level.

## Exercise 1: connect to db with PDO

In order to know what skills padawan had, I first let him connect to a database using PDO. This allows me to see if he already understands the concepts of abstraction layers and see if he's capable of securing his applications using bind parameters in his queries.

### Exercise 1

When connecting to the MySQL database, you most likely used mysql_connect() and mysql_query() functions, what I want you to do is take a look at php.net/pdo and write a simple code that connects to a database table and performs the following operations:

- retrieve a list of all products of that database (including a display of category)
- retrieve a list of all products of a given category (e.g. 'vegetable')
- update product "apple" and set price to 0.24
- remove pineapples from the product table

You can use this database schema:

    DROP TABLE IF EXISTS `pm_product`;
    CREATE TABLE `pm_product` (
        `productId` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `label` VARCHAR(45) NOT NULL,
        `categoryId` INT UNSIGNED NOT NULL,
        `price` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (`productId`),
        UNIQUE KEY `pm_product_label_uk` (`label`)
    );

    DROP TABLE IF EXISTS `pm_category`;
    CREATE TABLE `pm_category` (
        `categoryId` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `category` varchar(45) NOT NULL,
        PRIMARY KEY (`categoryId`),
        UNIQUE KEY `pm_category_category_uk` (`category`)
    );

    INSERT INTO `pm_product` (`label`,`categoryId`,`price`) 
        VALUES 
            ('apple',1, 0.15), 
            ('bannana', 1, 0.42), 
            ('potato', 2, 0.03), 
            ('coliflower', 2, 0.12), 
            ('pineapple', 1, 0.95);
            
    INSERT INTO `pm_category` (`category`) 
       VALUES 
           ('fruit'),
           ('vegetable');

## Exercise 2

Now you have functionality that can retrieve the data from a datastorage, but we're not there yet. We now need to devide responsibilities and apply a [Table Gateway](http://martinfowler.com/eaaCatalog/tableDataGateway.html) with a [Data Mapper](http://martinfowler.com/eaaCatalog/dataMapper.html).

![TGwDM](http://plopster.blob.core.windows.net/phpmentoring/table_gateway_with_data_mapper.png)

With [Dependency Injection (DI)](http://martinfowler.com/articles/injection.html) in mind, we want our mapper to be the core engine knowing how to connect to a table gateway using methods where we just pass in our model.

---

Copyright 2013 Michelangelo van Dam, used under a [Creative Commons Attribution-ShareAlike 3.0 Unported License](http://creativecommons.org/licenses/by-sa/3.0/deed.en_US)