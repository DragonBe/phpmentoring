<?php

abstract class Model
{
    /**
     * Constructor for model classes
     *
     * @param null $data
     */
    public function __construct($data = null)
    {
        if (null !== $data) {
            $this->populate($data);
        }
    }

    /**
     * Populates the model with row data
     *
     * @param $row
     * @return $this
     */
    abstract public function populate($row);

    /**
     * Converts the model to an array
     *
     * @return mixed
     */
    abstract public function toArray();
}

class Product extends Model
{
    /**
     * @var int ID for the product
     */
    protected $_productId;
    
    /**
     * @var string Label for the product
     */
    protected $_label;
    
    /**
     * @var Category The category for the product (optional)
     */
    protected $_category; // Shouldn't it be categoryId here?
    
    /**
     * @var float The price for the product
     */
    protected $_price;

    /**
     * @param \Category $category
     */
    public function setCategory($category)
    {
        $this->_category = $category;
    }

    /**
     * @return \Category
     */
    public function getCategory()
    {
        return $this->_category;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->_label = $label;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->_price = $price;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->_price;
    }

    /**
     * @param int $productId
     */
    public function setProductId($productId)
    {
        $this->_productId = $productId;
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->_productId;
    }
    
    public function populate($row)
    {
        $this->setProductId($row['productId']);
        $this->setLabel($row['label']);
        $this->setCategory($row['categoryId']);
        $this->setPrice($row['price']);
        return $this;
    }
    
    public function toArray()
    {
        return array (
            'productId' => $this->getProductId(),
            'label' => $this->getLabel(),
            'category' => $this->getCategory(),
            'price' => $this->getPrice(),
        );
    }
}

class Category extends Model
{
    /**
     * @var int The ID for the category
     */
    protected $_categoryId;
    
    /**
     * @var string The label for the category
     */
    protected $_category;

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->_category = $category;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->_category;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->_categoryId = $categoryId;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->_categoryId;
    }
    
    public function populate($row)
    {
        $this->setCategoryId($row['categoryId']);
        $this->setCategory($row['category']);
    }
    
    public function toArray()
    {
        return array (
            'categoryId' => $this->getCategoryId(),
            'category' => $this->getCategory(),
        );
    }
}

abstract class DbTable
{
    /**
     * @var array The configuration for the db table
     */
    protected $_config;
    
    /**
     * @var string The name of the table
     */
    protected $_tableName;
    
    /**
     * @var PDO Adapter
     */
    protected $_dbAdapter;
    
    /**
     * Constructor for this class
     *
     * @param null|array $config
     */
    public function __construct($config = null)
    {
        if (null !== $config) {
            $this->setConfig($config);
        }
    }

    /**
     * Sets the configuration for this connection
     *
     * @param $config
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setConfig($config)
    {
        $required = array ('host', 'dbname', 'username', 'password');
        foreach ($required as $param) {
            if (!array_key_exists($param, $config)) {
                throw new InvalidArgumentException('Missing required parameter ' . $param);
            }
        }
        $config = new ArrayObject($config, ArrayObject::ARRAY_AS_PROPS);
        $this->_config = $config;
        return $this;
    }

    /**
     * Retrieves the configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Sets up the connection
     *
     * @return $this
     * @throws RuntimeException
     */
    protected function _setConnection()
    {
        if (null === $this->getConfig()) {
            throw new RuntimeException('Connection details are not set yet');
        }
        $dsn = sprintf('mysql:dbname=%s;host=%s', $this->getConfig()->dbname, $this->getConfig()->host);
        $pdo = new PDO($dsn, $this->getConfig()->username, $this->getConfig()->password);
        $this->_dbAdapter = $pdo;
        return $this;
    }

    /**
     * Returns the connection resource
     *
     * @return PDO
     */
    protected function _getConnection()
    {
        if (null === $this->_dbAdapter) {
            $this->_setConnection();
        }
        return $this->_dbAdapter;
    }

