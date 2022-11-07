<?php

namespace CodeTests\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Code\QueryBuilder\Executor;
use Code\QueryBuilder\Query\{Delete, Insert, Select, Update};
class ExecutorTest extends TestCase
{
    private static $conn = null;

    private $executor;

    public static function setUpBeforeClass(): void
    {
        self::$conn = new \PDO(dsn: 'mysql:dbname=products;host=localhost', username: 'root', password: 'ruaViruri175');
        // self::$conn->exec("CREATE SCHEMA products");
        self::$conn->exec("
            CREATE TABLE IF NOT EXISTS products.products (
                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255),
                price FLOAT,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            );
        ");

        self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public static function tearDownAfterClass(): void
    {
        self::$conn->exec('DROP TABLE products.products');
        // self::$conn->exec('DROP SCHEMA products');
    }

    public function setUp(): void
    {
        $this->executor = new Executor(self::$conn);
    }

    public function testInsertANewProductInADatabase()
    {
        $query = new Insert('products', ['name', 'price', 'created_at', 'updated_at']);

        $executor = $this->executor;
        $executor->setQuery($query);

        $executor->setParams(':name', 'Product 1')
            ->setParams(':price', 19.99)
            ->setParams(':created_at', date('Y-m-d H:i:s'))
            ->setParams(':updated_at', date('Y-m-d H:i:s'));

        $this->assertTrue($executor->execute());
    }

    public function testTheSelectionOfAProduct()
    {
        $query = new Select('products');

        $executor = $this->executor;

        $executor->setQuery($query);

        $executor->execute();

        $products = $executor->getResult();

        $this->assertEquals('Product 1', $products[0]['name']);
        $this->assertEquals(19.99, $products[0]['price']);
    }

    public function testUpdateAndGetASingleProduct()
    {
        $query = new Update('products', ['name'], ['id'=> 1]);

        $executor = $this->executor;
        $executor->setQuery($query);
        $executor->setParams(':name', 'Produto 1 editado');

        $this->assertTrue($executor->execute());

        $query = (new Select('products'))->where('id', '=', ':id');
        $executor = new Executor(self::$conn);

        $executor->setQuery($query);
        $executor->setParams(':id', 1); 

        $executor->execute();

        $products = $executor->getResult();
        $this->assertEquals('Produto 1 editado', $products[0]['name']);
    }

    public function testDeleteAProductFromDatabase()
    {
        $query = new Delete('products', ['id'=> 1]);

        $executor = $this->executor;
        $executor->setQuery($query);

        $this->assertTrue($executor->execute());

        $query = (new Select('products'))->where('id', '=', ':id');

        $executor = new Executor(self::$conn);

        $executor->setQuery($query);
        $executor->setParams(':id', 1); 

        $executor->execute();

        $products = $executor->getResult();

        $this->assertCount(0, $products);
    }
}