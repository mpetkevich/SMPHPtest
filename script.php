<?php

namespace App;

use mysqli;
use Exception;

error_reporting(-1);
//error_reporting(E_ALL & ~E_NOTICE);

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASSWORD = '';
const DB_DATABASE = 'testdb';

interface DatabaseConnectionInterface
{
    public function initDbConnection();
}

class  MysqliDatabaseConnection implements DatabaseConnectionInterface
{
    public mysqli $connection;
    protected static ?MysqliDatabaseConnection $instance = null;

    public static function getInstance(): MysqliDatabaseConnection
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    protected function __clone()
    {
    }

    protected function __construct()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.\n");
    }

    public function initDbConnection()
    {
        $this->connection = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASSWORD,
            DB_DATABASE
        );

        if ($this->connection->connect_error) {
            throw new Exception("Connection failed: " . $this->connection->connect_error . "\n");
        }
    }
}

interface UserRepositoryInterface
{
    public function save(User $user): bool;

    public function find($id): ?array;
}

class User
{
    private string $id;
    private string $name;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly DatabaseConnectionInterface $db)
    {
        $db->initDbConnection();
    }

    public function save(User $user): bool
    {
        $userId = $user->getId();
        $user = $this->find($userId);
        if ($user) {
            echo "User with the same GUID already exists in the database\n";
            return false;
        }

        $userName = $user->getName();
        $sql = "INSERT INTO users (id, name) VALUES (?, ?)";
        $statement = $this->db->connection->prepare($sql);
        $statement->bind_param("ss", $userId, $userName);
        $result = $statement->execute();
        $statement->close();
        return $result;
    }

    public function find($id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = ?";
        $statement = $this->db->connection->prepare($sql);
        $statement->bind_param("s", $id);
        $statement->execute();
        $result = $statement->get_result();
        $statement->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return array('id' => $row['id'], 'name' => $row['name']);
        } else {
            return null;
        }
    }
}

try {
    $db = MysqliDatabaseConnection::getInstance();
    $userRepository = new UserRepository($db);

    // CLI script
    if ($argc == 1) {
        echo "Please provide a command.\n";
        exit(1);
    }

    $command = $argv[1];

    if ($command == 'add') {
        $user = new User('2cdaab9c-0f32-b9c0-aec0-4cc27e407cff', 'John Doe', 'johndoe@example.com');
        $result = $userRepository->save($user);

        if ($result) {
            echo "User saved successfully.\n";
        } else {
            echo "Error saving user.\n";
        }
    } else if ($command == 'find') {
        $user = $userRepository->find('2cdaab9c-0f32-b9c0-aec0-4cc27e407cff');

        if ($user) {
            echo "User found: {$user['name']}\n";
        } else {
            echo "User not found.\n";
        }
    } else {
        echo "Invalid command.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo $e . "\n";
}