    /**
     * Find results matching given primary key
     *
     * @param $value The value you're searching
     * @param string $primaryKey The name of the primary key
     * @return array
     */
    public function find($value, $primaryKey = 'id')
    {
        $stmt = $this->_getConnection()->prepare(
            sprintf('SELECT * FROM %s WHERE %s = ?', $this->_tableName, $primaryKey));
        $stmt->execute($value);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Find a single occurance
     *
     * @param null|string $where
     * @param null|string $order
     * @return mixed
     */
    public function findRow($where = null, $order = null)
    {
        $sql = sprintf('SELECT * FROM %s', $this->_tableName);
        if (null !== $where) {
            $sql .= ' WHERE ' . $where;
        }
        if (null !== $order) {
            $sql .= ' ORDER BY ' . $order;
        }
        $stmt = $this->_getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Finds all results matching given optional conditions
     *
     * @param null|string $where
     * @param null|string $order
     * @param null|int $limit
     * @param null|int  $offset
     * @return array
     */
    public function findAll($where = null, $order = null, $limit = null, $offset = null)
    {
        $sql = sprintf('SELECT * FROM %s', $this->_tableName);
        if (null !== $where) {
            $sql .= ' WHERE ' . $where;
        }
        if (null !== $order) {
            $sql .= ' ORDER BY ' . $order;
        }
        if (null !== $limit) {
            if (null === $offset) {
                $offset = 0;
            }
            $sql .= ' LIMIT ' . $limit . ',' . $offset;
        }
        $stmt = $this->_getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    /**
     * Update one or more rows
     * 
     * @param string $set
     * @param string $where
     * @return array
     * @throws Exception
     */
    public function update($set, $where)
    {
        $sql = sprintf('UPDATE `%s`', $this->_tableName);
        if (null === $set) {
            throw new Exception('Missing column(s) to be modified and their corresponding value(s)');
        } else {
            $sql .= ' SET ' . $set;
        }
        
        if (null !== $where) {
            $sql .= ' WHERE ' . $where;
        }
        $stmt = $this->_getConnection()->prepare($sql);
        $stmt->execute();
    }
    
    /**
     * Delete one or more rows
     * 
     * @param string $where
     */
    public function delete($where)
    {
        $sql = sprintf('DELETE FROM `%s`', $this->_tableName);
        if (null !== $where) {
            $sql .= ' WHERE ' . $where;
        }
        $stmt = $this->_getConnection()->prepare($sql);
        $stmt->execute();
    }
    
    /**
     * Insert data in the table
     * 
     * @param string $columnNames
     * @param string $values
     * @throws Exception
     */
    public function insert($columnNames, $values)
    {
        $sql = sprintf('INSERT INTO `%s`', $this->_tableName);
        if (null === $columnNames) {
            throw new Exception('Missing column names for data to be inserted in');
        } else {
            $sql .= sprintf(' (%s) ', $columnNames);
        }
        if (null === $values) {
            throw new Exception('Missing values to be inserted');
        } else {
            $sql .= sprintf(' VALUES (%s)', $values);
        }
        $stmt = $this->_getConnection()->prepare($sql);
        $stmt->execute();
    }
}

class ProductTable extends DbTable
{
    protected $_tableName = 'pm_product';
}

class CategoryTable extends DbTable
{
    protected $_tableName = 'pm_category';
}

class Mapper
{
    protected $_dbTable;

    public function __construct($dbTable = null)
    {
        if (null !== $dbTable) {
            $this->setDbTable($dbTable);
        }
    }
    /**
     * Sets the DbTable
     * @param DbTable $dbTable
     * @return $this
     */
    public function setDbTable(DbTable $dbTable)
    {
        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * Fetches the DbTable
     *
     * @return DbTable
     * @throws RuntimeException
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            throw new RuntimeException('DbTable was not set');
        }
        return $this->_dbTable;
    }

    /**
     * @see DbTable::find()
     */
    public function find(Model $model, $value, $primaryKey = 'id')
    {
        $result = $this->getDbTable()->find($value, $primaryKey);
        $resultSet = array();
        foreach ($result as $row) {
            $obj = clone $model;
            $resultSet[] = $obj->populate($row);
        }
        return $resultSet;
    }

    /**
     * @see DbTable::findRow
     */
    public function findRow(Model $model, $where = null, $order = null)
    {
        $result = $this->getDbTable()->findRow($where, $order);
        $model->populate($result);
    }

    public function findAll(Model $model, $where = null, $order = null, $limit = null, $offset = null)
    {
        $result = $this->getDbTable()->findAll($where, $order, $limit, $offset);
        $resultSet = array();
        foreach ($result as $row) {
            $obj = clone $model;
            $resultSet[] = $obj->populate($row);
        }
        return $resultSet;
    }
    
    /**
     * @see DbTable::update
     */
    public function update(Model $model, $set, $where)
    {
        $this->getDbTable()->update($set, $where);
    }
    
    /**
     * @see DbTable::delete
     */
    public function delete(Model $model, $where)
    {
        $this->getDbTable()->delete($where);
    }
    
    public function insert(Model $model, $columnNames, $values)
    {
        $this->getDbTable()->insert($columnNames, $values);
    }
}

$config = array (
    'host' => 'localhost',
    'dbname' => 'phpmentoring',
    'username' => 'phpmentoring',
    'password' => 'gophp',
);

// configure mappers and gateways
$productMapper = new Mapper(new ProductTable($config));
$categoryMapper = new Mapper(new CategoryTable($config));

/**
 * Display all products in a list
 * 
 * @param array $arrayOfProducts
 */
function displayAllProducts($arrayOfProducts)
{
    echo '<p>All Products:<ul>';
    foreach ($arrayOfProducts as $product) {
        echo '<li>Label: "' . $product->getLabel() . '", categoryId: "' . $product->getCategory() . '", price: "' . $product->getPrice() . '"</li>';
    }
    echo '</ul></p>';
}

// 1. List all products in table
echo '1. List all products in table<br />';
$product = new Product();
$products = $productMapper->findAll($product);
displayAllProducts($products);

// This is outputting "array(2) { [0]=> NULL [1]=> NULL }" and I would expect 
// something like this:
// "array(2) { [0]=> object(Category)= #X (2) {["_categoryId":protected]=> string(1) "1" ["_category":protected]=> string(5) "fruit"} NULL [1]=> object(Category)= #X (2) {["_categoryId":protected]=> string(1) "2" ["_category":protected]=> string(9) "vegetable"} }" 
//$category = new Category();
//$categories = $categoryMapper->findAll($category);
//var_dump($categories);

// 2. List all products in the 'vegetable' category
echo '2. List all products in the \'vegetable\' category<br />';
// Get the categoryId of the vegetable category
$vegetableCategory = new Category();
$categoryMapper->findRow($vegetableCategory, "`category` = 'vegetable'");
$vegetableCategoryId = $vegetableCategory->getCategoryId();

// Get all the products in the 'vegetable' category
$productVegetable = new Product();
$allVegetables = $productMapper->findAll($productVegetable, "`categoryId` = '". $vegetableCategoryId . "'");
displayAllProducts($allVegetables);

// 3. Update the product 'apple' and set its price to 0.24
echo '3. Update the product \'apple\' and set its price to 0.24<br />';
// Show old price
$oldAppleProduct = new Product();
$productMapper->findRow($oldAppleProduct, "`label` = 'apple'");
echo 'Product "' . $oldAppleProduct->getLabel() . '" used to cost "' . $oldAppleProduct->getPrice() . '"<br>';

// Update price and set to 0.24
$appleProduct = new Product();
$updatedApple = $productMapper->update($appleProduct, "`price` = 0.24", "`label` = 'apple'");

// Check price of the new 'apple' product (post-Jobs... ;)
$newAppleProduct = new Product();
$productMapper->findRow($newAppleProduct, "`label` = 'apple'");
echo 'Product "' . $newAppleProduct->getLabel() . '" costs now "' . $newAppleProduct->getPrice() . '"<br>';

// Update price and set back to its initial value of 0.15
$appleProductBack = new Product();
$updatedApple = $productMapper->update($appleProductBack, "`price` = 0.15", "`label` = 'apple'");

// 4. Remove product 'pineapple' from the product table
echo '4. Remove product \'pineapple\' from the product table<br />';
// Remove the product
$pineAppleProduct = new Product();
$productMapper->delete($pineAppleProduct, "`label` = 'pineapple'");

// List all the products to be sure the 'pineapple' product is gone
$anotherProduct = new Product();
$allProducts = $productMapper->findAll($anotherProduct);
displayAllProducts($allProducts);

// Insert the pineapple product again so that we don't need to do it by hand :)
$theLastProduct = new Product();
$productMapper->insert($theLastProduct, '`label`, `categoryId`, `price`', "'pineapple', 1, 0.95");

