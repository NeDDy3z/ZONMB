<?php

declare(strict_types=1);

namespace Models;

use Helpers\ReplaceHelper;
use PDO;
use PDOException;
use Logic\DatabaseException;

class DatabaseConnector
{
    private static string $server;
    private static string $dbname;
    private static string $username;
    private static string $password;
    private static ?PDO $connection;


    /**
     * Load database credentials from config file
     * @return void
     * @throws DatabaseException
     */
    public static function init(): void
    {
        if (!isset($_ENV['database'])) {
            throw new DatabaseException('Nepodařilo se načíst konfigurační soubor databáze');
        }

        $database = $_ENV['database'];
        self::$server = $database['server'];
        self::$dbname = $database['dbname'];
        self::$username = $database['username'];
        self::$password = $database['password'];
    }

    /**
     * Connect to the database
     * @return void
     * @throws DatabaseException
     */
    private static function connect(): void
    {
        try {
            self::$connection = new PDO(
                dsn: "mysql:host=" . self::$server . ";dbname=" . self::$dbname,
                username: self::$username,
                password: self::$password,
                options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ],
            );
        } catch (PDOException $e) {
            throw new DatabaseException('Nepodařilo se připojit k databázi, některé funkce webu budou omezeny. Chyba: ' . $e->getMessage());
        }
    }


    /**
     * Close connection
     * @return void
     */
    public static function close(): void
    {
        self::$connection = null;
    }

    /**
     * Template function for selecting data from database
     * @param string $table
     * @param array<string> $items
     * @param string|null $conditions
     * @return array<array<string>>
     * @throws DatabaseException
     */
    private static function select(string $table, array $items, ?string $conditions): array
    {
        // If connection is null create a new connection
        if (!isset(self::$connection)) {
            self::connect();
        }

        // Prepare items for query
        $items = implode(separator: ',', array: $items);

        // Create query
        $query = "SELECT {$items} FROM {$table}";
        $query .= ($conditions) ? ' ' . $conditions : null;

        // Execute query and fetch data
        try {
            $result = self::$connection->query($query)->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException('Nepodařilo se načíst data z databáze: ' . $e->getMessage());
        }

        // Convert into Array<Array<String>>
        $resultArray = [];
        foreach ($result as $row) {
            $resultArray[] = array_filter(
                $row,
                function ($key) {
                    return !is_int($key);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $resultArray;
    }

    /**
     * Template function for inserting data into database
     * @param string $table
     * @param array<string> $items
     * @param array<int, int|string> $values
     * @return void
     * @throws DatabaseException
     */
    private static function insert(string $table, array $items, array $values): void
    {
        // If connection is null create new connection
        if (!isset(self::$connection)) {
            self::connect();
        }

        if (count($items) !== count($values)) {
            throw new DatabaseException('Počet položek a hodnot neodpovídá');
        }

        // Prepare items for query
        $itemRows = implode(separator: ',', array: $items);
        $itemVals = implode(',', array_fill(0, count($items), '?'));

        // Create query
        $query = "INSERT INTO {$table} ({$itemRows}) VALUES ({$itemVals});";

        // Execute query with values. Check if data were inserted
        try {
            self::$connection->prepare($query)->execute($values);
        } catch (PDOException $e) {
            throw new DatabaseException('Nepodařilo se vložit data do databáze: ' . $e->getMessage());
        }
    }

    /**
     * Template function for updating data in database
     * @param string $table
     * @param array<string> $items
     * @param array<int, string|null> $values
     * @param string $conditions
     * @return void
     * @throws DatabaseException
     */
    private static function update(string $table, array $items, array $values, string $conditions): void
    {
        // If connection is null create new connection
        if (!isset(self::$connection)) {
            self::connect();
        }

        if (count($items) !== count($values)) {
            throw new DatabaseException('Počet položek a hodnot pro aktualizaci neodpovídá');
        }

        // Prepare items for query
        foreach ($items as $key => $item) {
            $items[$key] = $item . ' = ?';
        }
        $itemRows = implode(separator: ' , ', array: $items);

        // Create query
        $query = "UPDATE {$table} SET {$itemRows} {$conditions};";

        // Execute query with values. Check if data were inserted
        try {
            self::$connection->prepare($query)->execute($values);
        } catch (PDOException $e) {
            throw new DatabaseException('Nepodařilo se vložit data do databáze: ' . $e->getMessage());
        }
    }



    // User manipulation
    /**
     * Get user data from database
     * @param int|null $id
     * @param string|null $username
     * @return array<string, float|int|string|null>|null
     * @throws DatabaseException
     */
    public static function selectUser(?int $id = null, ?string $username = null): ?array
    {
        if (!$id and !$username) {
            return null;
        }

        $condition = ($id) ? "WHERE id = $id" : "WHERE username = '$username'";

        return self::select(
            table: 'user',
            items: ['*'],
            conditions: $condition,
        )[0];
    }

    /**
     * Get all users from database
     * @param string|null $conditions
     * @return array<array<string, float|int|string|null>|int<0, max>>|null
     * @throws DatabaseException
     */
    public static function selectUsers(?string $conditions = null): ?array
    {
        return self::select(
            table: 'user',
            items: ['*'],
            conditions: $conditions,
        );
    }

    /**
     * Check if user exists in database
     * @param string $username
     * @return array<array<string, float|int|string|null>|int<0, max>>|null
     * @throws DatabaseException
     */
    public static function existsUser(string $username): ?array
    {
        return self::select(
            table: 'user',
            items: ['username'],
            conditions: 'WHERE username LIKE "' . $username . '"',
        );
    }

    /**
     * @param string $username
     * @param string $password
     * @param string|null $profile_image_path
     * @return void
     * @throws DatabaseException
     */
    public static function insertUser(string $username, string $fullname, string $password, ?string $profile_image_path): void
    {
        $items = ['username', 'fullname', 'password', 'role'];
        $values = [$username, $fullname, $password, 'user'];

        if ($profile_image_path) {
            array_push($items, 'profile_image_path');
            array_push($values, $profile_image_path);
        }

        self::insert(
            table: 'user',
            items: $items,
            values: $values,
        );
    }

    /**
     * @param int $id
     * @param string|null $username
     * @param string|null $profile_image_path
     * @return void
     * @throws DatabaseException
     */
    public static function updateUser(int $id, string $username = null, string $profile_image_path = null): void
    {
        $items = [];
        $values = [];

        if ($username) {
            $items[] = 'username';
            $values[] = $username;
        }
        if ($profile_image_path) {
            $items[] = 'profile_image_path';
            $values[] = $profile_image_path;
        }

        self::update(
            table: 'user',
            items: $items,
            values: $values,
            conditions: 'WHERE id = ' . $id,
        );
    }



    // Article manipulation
    /**
     * @param string $title
     * @param string $subtitle
     * @param string $content
     * @param array<string> $imagePaths
     * @param int $authorId
     * @return void
     * @throws DatabaseException
     */
    public static function insertArticle(string $title, string $subtitle, string $content, array $imagePaths = [], int $authorId = 1): void
    {
        $slug = ReplaceHelper::getUrlFriendlyString($title);
        $imagePaths = implode(',', $imagePaths);

        self::insert(
            table: 'article',
            items: ['title', 'subtitle', 'content', 'slug', 'image_path', 'author_id', 'created_at'],
            values: [$title, $subtitle, $content, $slug, $imagePaths, $authorId, date('Y-m-d')],
        );
    }

    /**
     * @param string $conditions
     * @return array<string>|null
     * @throws DatabaseException
     */
    public static function selectArticle(string $conditions): ?array
    {
        return self::select(
            table: 'article',
            items: ['*'],
            conditions: $conditions,
        )[0];
    }

    /**
     * @param string|null $conditions
     * @return array<array<string>>|null
     * @throws DatabaseException
     */
    public static function selectArticles(?string $conditions = null): ?array
    {
        return self::select(
            table: 'article',
            items: ['*'],
            conditions: $conditions,
        );
    }
}
